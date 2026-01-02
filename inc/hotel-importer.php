<?php
/**
 * Apollo Hotels Importer
 *
 * Fetches hotels from Apollo API and saves as WordPress posts with detailed logging.
 *
 * @package Seminargo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Seminargo_Hotel_Importer {

    private $api_url = 'https://lister-staging.seminargo.com/pricelist/graphql';
    private $finder_base_url = 'https://finder.dev.seminargo.eu/';
    private $log_option = 'seminargo_hotels_import_log';
    private $last_import_option = 'seminargo_hotels_last_import';
    private $imported_ids_option = 'seminargo_hotels_imported_ids';
    private $auto_import_enabled_option = 'seminargo_auto_import_enabled';
    private $auto_import_progress_option = 'seminargo_auto_import_progress';

    // Log batching to avoid database thrashing
    private $log_batch = [];
    private $log_batch_size = 5; // Flush logs every 5 entries for real-time visibility

    public function __construct() {
        // Admin menu
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

        // AJAX handlers
        add_action( 'wp_ajax_seminargo_fetch_hotels', [ $this, 'ajax_fetch_hotels' ] );
        add_action( 'wp_ajax_seminargo_start_batched_import', [ $this, 'ajax_start_batched_import' ] );
        add_action( 'wp_ajax_seminargo_skip_to_phase2', [ $this, 'ajax_skip_to_phase2' ] );
        add_action( 'wp_ajax_seminargo_get_import_progress', [ $this, 'ajax_get_import_progress' ] );
        add_action( 'wp_ajax_seminargo_get_logs', [ $this, 'ajax_get_logs' ] );
        add_action( 'wp_ajax_seminargo_clear_logs', [ $this, 'ajax_clear_logs' ] );
        add_action( 'wp_ajax_seminargo_delete_all_hotels', [ $this, 'ajax_delete_all_hotels' ] );
        add_action( 'wp_ajax_seminargo_toggle_auto_import', [ $this, 'ajax_toggle_auto_import' ] );
        add_action( 'wp_ajax_seminargo_reset_auto_import', [ $this, 'ajax_reset_auto_import' ] );
        add_action( 'wp_ajax_seminargo_get_auto_import_status', [ $this, 'ajax_get_auto_import_status' ] );

        // Cron
        add_action( 'init', [ $this, 'register_cron' ] );
        add_action( 'seminargo_hotels_cron', [ $this, 'run_auto_import_batch' ] );
        add_action( 'seminargo_process_import_batch', [ $this, 'process_single_batch' ] );

        // Register hotel post type if not exists
        add_action( 'init', [ $this, 'register_post_type' ] );

        // Add cron interval
        add_filter( 'cron_schedules', [ $this, 'add_cron_interval' ] );

        // Register WP-CLI command for imports without timeouts (WP Engine compatible)
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'seminargo import-hotels', [ $this, 'cli_import_hotels' ] );
        }

        // Add meta boxes for hotel edit page
        add_action( 'add_meta_boxes', [ $this, 'add_hotel_meta_boxes' ] );

        // Save featured meta
        add_action( 'save_post_hotel', [ $this, 'save_featured_meta' ] );

        // Add featured column to hotels list
        add_filter( 'manage_hotel_posts_columns', [ $this, 'add_featured_column' ] );
        add_action( 'manage_hotel_posts_custom_column', [ $this, 'render_featured_column' ], 10, 2 );

        // Column styles
        add_action( 'admin_head', [ $this, 'hotel_column_styles' ] );

        // Quick edit support
        add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_featured' ], 10, 2 );
        add_action( 'save_post_hotel', [ $this, 'save_quick_edit_featured' ] );
        add_action( 'admin_footer-edit.php', [ $this, 'quick_edit_javascript' ] );
    }

    /**
     * Bypass WordPress file type validation for image imports
     */
    public function bypass_filetype_check( $data, $file, $filename, $mimes ) {
        // Always check if it's an image file by content first (most reliable)
        if ( file_exists( $file ) && function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            $mime = finfo_file( $finfo, $file );
            finfo_close( $finfo );

            $mime_to_ext = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
                'image/bmp' => 'bmp',
                'image/tiff' => 'tiff',
            ];

            if ( isset( $mime_to_ext[ $mime ] ) ) {
                // Force override - we detected it's an image, so allow it
                $data['ext'] = $mime_to_ext[ $mime ];
                $data['type'] = $mime;
                $data['proper_filename'] = false;
                return $data;
            }
        }

        // If we already have a valid type, return it
        if ( ! empty( $data['type'] ) && ! empty( $data['ext'] ) ) {
            return $data;
        }

        // Only bypass during hotel import if we couldn't detect MIME type
        // This is a fallback for edge cases
        if ( $this->is_importing_images() ) {
            // If we're importing and can't detect, assume it's valid
            // WordPress will still validate the actual file content
            return $data;
        }

        return $data;
    }

    /**
     * Allow all image MIME types during import
     */
    public function allow_all_image_mimes( $mimes ) {
        // Only modify during hotel import
        if ( ! $this->is_importing_images() ) {
            return $mimes;
        }

        // Ensure all common image formats are allowed
        $mimes['jpg|jpeg|jpe'] = 'image/jpeg';
        $mimes['gif'] = 'image/gif';
        $mimes['png'] = 'image/png';
        $mimes['webp'] = 'image/webp';
        $mimes['svg'] = 'image/svg+xml';
        $mimes['bmp'] = 'image/bmp';
        $mimes['tiff|tif'] = 'image/tiff';

        return $mimes;
    }

    /**
     * Check if we're currently importing images
     * Uses a static flag to avoid repeated option reads
     */
    private static $importing_images_flag = null;
    
    private function is_importing_images() {
        // Use static flag to cache the result during a single request
        if ( self::$importing_images_flag !== null ) {
            return self::$importing_images_flag;
        }
        
        // Check if we're in Phase 2 (image import phase)
        $progress = get_option( $this->auto_import_progress_option, [] );
        self::$importing_images_flag = isset( $progress['phase'] ) && $progress['phase'] === 'phase2';
        
        return self::$importing_images_flag;
    }

    /**
     * Add column styles
     */
    public function hotel_column_styles() {
        global $pagenow, $typenow;
        if ( $pagenow === 'edit.php' && $typenow === 'hotel' ) {
            echo '<style>
                .column-hotel_image { width: 70px; }
                .column-hotel_rating { width: 80px; }
                .column-hotel_rooms { width: 60px; }
                .column-hotel_capacity { width: 90px; }
                .column-featured_landing { width: 90px; }
            </style>';
        }
    }

    /**
     * Add featured field to quick edit
     */
    public function quick_edit_featured( $column_name, $post_type ) {
        if ( $post_type !== 'hotel' || $column_name !== 'featured_landing' ) {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label class="inline-edit-featured">
                    <input type="checkbox" name="featured_on_landing" value="1">
                    <span class="checkbox-title">Auf Startseite anzeigen</span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Save quick edit featured field
     */
    public function save_quick_edit_featured( $post_id ) {
        // Skip if not quick edit or bulk edit
        if ( ! isset( $_POST['_inline_edit'] ) && ! isset( $_POST['hotel_featured_nonce_field'] ) ) {
            return;
        }

        // Check inline edit nonce
        if ( isset( $_POST['_inline_edit'] ) && ! wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) ) {
            return;
        }

        // Skip if regular save (handled by other function)
        if ( isset( $_POST['hotel_featured_nonce_field'] ) ) {
            return;
        }

        if ( isset( $_POST['featured_on_landing'] ) && $_POST['featured_on_landing'] === '1' ) {
            update_post_meta( $post_id, 'featured_on_landing', '1' );
        } else {
            delete_post_meta( $post_id, 'featured_on_landing' );
        }
    }

    /**
     * JavaScript for quick edit to populate checkbox
     */
    public function quick_edit_javascript() {
        global $typenow;
        if ( $typenow !== 'hotel' ) {
            return;
        }
        ?>
        <script>
        jQuery(function($) {
            var $inlineEdit = inlineEditPost.edit;
            inlineEditPost.edit = function(id) {
                $inlineEdit.apply(this, arguments);

                var postId = 0;
                if (typeof(id) === 'object') {
                    postId = parseInt(this.getId(id));
                }

                if (postId > 0) {
                    var $row = $('#post-' + postId);
                    var featured = $row.find('.column-featured_landing').text().trim();
                    var isChecked = featured.indexOf('Aktiv') !== -1;
                    $('input[name="featured_on_landing"]', '.inline-edit-row').prop('checked', isChecked);
                }
            };
        });
        </script>
        <?php
    }

    /**
     * Add custom columns to hotels list
     */
    public function add_featured_column( $columns ) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['hotel_image'] = 'Bild';
        $new_columns['title'] = $columns['title'];
        $new_columns['hotel_location'] = 'Standort';
        $new_columns['hotel_rating'] = 'Bewertung';
        $new_columns['hotel_rooms'] = 'Räume';
        $new_columns['hotel_capacity'] = 'Kapazität';
        $new_columns['featured_landing'] = 'Startseite';
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom column content
     */
    public function render_featured_column( $column, $post_id ) {
        switch ( $column ) {
            case 'hotel_image':
                $image_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
                if ( ! $image_url ) {
                    $medias_json = get_post_meta( $post_id, 'medias_json', true );
                    $medias = json_decode( $medias_json, true ) ?: [];
                    if ( ! empty( $medias[0]['previewUrl'] ) ) {
                        $image_url = $medias[0]['previewUrl'];
                    }
                }
                if ( $image_url ) {
                    echo '<img src="' . esc_url( $image_url ) . '" style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px;">';
                } else {
                    echo '<span style="display: inline-block; width: 60px; height: 45px; background: #f0f0f0; border-radius: 4px; text-align: center; line-height: 45px; color: #999;">—</span>';
                }
                break;

            case 'hotel_location':
                $city = get_post_meta( $post_id, 'business_city', true );
                $country = get_post_meta( $post_id, 'business_country', true );
                $location = array_filter( [ $city, $country ] );
                if ( ! empty( $location ) ) {
                    echo '<span style="color: #666;">' . esc_html( implode( ', ', $location ) ) . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'hotel_rating':
                $rating = get_post_meta( $post_id, 'rating', true );
                if ( $rating && floatval( $rating ) > 0 ) {
                    $rating_val = floatval( $rating );
                    $color = $rating_val >= 8 ? '#4caf50' : ( $rating_val >= 6 ? '#ff9800' : '#f44336' );
                    echo '<span style="display: inline-block; padding: 2px 8px; background: ' . $color . '; color: white; border-radius: 4px; font-weight: 600; font-size: 12px;">' . number_format( $rating_val, 1 ) . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'hotel_rooms':
                $rooms = get_post_meta( $post_id, 'rooms', true );
                if ( $rooms && intval( $rooms ) > 0 ) {
                    echo '<span style="font-weight: 500;">' . intval( $rooms ) . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'hotel_capacity':
                $capacity = get_post_meta( $post_id, 'capacity', true );
                if ( $capacity && intval( $capacity ) > 0 ) {
                    echo '<span style="font-weight: 500;">' . intval( $capacity ) . ' Pers.</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'featured_landing':
                $featured = get_post_meta( $post_id, 'featured_on_landing', true );
                if ( $featured === '1' ) {
                    echo '<span style="display: inline-block; padding: 3px 8px; background: #4caf50; color: white; border-radius: 4px; font-size: 11px; font-weight: 600;">⭐ Aktiv</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
        }
    }

    /**
     * Save featured meta field
     */
    public function save_featured_meta( $post_id ) {
        // Check nonce
        if ( ! isset( $_POST['hotel_featured_nonce_field'] ) || ! wp_verify_nonce( $_POST['hotel_featured_nonce_field'], 'hotel_featured_nonce' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save or delete the meta
        if ( isset( $_POST['featured_on_landing'] ) && $_POST['featured_on_landing'] === '1' ) {
            update_post_meta( $post_id, 'featured_on_landing', '1' );
        } else {
            delete_post_meta( $post_id, 'featured_on_landing' );
        }
    }

    /**
     * Add custom cron interval
     */
    public function add_cron_interval( $schedules ) {
        $schedules['every_six_hours'] = [
            'interval' => 21600,
            'display'  => __( 'Every 6 Hours', 'seminargo' ),
        ];
        return $schedules;
    }

    /**
     * Register hotel post type
     */
    public function register_post_type() {
        if ( ! post_type_exists( 'hotel' ) ) {
            register_post_type( 'hotel', [
                'labels' => [
                    'name'          => __( 'Hotels', 'seminargo' ),
                    'singular_name' => __( 'Hotel', 'seminargo' ),
                    'add_new'       => __( 'Add New', 'seminargo' ),
                    'add_new_item'  => __( 'Add New Hotel', 'seminargo' ),
                    'edit_item'     => __( 'Edit Hotel', 'seminargo' ),
                    'view_item'     => __( 'View Hotel', 'seminargo' ),
                    'all_items'     => __( 'All Hotels', 'seminargo' ),
                    'search_items'  => __( 'Search Hotels', 'seminargo' ),
                    'not_found'     => __( 'No hotels found', 'seminargo' ),
                ],
                'public'       => true,
                'has_archive'  => true,
                'show_in_rest' => true,
                'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
                'menu_icon'    => 'dashicons-building',
                'rewrite'      => [ 'slug' => 'hotel' ],
            ] );
        }

        // Register featured_on_landing meta for REST API / Block Editor
        register_post_meta( 'hotel', 'featured_on_landing', [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
    }

    /**
     * Register cron job
     */
    public function register_cron() {
        $auto_import_enabled = get_option( $this->auto_import_enabled_option, false );
        $is_scheduled = wp_next_scheduled( 'seminargo_hotels_cron' );

        if ( $auto_import_enabled && ! $is_scheduled ) {
            // Schedule to run every hour when auto-import is enabled
            wp_schedule_event( time(), 'hourly', 'seminargo_hotels_cron' );
        } elseif ( ! $auto_import_enabled && $is_scheduled ) {
            // Unschedule if auto-import is disabled
            wp_clear_scheduled_hook( 'seminargo_hotels_cron' );
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=hotel',
            __( 'Hotel Import', 'seminargo' ),
            __( 'Import / Sync', 'seminargo' ),
            'manage_options',
            'seminargo-hotel-import',
            [ $this, 'render_admin_page' ]
        );
    }

    /**
     * Add meta boxes to hotel edit page
     */
    public function add_hotel_meta_boxes() {
        add_meta_box(
            'hotel_basic_info',
            '📋 ' . __( 'Basic Information', 'seminargo' ),
            [ $this, 'render_basic_info_meta_box' ],
            'hotel',
            'normal',
            'high'
        );

        add_meta_box(
            'hotel_location',
            '📍 ' . __( 'Location & Address', 'seminargo' ),
            [ $this, 'render_location_meta_box' ],
            'hotel',
            'normal',
            'high'
        );

        add_meta_box(
            'hotel_texts',
            '📝 ' . __( 'Descriptions & Arrival Info', 'seminargo' ),
            [ $this, 'render_texts_meta_box' ],
            'hotel',
            'normal',
            'default'
        );

        add_meta_box(
            'hotel_amenities',
            '✨ ' . __( 'Amenities & Attributes', 'seminargo' ),
            [ $this, 'render_amenities_meta_box' ],
            'hotel',
            'normal',
            'default'
        );

        add_meta_box(
            'hotel_meeting_rooms',
            '🏢 ' . __( 'Meeting Rooms', 'seminargo' ),
            [ $this, 'render_meeting_rooms_meta_box' ],
            'hotel',
            'normal',
            'default'
        );

        add_meta_box(
            'hotel_media',
            '🖼️ ' . __( 'Media & Images', 'seminargo' ),
            [ $this, 'render_media_meta_box' ],
            'hotel',
            'side',
            'default'
        );

        add_meta_box(
            'hotel_api_info',
            '🔌 ' . __( 'API Information', 'seminargo' ),
            [ $this, 'render_api_info_meta_box' ],
            'hotel',
            'side',
            'default'
        );

        add_meta_box(
            'hotel_featured',
            '⭐ ' . __( 'Landingpage', 'seminargo' ),
            [ $this, 'render_featured_meta_box' ],
            'hotel',
            'side',
            'high'
        );
    }

    /**
     * Render Featured meta box
     */
    public function render_featured_meta_box( $post ) {
        $featured = get_post_meta( $post->ID, 'featured_on_landing', true );
        wp_nonce_field( 'hotel_featured_nonce', 'hotel_featured_nonce_field' );
        ?>
        <style>
            .featured-toggle {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                background: <?php echo $featured === '1' ? '#e8f5e9' : '#f5f5f5'; ?>;
                border-radius: 8px;
                border: 2px solid <?php echo $featured === '1' ? '#4caf50' : '#ddd'; ?>;
            }
            .featured-toggle input[type="checkbox"] {
                width: 20px;
                height: 20px;
                cursor: pointer;
            }
            .featured-toggle .toggle-label {
                font-weight: 600;
                color: <?php echo $featured === '1' ? '#2e7d32' : '#666'; ?>;
            }
            .featured-toggle .toggle-status {
                margin-left: auto;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                background: <?php echo $featured === '1' ? '#4caf50' : '#999'; ?>;
                color: white;
            }
        </style>
        <div class="featured-toggle">
            <input type="checkbox" name="featured_on_landing" id="featured_on_landing" value="1" <?php checked( $featured, '1' ); ?>>
            <label for="featured_on_landing" class="toggle-label"><?php esc_html_e( 'Auf Startseite anzeigen', 'seminargo' ); ?></label>
            <span class="toggle-status"><?php echo $featured === '1' ? esc_html__( 'Aktiv', 'seminargo' ) : esc_html__( 'Inaktiv', 'seminargo' ); ?></span>
        </div>
        <p class="description" style="margin-top: 10px; color: #666;">
            <?php esc_html_e( 'Zeigt dieses Hotel im Bereich "Top-Veranstaltungsorte" auf der Startseite.', 'seminargo' ); ?>
        </p>
        <?php
    }

    /**
     * Render Basic Info meta box
     */
    public function render_basic_info_meta_box( $post ) {
        $fields = [
            'hotel_id'             => __( 'Hotel ID', 'seminargo' ),
            'ref_code'             => __( 'Reference Code', 'seminargo' ),
            'hotel_name'           => __( 'Hotel Name', 'seminargo' ),
            'business_name'        => __( 'Company Name (Firmenname)', 'seminargo' ),
            'rating'               => __( 'Rating', 'seminargo' ),
            'stars'                => __( 'Stars', 'seminargo' ),
            'capacity'             => __( 'Max Capacity (People)', 'seminargo' ),
            'rooms'                => __( 'Meeting Rooms Count', 'seminargo' ),
            'max_capacity_rooms'   => __( 'Max Capacity Rooms (API)', 'seminargo' ),
            'max_capacity_people'  => __( 'Max Capacity People (API)', 'seminargo' ),
        ];

        echo '<table class="form-table"><tbody>';
        foreach ( $fields as $key => $label ) {
            $value = get_post_meta( $post->ID, $key, true );
            echo '<tr>';
            echo '<th scope="row"><label>' . esc_html( $label ) . '</label></th>';
            echo '<td><input type="text" class="regular-text" value="' . esc_attr( $value ) . '" readonly /></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Show stars visually
        $stars = get_post_meta( $post->ID, 'stars', true );
        if ( $stars ) {
            echo '<p><strong>' . esc_html__( 'Star Rating:', 'seminargo' ) . '</strong> ';
            $full_stars = floor( $stars );
            $half_star = ( $stars - $full_stars ) >= 0.5;
            echo str_repeat( '⭐', $full_stars );
            if ( $half_star ) {
                echo '½';
            }
            echo ' (' . esc_html( $stars ) . ')</p>';
        }
    }

    /**
     * Render Location meta box
     */
    public function render_location_meta_box( $post ) {
        $fields = [
            'business_address_1'                   => __( 'Address Line 1', 'seminargo' ),
            'business_address_2'                   => __( 'Address Line 2', 'seminargo' ),
            'business_address_3'                   => __( 'Address Line 3', 'seminargo' ),
            'business_address_4'                   => __( 'Address Line 4', 'seminargo' ),
            'business_zip'                         => __( 'ZIP Code', 'seminargo' ),
            'business_city'                        => __( 'City', 'seminargo' ),
            'business_country'                     => __( 'Country', 'seminargo' ),
            'business_email'                       => __( 'Email', 'seminargo' ),
            'full_address'                         => __( 'Full Address', 'seminargo' ),
            'location_latitude'                    => __( 'Latitude', 'seminargo' ),
            'location_longitude'                   => __( 'Longitude', 'seminargo' ),
            'distance_to_nearest_airport'          => __( 'Distance to Airport (km)', 'seminargo' ),
            'distance_to_nearest_railroad_station' => __( 'Distance to Train Station (km)', 'seminargo' ),
        ];

        echo '<table class="form-table"><tbody>';
        foreach ( $fields as $key => $label ) {
            $value = get_post_meta( $post->ID, $key, true );
            if ( $key === 'distance_to_nearest_airport' || $key === 'distance_to_nearest_railroad_station' ) {
                $value = $value ? round( $value, 2 ) . ' km' : '';
            }
            echo '<tr>';
            echo '<th scope="row"><label>' . esc_html( $label ) . '</label></th>';
            echo '<td><input type="text" class="regular-text" value="' . esc_attr( $value ) . '" readonly /></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Show map link
        $lat = get_post_meta( $post->ID, 'location_latitude', true );
        $lng = get_post_meta( $post->ID, 'location_longitude', true );
        if ( $lat && $lng ) {
            echo '<p><a href="https://www.google.com/maps?q=' . esc_attr( $lat ) . ',' . esc_attr( $lng ) . '" target="_blank" class="button">📍 ' . esc_html__( 'View on Google Maps', 'seminargo' ) . '</a></p>';
        }
    }

    /**
     * Render Texts meta box
     */
    public function render_texts_meta_box( $post ) {
        $fields = [
            'description'    => __( 'Hotel Description', 'seminargo' ),
            'arrival_car'    => __( 'Arrival by Car', 'seminargo' ),
            'arrival_flight' => __( 'Arrival by Flight', 'seminargo' ),
            'arrival_train'  => __( 'Arrival by Train', 'seminargo' ),
        ];

        foreach ( $fields as $key => $label ) {
            $value = get_post_meta( $post->ID, $key, true );
            echo '<div style="margin-bottom: 20px;">';
            echo '<h4 style="margin-bottom: 5px;">' . esc_html( $label ) . '</h4>';
            if ( $value ) {
                echo '<div style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto;">';
                echo wp_kses_post( nl2br( $value ) );
                echo '</div>';
            } else {
                echo '<p style="color: #999; font-style: italic;">' . esc_html__( 'Not available', 'seminargo' ) . '</p>';
            }
            echo '</div>';
        }
    }

    /**
     * Render Amenities meta box
     */
    public function render_amenities_meta_box( $post ) {
        $amenities_list = get_post_meta( $post->ID, 'amenities_list', true );
        $amenities = json_decode( $amenities_list, true ) ?: [];

        $labels = [
            'room'     => '🛏️ ' . __( 'Room Features', 'seminargo' ),
            'design'   => '🎨 ' . __( 'Design Style', 'seminargo' ),
            'activity' => '🏃 ' . __( 'Activities', 'seminargo' ),
            'wellness' => '💆 ' . __( 'Wellness', 'seminargo' ),
            'facility' => '🏨 ' . __( 'Hotel Facilities', 'seminargo' ),
            'ecolabel' => '🌿 ' . __( 'Eco Labels', 'seminargo' ),
        ];

        $translations = [
            'ROOM_SAFE'                            => __( 'Safe', 'seminargo' ),
            'ROOM_AIRCONDITIONER'                  => __( 'Air Conditioning', 'seminargo' ),
            'ROOM_BARRIER_FREE'                    => __( 'Barrier Free', 'seminargo' ),
            'ROOM_MINIBAR'                         => __( 'Minibar', 'seminargo' ),
            'ROOM_BALCONY'                         => __( 'Balcony', 'seminargo' ),
            'DESIGN_BUSINESS'                      => __( 'Business', 'seminargo' ),
            'DESIGN_RURAL'                         => __( 'Rural', 'seminargo' ),
            'DESIGN_MODERN'                        => __( 'Modern', 'seminargo' ),
            'DESIGN_TRADITIONAL'                   => __( 'Traditional', 'seminargo' ),
            'ACTIVITY_GOLF'                        => __( 'Golf', 'seminargo' ),
            'ACTIVITY_BIKE_RENTAL'                 => __( 'Bike Rental', 'seminargo' ),
            'ACTIVITY_FITNESS'                     => __( 'Fitness', 'seminargo' ),
            'ACTIVITY_TENNIS'                      => __( 'Tennis', 'seminargo' ),
            'ACTIVITY_SKI_SLOPE'                   => __( 'Ski Slope', 'seminargo' ),
            'WELLNESS_OUTDOOR_POOL'                => __( 'Outdoor Pool', 'seminargo' ),
            'WELLNESS_INDOOR_POOL'                 => __( 'Indoor Pool', 'seminargo' ),
            'WELLNESS_MASSAGE'                     => __( 'Massage', 'seminargo' ),
            'WELLNESS_SAUNA'                       => __( 'Sauna', 'seminargo' ),
            'WELLNESS_WHIRLPOOL'                   => __( 'Whirlpool', 'seminargo' ),
            'HOTELFACILITY_BARRIER_FREE'           => __( 'Barrier Free Access', 'seminargo' ),
            'HOTELFACILITY_ELECTRIC_CHARGING_STATION' => __( 'EV Charging', 'seminargo' ),
            'HOTELFACILITY_GREEN_AREA'             => __( 'Green Area', 'seminargo' ),
            'ECOLABEL_AUSTRIAN_ECOLABEL'           => __( 'Austrian Ecolabel', 'seminargo' ),
            'ECOLABEL_EU_ECOLABEL'                 => __( 'EU Ecolabel', 'seminargo' ),
            'ECOLABEL_GREEN_KEY'                   => __( 'Green Key', 'seminargo' ),
        ];

        foreach ( $labels as $key => $label ) {
            if ( ! empty( $amenities[ $key ] ) ) {
                echo '<div style="margin-bottom: 15px;">';
                echo '<strong>' . esc_html( $label ) . ':</strong><br>';
                echo '<div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">';
                foreach ( $amenities[ $key ] as $attr ) {
                    $display = $translations[ $attr ] ?? str_replace( [ 'ROOM_', 'DESIGN_', 'ACTIVITY_', 'WELLNESS_', 'HOTELFACILITY_', 'ECOLABEL_' ], '', $attr );
                    echo '<span style="background: #e7f3ff; padding: 3px 8px; border-radius: 3px; font-size: 12px;">' . esc_html( $display ) . '</span>';
                }
                echo '</div></div>';
            }
        }

        if ( empty( array_filter( $amenities ) ) ) {
            echo '<p style="color: #999;">' . esc_html__( 'No amenities data available', 'seminargo' ) . '</p>';
        }
    }

    /**
     * Render Meeting Rooms meta box
     */
    public function render_meeting_rooms_meta_box( $post ) {
        $meeting_rooms = get_post_meta( $post->ID, 'meeting_rooms', true );
        $rooms = json_decode( $meeting_rooms, true ) ?: [];

        if ( empty( $rooms ) ) {
            echo '<p style="color: #999;">' . esc_html__( 'No meeting rooms data available', 'seminargo' ) . '</p>';
            return;
        }

        echo '<p><strong>' . esc_html__( 'Total Meeting Rooms:', 'seminargo' ) . '</strong> ' . count( $rooms ) . '</p>';

        echo '<table class="widefat striped" style="margin-top: 10px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Name', 'seminargo' ) . '</th>';
        echo '<th>' . esc_html__( 'Area (m²)', 'seminargo' ) . '</th>';
        echo '<th>' . esc_html__( 'Theater', 'seminargo' ) . '</th>';
        echo '<th>' . esc_html__( 'Bankett', 'seminargo' ) . '</th>';
        echo '<th>' . esc_html__( 'U-Form', 'seminargo' ) . '</th>';
        echo '<th>' . esc_html__( 'Parliament', 'seminargo' ) . '</th>';
        echo '<th>' . esc_html__( 'Block', 'seminargo' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rooms as $room ) {
            echo '<tr>';
            echo '<td><strong>' . esc_html( $room['name'] ?? '' ) . '</strong></td>';
            echo '<td>' . esc_html( $room['area'] ?? '-' ) . '</td>';
            echo '<td>' . esc_html( $room['capacityTheater'] ?? '-' ) . '</td>';
            echo '<td>' . esc_html( $room['capacityBankett'] ?? '-' ) . '</td>';
            echo '<td>' . esc_html( $room['capacityUForm'] ?? '-' ) . '</td>';
            echo '<td>' . esc_html( $room['capacityParlament'] ?? '-' ) . '</td>';
            echo '<td>' . esc_html( $room['capacityBlock'] ?? '-' ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Render Media meta box
     */
    public function render_media_meta_box( $post ) {
        $medias_json = get_post_meta( $post->ID, 'medias_json', true );
        $medias = json_decode( $medias_json, true ) ?: [];

        // Get WordPress gallery attachments
        $gallery_ids = get_post_meta( $post->ID, 'gallery', true );
        if ( ! is_array( $gallery_ids ) ) {
            $gallery_ids = [];
        }

        // Also get all attachments directly attached to this post
        $all_attachments = get_attached_media( 'image', $post->ID );

        echo '<style>
            .hotel-media-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd; }
            .hotel-media-section:last-child { border-bottom: none; }
            .hotel-media-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
            .hotel-media-thumb { position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 4px; border: 2px solid #ddd; }
            .hotel-media-thumb.featured { border-color: #2271b1; }
            .hotel-media-thumb img { width: 100%; height: 100%; object-fit: cover; }
            .hotel-media-badge { position: absolute; top: 2px; right: 2px; background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
            .hotel-media-count { background: #f0f0f1; padding: 8px; border-radius: 4px; font-size: 13px; margin-top: 10px; }
        </style>';

        // Featured Image
        echo '<div class="hotel-media-section">';
        echo '<p><strong>📸 ' . esc_html__( 'Featured Image', 'seminargo' ) . '</strong></p>';
        if ( has_post_thumbnail( $post->ID ) ) {
            echo get_the_post_thumbnail( $post->ID, 'medium', [ 'style' => 'max-width: 100%; height: auto; border-radius: 4px;' ] );
            $featured_id = get_post_thumbnail_id( $post->ID );
            $edit_url = admin_url( 'post.php?post=' . $featured_id . '&action=edit' );
            echo '<p style="margin-top: 5px; font-size: 11px;"><a href="' . esc_url( $edit_url ) . '" target="_blank">✏️ ' . esc_html__( 'Edit in Media Library', 'seminargo' ) . '</a></p>';
        } else {
            echo '<p style="color: #999; font-style: italic;">' . esc_html__( 'No featured image set', 'seminargo' ) . '</p>';
        }
        echo '</div>';

        // Gallery Images (from gallery meta)
        echo '<div class="hotel-media-section">';
        echo '<p><strong>🖼️ ' . esc_html__( 'Gallery Images', 'seminargo' ) . ' (' . count( $gallery_ids ) . ')</strong></p>';
        if ( ! empty( $gallery_ids ) ) {
            echo '<div class="hotel-media-gallery">';
            $featured_id = get_post_thumbnail_id( $post->ID );
            foreach ( array_slice( $gallery_ids, 0, 12 ) as $attachment_id ) {
                $img = wp_get_attachment_image( $attachment_id, 'thumbnail', false, [ 'loading' => 'lazy' ] );
                if ( $img ) {
                    $edit_url = admin_url( 'post.php?post=' . $attachment_id . '&action=edit' );
                    $is_featured = ( $attachment_id == $featured_id );
                    echo '<a href="' . esc_url( $edit_url ) . '" target="_blank" class="hotel-media-thumb' . ( $is_featured ? ' featured' : '' ) . '" title="' . esc_attr__( 'Click to edit', 'seminargo' ) . '">';
                    echo $img;
                    if ( $is_featured ) {
                        echo '<span class="hotel-media-badge">★</span>';
                    }
                    echo '</a>';
                }
            }
            echo '</div>';
            if ( count( $gallery_ids ) > 12 ) {
                echo '<p style="margin-top: 8px; font-size: 11px; color: #666;">' . sprintf( esc_html__( '... and %d more images', 'seminargo' ), count( $gallery_ids ) - 12 ) . '</p>';
            }
            echo '<p style="margin-top: 10px; font-size: 11px;"><a href="' . esc_url( admin_url( 'upload.php?post_parent=' . $post->ID ) ) . '" target="_blank">📁 ' . esc_html__( 'View all in Media Library', 'seminargo' ) . '</a></p>';
        } else {
            echo '<p style="color: #999; font-style: italic;">' . esc_html__( 'No gallery images downloaded yet', 'seminargo' ) . '</p>';
            echo '<p style="font-size: 11px; color: #666;">' . esc_html__( 'Images will be downloaded automatically when importing from API', 'seminargo' ) . '</p>';
        }
        echo '</div>';

        // All Attached Images (including those not in gallery)
        if ( ! empty( $all_attachments ) ) {
            $non_gallery = array_filter( $all_attachments, function( $att ) use ( $gallery_ids ) {
                return ! in_array( $att->ID, $gallery_ids );
            } );
            if ( ! empty( $non_gallery ) ) {
                echo '<div class="hotel-media-section">';
                echo '<p><strong>📎 ' . esc_html__( 'Other Attached Images', 'seminargo' ) . ' (' . count( $non_gallery ) . ')</strong></p>';
                echo '<div class="hotel-media-gallery">';
                foreach ( array_slice( $non_gallery, 0, 6 ) as $attachment ) {
                    $img = wp_get_attachment_image( $attachment->ID, 'thumbnail', false, [ 'loading' => 'lazy' ] );
                    if ( $img ) {
                        $edit_url = admin_url( 'post.php?post=' . $attachment->ID . '&action=edit' );
                        echo '<a href="' . esc_url( $edit_url ) . '" target="_blank" class="hotel-media-thumb" title="' . esc_attr__( 'Click to edit', 'seminargo' ) . '">';
                        echo $img;
                        echo '</a>';
                    }
                }
                echo '</div>';
                echo '</div>';
            }
        }

        // API Media Info (for reference)
        echo '<div class="hotel-media-section">';
        echo '<p><strong>ℹ️ ' . esc_html__( 'API Media Info', 'seminargo' ) . '</strong></p>';
        echo '<div class="hotel-media-count">';
        echo '📊 ' . sprintf( esc_html__( '%d images from API', 'seminargo' ), count( $medias ) ) . '<br>';
        echo '💾 ' . sprintf( esc_html__( '%d images in WordPress', 'seminargo' ), count( $gallery_ids ) ) . '<br>';
        echo '📁 ' . sprintf( esc_html__( '%d total attachments', 'seminargo' ), count( $all_attachments ) );
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render API Info meta box
     */
    public function render_api_info_meta_box( $post ) {
        $fields = [
            'hotel_id'                    => __( 'API Hotel ID', 'seminargo' ),
            'ref_code'                    => __( 'Reference Code', 'seminargo' ),
            'api_slug'                    => __( 'API Slug', 'seminargo' ),
            'shop_url'                    => __( 'Shop URL', 'seminargo' ),
            'space_id'                    => __( 'Space ID', 'seminargo' ),
            'space_name'                  => __( 'Space Name', 'seminargo' ),
            'has_active_partner_contract' => __( 'Partner Contract', 'seminargo' ),
            'direct_booking'              => __( 'Direct Booking', 'seminargo' ),
        ];

        echo '<table style="width: 100%;">';
        foreach ( $fields as $key => $label ) {
            $value = get_post_meta( $post->ID, $key, true );
            if ( $key === 'has_active_partner_contract' || $key === 'direct_booking' ) {
                $value = $value ? '✅ ' . __( 'Yes', 'seminargo' ) : '❌ ' . __( 'No', 'seminargo' );
            }
            echo '<tr>';
            echo '<td style="padding: 5px 0;"><strong>' . esc_html( $label ) . ':</strong></td>';
            echo '</tr><tr>';
            echo '<td style="padding: 0 0 10px 0; word-break: break-all; font-size: 12px;">' . esc_html( $value ?: '-' ) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Links to finder
        $finder_url_slug = get_post_meta( $post->ID, 'finder_url_slug', true );
        $finder_add_slug = get_post_meta( $post->ID, 'finder_add_slug', true );

        echo '<p style="margin-top: 10px;">';
        if ( $finder_url_slug ) {
            echo '<a href="' . esc_url( $finder_url_slug ) . '" target="_blank" class="button button-small" style="margin-right: 5px;">🔍 ' . esc_html__( 'View in Finder', 'seminargo' ) . '</a>';
        }
        if ( $finder_add_slug ) {
            echo '<a href="' . esc_url( $finder_add_slug ) . '" target="_blank" class="button button-primary button-small">➕ ' . esc_html__( 'Add to Selection', 'seminargo' ) . '</a>';
        }
        echo '</p>';
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $last_import = get_option( $this->last_import_option, [] );
        $next_scheduled = wp_next_scheduled( 'seminargo_hotels_cron' );
        ?>
        <div class="wrap">
            <h1>🏨 <?php esc_html_e( 'Hotel Import / Sync', 'seminargo' ); ?></h1>

            <div class="seminargo-import-dashboard" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">

                <!-- Status Card -->
                <div class="card" style="padding: 20px;">
                    <h2>📊 <?php esc_html_e( 'Import Status', 'seminargo' ); ?></h2>
                    <table class="widefat" style="margin-top: 15px;">
                        <tr>
                            <td><strong><?php esc_html_e( 'Last Import:', 'seminargo' ); ?></strong></td>
                            <td><?php echo ! empty( $last_import['time'] ) ? esc_html( date_i18n( 'Y-m-d H:i:s', $last_import['time'] ) ) : esc_html__( 'Never', 'seminargo' ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Next Scheduled:', 'seminargo' ); ?></strong></td>
                            <td><?php echo $next_scheduled ? esc_html( date_i18n( 'Y-m-d H:i:s', $next_scheduled ) ) : esc_html__( 'Not scheduled', 'seminargo' ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Hotels Created:', 'seminargo' ); ?></strong></td>
                            <td id="stat-created"><?php echo esc_html( $last_import['created'] ?? 0 ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Hotels Updated:', 'seminargo' ); ?></strong></td>
                            <td id="stat-updated"><?php echo esc_html( $last_import['updated'] ?? 0 ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Hotels Drafted (Removed):', 'seminargo' ); ?></strong></td>
                            <td id="stat-drafted"><?php echo esc_html( $last_import['drafted'] ?? 0 ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Errors:', 'seminargo' ); ?></strong></td>
                            <td id="stat-errors"><?php echo esc_html( $last_import['errors'] ?? 0 ); ?></td>
                        </tr>
                    </table>

                    <div style="margin-top: 20px;">
                        <button id="btn-fetch-now" class="button button-primary button-hero">
                            🔄 <?php esc_html_e( 'Fetch Now', 'seminargo' ); ?>
                        </button>
                        <button id="btn-skip-to-phase2" class="button" style="margin-left: 10px; background: #f59e0b; color: white; border-color: #f59e0b;" title="Debug: Skip Phase 1 and start directly with Phase 2 (image downloads)">
                            📸 <?php esc_html_e( 'Skip to Phase 2 (Debug)', 'seminargo' ); ?>
                        </button>
                        <button id="btn-clear-logs" class="button" style="margin-left: 10px;">
                            🗑️ <?php esc_html_e( 'Clear Logs', 'seminargo' ); ?>
                        </button>
                        <button id="btn-delete-all-hotels" class="button" style="margin-left: 10px; background: #dc3232; color: white; border-color: #dc3232;">
                            💣 <?php esc_html_e( 'Delete All Hotels', 'seminargo' ); ?>
                        </button>
                    </div>

                    <div id="import-progress" style="display: none; margin-top: 20px;">
                        <!-- Current Phase -->
                        <div style="background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 15px 0; font-size: 16px;">
                                <span id="phase-icon">🚀</span>
                                <span id="phase-name">Starting Import...</span>
                            </h3>

                            <!-- Overall Progress Bar -->
                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <strong>Overall Progress</strong>
                                    <span id="overall-percent">0%</span>
                                </div>
                                <div style="background: #f0f0f0; border-radius: 4px; padding: 3px; height: 30px;">
                                    <div id="progress-bar" style="background: linear-gradient(90deg, #AC2A6E, #d64a94); height: 24px; border-radius: 3px; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;"></div>
                                </div>
                            </div>

                            <!-- Current Action -->
                            <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                                <div style="font-size: 13px; color: #666;">
                                    <strong>Current Action:</strong> <span id="current-action">Initializing...</span>
                                </div>
                            </div>

                            <!-- Phase Details -->
                            <div id="phase-details" style="margin-top: 15px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                                <div style="padding: 10px; background: #f0f9ff; border-radius: 4px; border-left: 3px solid #2271b1;">
                                    <div style="font-size: 11px; color: #666; text-transform: uppercase;">Hotels Processed</div>
                                    <div style="font-size: 20px; font-weight: bold; color: #2271b1;"><span id="hotels-processed">0</span> / <span id="hotels-total">0</span></div>
                                </div>
                                <div style="padding: 10px; background: #f0fdf4; border-radius: 4px; border-left: 3px solid #10b981;">
                                    <div style="font-size: 11px; color: #666; text-transform: uppercase;">Created</div>
                                    <div style="font-size: 20px; font-weight: bold; color: #10b981;" id="live-created">0</div>
                                </div>
                                <div style="padding: 10px; background: #fffbeb; border-radius: 4px; border-left: 3px solid #f59e0b;">
                                    <div style="font-size: 11px; color: #666; text-transform: uppercase;">Updated</div>
                                    <div style="font-size: 20px; font-weight: bold; color: #f59e0b;" id="live-updated">0</div>
                                </div>
                                <div style="padding: 10px; background: #fef2f2; border-radius: 4px; border-left: 3px solid #ef4444;">
                                    <div style="font-size: 11px; color: #666; text-transform: uppercase;">Images</div>
                                    <div style="font-size: 20px; font-weight: bold; color: #ef4444;"><span id="images-processed">0</span></div>
                                </div>
                            </div>

                            <!-- Time Estimate -->
                            <div style="margin-top: 15px; padding: 10px; background: #fffbeb; border-radius: 4px; border-left: 3px solid #f59e0b;">
                                <div style="font-size: 12px;">
                                    <strong>⏱️ Elapsed:</strong> <span id="time-elapsed">0s</span> |
                                    <strong>Estimated Remaining:</strong> <span id="time-remaining">Calculating...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Info Card -->
                <div class="card" style="padding: 20px;">
                    <h2>🔌 <?php esc_html_e( 'API Configuration', 'seminargo' ); ?></h2>
                    <table class="widefat" style="margin-top: 15px;">
                        <tr>
                            <td><strong><?php esc_html_e( 'API Endpoint:', 'seminargo' ); ?></strong></td>
                            <td><code style="font-size: 11px;"><?php echo esc_html( $this->api_url ); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Finder Base URL:', 'seminargo' ); ?></strong></td>
                            <td><code style="font-size: 11px;"><?php echo esc_html( $this->finder_base_url ); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Cron Schedule:', 'seminargo' ); ?></strong></td>
                            <td><?php esc_html_e( 'Every 12 hours (twicedaily)', 'seminargo' ); ?></td>
                        </tr>
                    </table>

                    <h3 style="margin-top: 20px;">ℹ️ <?php esc_html_e( '"Fetch Now" - How it works', 'seminargo' ); ?></h3>
                    <ul style="list-style: disc; margin-left: 20px; color: #666; font-size: 13px;">
                        <li><strong>Batched Processing:</strong> Processes 200 hotels at a time in background</li>
                        <li><strong>No Timeouts:</strong> Each batch completes in < 60s, works on WP Engine!</li>
                        <li><strong>Live Progress:</strong> UI updates every 2 seconds with real-time stats</li>
                        <li><strong>Phase 1:</strong> Creates/updates all hotel posts (~10 min)</li>
                        <li><strong>Phase 2:</strong> Downloads images for all hotels (~20-30 min)</li>
                        <li><strong>Total Time:</strong> ~30-40 minutes for full import with 4815 hotels</li>
                    </ul>
                    <div style="margin-top: 15px; padding: 10px; background: #f0fdf4; border-left: 3px solid #10b981; border-radius: 3px;">
                        <strong style="color: #059669;">✅ Production Ready:</strong>
                        <span style="color: #059669; font-size: 12px;">Works on WP Engine, shared hosting, and all environments!</span>
                    </div>
                </div>

                <!-- Auto-Import Card -->
                <div class="card" style="padding: 20px;">
                    <h2>🤖 <?php esc_html_e( 'Automatic Import (Ongoing Sync)', 'seminargo' ); ?></h2>
                    <p style="color: #666; font-size: 13px; margin-top: 10px;">
                        <?php esc_html_e( 'Automatically syncs hotel data in batches (500 at a time) every hour. Checks for changes and updates hotels WITHOUT re-downloading images. Perfect for daily sync!', 'seminargo' ); ?>
                    </p>

                    <div id="auto-import-status" style="margin-top: 15px;">
                        <p><?php esc_html_e( 'Loading status...', 'seminargo' ); ?></p>
                    </div>

                    <div style="margin-top: 15px;">
                        <button id="btn-toggle-auto-import" class="button button-primary">
                            🔄 <?php esc_html_e( 'Enable Auto-Import', 'seminargo' ); ?>
                        </button>
                        <button id="btn-reset-progress" class="button" style="margin-left: 10px;">
                            ↻ <?php esc_html_e( 'Reset Progress', 'seminargo' ); ?>
                        </button>
                    </div>

                    <div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 3px solid #2271b1; border-radius: 3px;">
                        <strong>💡 How Auto-Import Works:</strong>
                        <ul style="margin: 10px 0 0 20px; font-size: 12px;">
                            <li>Processes 500 hotels per hour (fast - no timeouts)</li>
                            <li><strong>Syncs hotel data</strong> (name, address, rooms, etc.)</li>
                            <li><strong>Skips images</strong> if already downloaded (fast updates)</li>
                            <li>New hotels get images via manual "Fetch Now" or WP-CLI</li>
                            <li>Perfect for ongoing daily/hourly sync on WP Engine</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Logs Section -->
            <div class="card" style="padding: 20px; margin-top: 20px;">
                <h2>📋 <?php esc_html_e( 'Import Logs', 'seminargo' ); ?></h2>
                <div style="margin-bottom: 15px;">
                    <label>
                        <input type="checkbox" id="filter-errors" /> <?php esc_html_e( 'Show only errors', 'seminargo' ); ?>
                    </label>
                    <label style="margin-left: 15px;">
                        <input type="checkbox" id="filter-updates" /> <?php esc_html_e( 'Show only updates', 'seminargo' ); ?>
                    </label>
                </div>
                <div id="logs-container" style="max-height: 500px; overflow-y: auto; background: #1d2327; color: #fff; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    <p style="color: #72aee6;"><?php esc_html_e( 'Loading logs...', 'seminargo' ); ?></p>
                </div>
            </div>
        </div>

        <style>
            .log-entry { padding: 5px 0; border-bottom: 1px solid #333; }
            .log-entry.error { color: #ff6b6b; }
            .log-entry.success { color: #51cf66; }
            .log-entry.update { color: #ffd43b; }
            .log-entry.info { color: #72aee6; }
            .log-entry.draft { color: #ff922b; }
            .log-time { color: #868e96; margin-right: 10px; }
            .log-hotel { color: #da77f2; font-weight: bold; }
            .log-field { color: #69db7c; }
            .log-old { color: #ff8787; text-decoration: line-through; }
            .log-new { color: #8ce99a; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Debug: Verify button exists and attach handler
            var $skipBtn = $('#btn-skip-to-phase2');
            if ($skipBtn.length === 0) {
                console.error('Skip to Phase 2 button not found!');
            } else {
                console.log('Skip to Phase 2 button found, ID:', $skipBtn.attr('id'));
            }

            function loadLogs() {
                $.post(ajaxurl, { action: 'seminargo_get_logs' }, function(response) {
                    if (response.success) {
                        renderLogs(response.data);
                    }
                });
            }

            function renderLogs(logs) {
                var container = $('#logs-container');
                container.empty();

                if (!logs || logs.length === 0) {
                    container.html('<p style="color: #868e96;"><?php echo esc_js( __( 'No logs yet. Click "Fetch Now" to start an import.', 'seminargo' ) ); ?></p>');
                    return;
                }

                var showErrors = $('#filter-errors').is(':checked');
                var showUpdates = $('#filter-updates').is(':checked');

                logs.forEach(function(log) {
                    if (showErrors && log.type !== 'error') return;
                    if (showUpdates && log.type !== 'update') return;

                    var entry = $('<div class="log-entry ' + log.type + '"></div>');
                    var time = '<span class="log-time">[' + log.time + ']</span>';
                    var message = log.message;

                    if (log.hotel) {
                        message = message.replace(log.hotel, '<span class="log-hotel">' + log.hotel + '</span>');
                    }
                    if (log.field) {
                        message += ' <span class="log-field">' + log.field + '</span>:';
                        if (log.old_value !== undefined) {
                            message += ' <span class="log-old">' + truncate(log.old_value) + '</span> →';
                        }
                        if (log.new_value !== undefined) {
                            message += ' <span class="log-new">' + truncate(log.new_value) + '</span>';
                        }
                    }

                    entry.html(time + message);
                    container.append(entry);
                });
            }

            function truncate(str) {
                str = String(str);
                return str.length > 50 ? str.substring(0, 50) + '...' : str;
            }

            // Progress tracking variables
            var importStartTime = null;
            var lastProcessedCount = 0;
            var totalHotels = 0;

            function updateProgressUI(logs) {
                if (!logs || logs.length === 0) return;

                // Get the latest log entry
                var latestLog = logs[logs.length - 1];
                var message = latestLog.message;

                // Update elapsed time
                if (importStartTime) {
                    var elapsed = Math.floor((Date.now() - importStartTime) / 1000);
                    var mins = Math.floor(elapsed / 60);
                    var secs = elapsed % 60;
                    $('#time-elapsed').text(mins > 0 ? mins + 'm ' + secs + 's' : secs + 's');
                }

                // Parse different message types and update UI
                if (message.includes('FETCH COMPLETE! Total hotels:')) {
                    var match = message.match(/Total hotels: (\d+)/);
                    if (match) {
                        totalHotels = parseInt(match[1]);
                        $('#hotels-total').text(totalHotels);
                        $('#phase-icon').text('📦');
                        $('#phase-name').text('Fetch Complete - ' + totalHotels + ' hotels ready');
                        $('#current-action').text('All hotels fetched from API');
                        $('#progress-bar').css('width', '10%').text('10%');
                        $('#overall-percent').text('10%');
                    }
                }
                else if (message.includes('PHASE 1:')) {
                    $('#phase-icon').text('🏨');
                    $('#phase-name').text('Phase 1: Creating Hotel Posts (Fast)');
                    $('#current-action').text('Creating/updating hotel posts without images...');
                    $('#progress-bar').css('width', '15%').text('15%');
                    $('#overall-percent').text('15%');
                }
                else if (message.includes('Phase 1 Progress:')) {
                    var match = message.match(/(\d+)\/(\d+) hotels \((\d+)%\).*Created: (\d+), Updated: (\d+)/);
                    if (match) {
                        var processed = parseInt(match[1]);
                        var total = parseInt(match[2]);
                        var percent = parseInt(match[3]);
                        var created = parseInt(match[4]);
                        var updated = parseInt(match[5]);

                        $('#hotels-processed').text(processed);
                        $('#hotels-total').text(total);
                        $('#live-created').text(created);
                        $('#live-updated').text(updated);

                        // Progress is 10-50% for Phase 1
                        var overallPercent = 10 + (percent * 0.4);
                        $('#progress-bar').css('width', overallPercent + '%').text(Math.round(overallPercent) + '%');
                        $('#overall-percent').text(Math.round(overallPercent) + '%');
                        $('#current-action').text('Processing hotel ' + processed + ' of ' + total + '...');

                        // Calculate time remaining for Phase 1
                        if (importStartTime && processed > lastProcessedCount) {
                            var elapsed = (Date.now() - importStartTime) / 1000;
                            var rate = processed / elapsed; // hotels per second
                            var remaining = total - processed;
                            var eta = remaining / rate;
                            var etaMins = Math.floor(eta / 60);
                            var etaSecs = Math.floor(eta % 60);
                            $('#time-remaining').text(etaMins > 0 ? etaMins + 'm ' + etaSecs + 's' : etaSecs + 's');
                        }

                        lastProcessedCount = processed;
                    }
                }
                else if (message.includes('PHASE 1 COMPLETE')) {
                    $('#phase-icon').text('✅');
                    $('#phase-name').text('Phase 1 Complete!');
                    $('#current-action').text('All hotels created successfully. Starting image downloads...');
                    $('#progress-bar').css('width', '50%').text('50%');
                    $('#overall-percent').text('50%');
                }
                else if (message.includes('PHASE 2:')) {
                    $('#phase-icon').text('📸');
                    $('#phase-name').text('Phase 2: Downloading Images (Slow)');
                    $('#current-action').text('Downloading and processing hotel images...');
                    $('#progress-bar').css('width', '55%').text('55%');
                    $('#overall-percent').text('55%');
                }
                else if (message.includes('Phase 2 Progress:')) {
                    var match = message.match(/(\d+)\/(\d+) hotels \((\d+)%\)/);
                    if (match) {
                        var processed = parseInt(match[1]);
                        var total = parseInt(match[2]);
                        var percent = parseInt(match[3]);

                        $('#images-processed').text(processed);

                        // Progress is 50-95% for Phase 2
                        var overallPercent = 50 + (percent * 0.45);
                        $('#progress-bar').css('width', overallPercent + '%').text(Math.round(overallPercent) + '%');
                        $('#overall-percent').text(Math.round(overallPercent) + '%');
                        $('#current-action').text('Downloading images for hotel ' + processed + ' of ' + total + '...');

                        // Calculate time remaining for Phase 2
                        if (importStartTime && processed > 0) {
                            var elapsed = (Date.now() - importStartTime) / 1000;
                            var rate = processed / elapsed;
                            var remaining = total - processed;
                            var eta = remaining / rate;
                            var etaMins = Math.floor(eta / 60);
                            var etaSecs = Math.floor(eta % 60);
                            $('#time-remaining').text(etaMins > 0 ? etaMins + 'm ' + etaSecs + 's' : etaSecs + 's (images are slow)');
                        }
                    }
                }
                else if (message.includes('Images:') && message.includes('downloaded')) {
                    var match = message.match(/(\d+) downloaded/);
                    if (match) {
                        var current = parseInt($('#images-processed').text()) || 0;
                        $('#images-processed').text(current + parseInt(match[1]));
                    }
                }
                else if (message.includes('PHASE 2 COMPLETE')) {
                    $('#phase-icon').text('✅');
                    $('#phase-name').text('Phase 2 Complete!');
                    $('#current-action').text('All images downloaded and processed.');
                    $('#progress-bar').css('width', '95%').text('95%');
                    $('#overall-percent').text('95%');
                }
                else if (message.includes('Import completed:')) {
                    $('#phase-icon').text('🎉');
                    $('#phase-name').text('Import Complete!');
                    $('#current-action').text('Import finished successfully.');
                    $('#progress-bar').css('width', '100%').text('100%');
                    $('#overall-percent').text('100%');
                    $('#time-remaining').text('Complete!');
                }
            }

            $('#btn-skip-to-phase2').on('click', function(e) {
                e.preventDefault();
                console.log('Skip to Phase 2 button clicked');
                
                if (!confirm('⚠️ Skip to Phase 2?\n\nThis will skip Phase 1 (hotel creation) and start directly with Phase 2 (image downloads).\n\nUse this for debugging image download issues only.\n\nContinue?')) {
                    return;
                }

                var btn = $(this);
                console.log('Starting Phase 2...');
                btn.prop('disabled', true).text('⏳ <?php echo esc_js( __( 'Starting Phase 2...', 'seminargo' ) ); ?>');

                // Reset progress tracking
                importStartTime = Date.now();
                lastProcessedCount = 0;
                totalHotels = 0;

                // Reset UI
                $('#import-progress').show();
                $('#phase-icon').text('📸');
                $('#phase-name').text('Phase 2: Downloading Images (Debug Mode)');
                $('#current-action').text('Starting Phase 2 directly (skipped Phase 1)...');
                $('#progress-bar').css('width', '50%').css('background', 'linear-gradient(90deg, #AC2A6E, #d64a94)').text('50%');
                $('#overall-percent').text('50%');
                $('#hotels-processed, #hotels-total, #live-created, #live-updated').text('0');
                $('#images-processed').text('0');
                $('#time-elapsed').text('0s');
                $('#time-remaining').text('Calculating...');

                // Skip to Phase 2
                $.post(ajaxurl, { action: 'seminargo_skip_to_phase2' }, function(response) {
                    if (!response.success) {
                        $('#phase-icon').text('❌');
                        $('#phase-name').text('Failed to Start Phase 2');
                        $('#current-action').text('Error: ' + (response.data || 'Unknown error'));
                        btn.prop('disabled', false).text('📸 <?php echo esc_js( __( 'Skip to Phase 2 (Debug)', 'seminargo' ) ); ?>');
                        console.error('Skip to Phase 2 failed:', response);
                        return;
                    }

                    console.log('Phase 2 started successfully:', response);
                    $('#current-action').text('Phase 2 running in background batches...');

                    // Poll for progress every 2 seconds
                    var progressInterval = setInterval(function() {
                        $.post(ajaxurl, { action: 'seminargo_get_import_progress' }, function(resp) {
                            if (!resp.success) return;

                            var prog = resp.data.progress;
                            var logs = resp.data.logs;

                            // Update logs
                            if (logs) {
                                renderLogs(logs);
                                updateProgressUI(logs);
                            }

                            // Update progress from background process
                            if (prog) {
                                $('#hotels-total').text(prog.total_hotels || 0);
                                $('#hotels-processed').text(prog.hotels_processed || 0);
                                $('#live-created').text(prog.created || 0);
                                $('#live-updated').text(prog.updated || 0);
                                $('#images-processed').text(prog.images_processed || 0);

                                // Calculate overall progress (Phase 2 is 50-95%)
                                if (prog.total_hotels > 0 && prog.phase === 'phase2') {
                                    var phase2Percent = (prog.images_processed / prog.total_hotels) * 100;
                                    var overallPercent = 50 + (phase2Percent * 0.45); // 50-95% range
                                    $('#progress-bar').css('width', overallPercent + '%').text(Math.round(overallPercent) + '%');
                                    $('#overall-percent').text(Math.round(overallPercent) + '%');
                                }

                                // Check if complete
                                if (prog.status === 'complete' || prog.phase === 'done') {
                                    clearInterval(progressInterval);
                                    $('#phase-icon').text('✅');
                                    $('#phase-name').text('Import Complete');
                                    $('#current-action').text('All images processed!');
                                    $('#progress-bar').css('width', '100%').text('100%');
                                    $('#overall-percent').text('100%');
                                    btn.prop('disabled', false).text('📸 <?php echo esc_js( __( 'Skip to Phase 2 (Debug)', 'seminargo' ) ); ?>');
                                }
                            }
                        });
                    }, 2000);
                }).fail(function(xhr, status, error) {
                    console.error('AJAX error:', status, error, xhr);
                    $('#phase-icon').text('❌');
                    $('#phase-name').text('Failed to Start Phase 2');
                    $('#current-action').text('AJAX Error: ' + error);
                    btn.prop('disabled', false).text('📸 <?php echo esc_js( __( 'Skip to Phase 2 (Debug)', 'seminargo' ) ); ?>');
                });
            });

            $('#btn-fetch-now').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('⏳ <?php echo esc_js( __( 'Importing...', 'seminargo' ) ); ?>');

                // Reset progress tracking
                importStartTime = Date.now();
                lastProcessedCount = 0;
                totalHotels = 0;

                // Reset UI
                $('#import-progress').show();
                $('#phase-icon').text('🚀');
                $('#phase-name').text('Starting Batched Import...');
                $('#current-action').text('Initializing background process...');
                $('#progress-bar').css('width', '5%').css('background', 'linear-gradient(90deg, #AC2A6E, #d64a94)').text('5%');
                $('#overall-percent').text('5%');
                $('#hotels-processed, #hotels-total, #live-created, #live-updated, #images-processed').text('0');
                $('#time-elapsed').text('0s');
                $('#time-remaining').text('Calculating...');

                // Start batched import (returns immediately)
                $.post(ajaxurl, { action: 'seminargo_start_batched_import' }, function(response) {
                    if (!response.success) {
                        $('#phase-icon').text('❌');
                        $('#phase-name').text('Failed to Start');
                        $('#current-action').text('Error: ' + response.data);
                        btn.prop('disabled', false).text('🔄 <?php echo esc_js( __( 'Fetch Now', 'seminargo' ) ); ?>');
                        return;
                    }

                    $('#current-action').text('Import running in background batches...');

                    // Poll for progress every 2 seconds
                    var progressInterval = setInterval(function() {
                        $.post(ajaxurl, { action: 'seminargo_get_import_progress' }, function(resp) {
                            if (!resp.success) return;

                            var prog = resp.data.progress;
                            var logs = resp.data.logs;

                            // Update logs
                            if (logs) {
                                renderLogs(logs);
                                updateProgressUI(logs);
                            }

                            // Update progress from background process
                            if (prog) {
                                $('#hotels-total').text(prog.total_hotels || 0);
                                $('#hotels-processed').text(prog.hotels_processed || 0);
                                $('#live-created').text(prog.created || 0);
                                $('#live-updated').text(prog.updated || 0);
                                $('#images-processed').text(prog.images_processed || 0);

                                // Calculate overall progress
                                if (prog.total_hotels > 0) {
                                    var percent = 0;
                                    if (prog.phase === 'fetch') {
                                        percent = (prog.hotels_fetched / 5000) * 10; // 0-10%
                                    } else if (prog.phase === 'phase1') {
                                        percent = 10 + ((prog.hotels_processed / prog.total_hotels) * 40); // 10-50%
                                    } else if (prog.phase === 'phase2') {
                                        percent = 50 + ((prog.offset / prog.total_hotels) * 45); // 50-95%
                                    } else if (prog.phase === 'finalize' || prog.phase === 'done') {
                                        percent = 100;
                                    }

                                    $('#progress-bar').css('width', Math.round(percent) + '%').text(Math.round(percent) + '%');
                                    $('#overall-percent').text(Math.round(percent) + '%');
                                }

                                // Update elapsed time
                                if (prog.start_time) {
                                    var elapsed = Math.floor((Date.now() / 1000) - prog.start_time);
                                    var mins = Math.floor(elapsed / 60);
                                    var secs = elapsed % 60;
                                    $('#time-elapsed').text(mins > 0 ? mins + 'm ' + secs + 's' : secs + 's');
                                }

                                // Check if complete
                                if (prog.status === 'complete') {
                                    clearInterval(progressInterval);
                                    btn.prop('disabled', false).text('🔄 <?php echo esc_js( __( 'Fetch Now', 'seminargo' ) ); ?>');

                                    $('#phase-icon').text('🎉');
                                    $('#phase-name').text('Import Complete!');
                                    $('#current-action').text('All batches processed successfully.');
                                    $('#progress-bar').css('width', '100%').text('100%');
                                    $('#overall-percent').text('100%');
                                    $('#time-remaining').text('Complete!');

                                    $('#stat-created').text(prog.created);
                                    $('#stat-updated').text(prog.updated);
                                    $('#stat-drafted').text(prog.drafted);
                                    $('#stat-errors').text(prog.errors);

                                    setTimeout(function() {
                                        $('#import-progress').fadeOut(2000);
                                    }, 10000);
                                }
                            }
                        });
                    }, 2000);

                    // Immediate first poll
                    setTimeout(function() {
                        $.post(ajaxurl, { action: 'seminargo_get_import_progress' }, function(resp) {
                            if (resp.success && resp.data.logs) {
                                renderLogs(resp.data.logs);
                            }
                        });
                    }, 500);
                });
            });

            $('#btn-clear-logs').on('click', function() {
                if (confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs?', 'seminargo' ) ); ?>')) {
                    $.post(ajaxurl, { action: 'seminargo_clear_logs' }, function() {
                        loadLogs();
                    });
                }
            });

            $('#btn-delete-all-hotels').on('click', function() {
                var confirmed = confirm('⚠️ ARE YOU ABSOLUTELY SURE?\n\nThis will PERMANENTLY DELETE:\n• All hotel posts\n• All hotel images\n• All hotel metadata\n\nThis action CANNOT be undone!\n\nClick OK to continue, or Cancel to abort.');
                if (!confirmed) return;

                var confirmText = prompt('⚠️ FINAL WARNING!\n\nType "DELETE" in capital letters to confirm deletion:');
                if (confirmText !== 'DELETE') {
                    alert('❌ Deletion cancelled. You did not type "DELETE" correctly.');
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('🔥 Deleting...');

                $.post(ajaxurl, { action: 'seminargo_delete_all_hotels' }, function(response) {
                    if (response.success) {
                        alert('✅ Successfully deleted!\n\n' +
                              '• Hotels: ' + response.data.deleted_posts + '\n' +
                              '• Images: ' + response.data.deleted_images + '\n\n' +
                              'The page will now reload.');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + response.data);
                        $btn.prop('disabled', false).text('💣 Delete All Hotels');
                    }
                }).fail(function() {
                    alert('❌ Server error occurred during deletion.');
                    $btn.prop('disabled', false).text('💣 Delete All Hotels');
                });
            });

            $('#filter-errors, #filter-updates').on('change', function() {
                loadLogs();
            });

            // Auto-Import Controls
            function loadAutoImportStatus() {
                $.post(ajaxurl, { action: 'seminargo_get_auto_import_status' }, function(response) {
                    if (response.success) {
                        const data = response.data;
                        const enabled = data.enabled;
                        const progress = data.progress;

                        // Update button
                        const $btn = $('#btn-toggle-auto-import');
                        if (enabled) {
                            $btn.text('⏸️ <?php echo esc_js( __( 'Disable Auto-Import', 'seminargo' ) ); ?>').removeClass('button-primary').addClass('button-secondary');
                        } else {
                            $btn.text('▶️ <?php echo esc_js( __( 'Enable Auto-Import', 'seminargo' ) ); ?>').addClass('button-primary').removeClass('button-secondary');
                        }

                        // Update status display
                        let statusHTML = '<table class="widefat" style="font-size: 12px;">';
                        statusHTML += '<tr><td><strong>Status:</strong></td><td>' + (enabled ? '<span style="color: #51cf66;">● Active</span>' : '<span style="color: #868e96;">○ Disabled</span>') + '</td></tr>';
                        statusHTML += '<tr><td><strong>Progress:</strong></td><td>' + progress.offset + ' hotels processed</td></tr>';
                        statusHTML += '<tr><td><strong>Total Imported:</strong></td><td>' + progress.total_imported + ' hotels</td></tr>';
                        statusHTML += '<tr><td><strong>Status:</strong></td><td>' + (progress.is_complete ? '<span style="color: #51cf66;">✅ Complete</span>' : '<span style="color: #2271b1;">🔄 In Progress</span>') + '</td></tr>';

                        if (enabled) {
                            statusHTML += '<tr><td><strong>Next Run:</strong></td><td>' + data.next_run_formatted + '</td></tr>';
                        }

                        if (data.last_run_formatted) {
                            statusHTML += '<tr><td><strong>Last Run:</strong></td><td>' + data.last_run_formatted + '</td></tr>';
                        }

                        statusHTML += '</table>';

                        $('#auto-import-status').html(statusHTML);
                    }
                });
            }

            $('#btn-toggle-auto-import').on('click', function() {
                const $btn = $(this);
                const wasEnabled = $btn.text().includes('Disable');
                const newState = !wasEnabled;

                $btn.prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'seminargo_toggle_auto_import',
                    enabled: newState
                }, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        loadAutoImportStatus();
                        loadLogs();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $('#btn-reset-progress').on('click', function() {
                if (confirm('<?php echo esc_js( __( 'Reset auto-import progress? This will start the import from the beginning.', 'seminargo' ) ); ?>')) {
                    $.post(ajaxurl, { action: 'seminargo_reset_auto_import' }, function(response) {
                        if (response.success) {
                            loadAutoImportStatus();
                        }
                    });
                }
            });

            // Initial loads
            loadLogs();
            loadAutoImportStatus();

            // Refresh auto-import status every 30 seconds
            setInterval(loadAutoImportStatus, 30000);
            
            // Debug: Test skip button after a short delay
            setTimeout(function() {
                var $btn = $('#btn-skip-to-phase2');
                if ($btn.length > 0) {
                    console.log('✅ Skip to Phase 2 button found and ready');
                    // Test if click handler is attached
                    $btn.on('test', function() {
                        console.log('✅ Click handler is attached');
                    });
                    $btn.trigger('test');
                } else {
                    console.error('❌ Skip to Phase 2 button NOT FOUND in DOM');
                }
            }, 1000);
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for fetching hotels
     */
    public function ajax_fetch_hotels() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'seminargo' ) );
        }

        // Increase memory and execution time for FULL import (40k+ hotels)
        @ini_set( 'memory_limit', '1024M' );
        @ini_set( 'max_execution_time', 0 ); // No limit
        @set_time_limit( 0 ); // No limit

        // Prevent timeouts by keeping connection alive
        if ( function_exists( 'apache_setenv' ) ) {
            @apache_setenv( 'no-gzip', 1 );
        }
        @ini_set( 'zlib.output_compression', 0 );
        @ini_set( 'implicit_flush', 1 );

        $result = $this->run_import();
        wp_send_json_success( $result );
    }

    /**
     * AJAX handler for getting logs
     */
    public function ajax_get_logs() {
        $logs = get_option( $this->log_option, [] );
        wp_send_json_success( array_reverse( $logs ) );
    }

    /**
     * AJAX handler for clearing logs
     */
    public function ajax_clear_logs() {
        delete_option( $this->log_option );
        wp_send_json_success();
    }

    /**
     * AJAX handler to delete ALL hotels and their images
     */
    public function ajax_delete_all_hotels() {
        // Security check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        // Get all hotels
        $hotels = get_posts([
            'post_type'      => 'hotel',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ]);

        $deleted_posts = 0;
        $deleted_images = 0;

        // Delete each hotel and its images
        foreach ( $hotels as $hotel_id ) {
            // Get all images attached to this hotel
            $attachments = get_posts([
                'post_type'      => 'attachment',
                'posts_per_page' => -1,
                'post_parent'    => $hotel_id,
                'fields'         => 'ids',
            ]);

            // Delete all images
            foreach ( $attachments as $attachment_id ) {
                if ( wp_delete_attachment( $attachment_id, true ) ) {
                    $deleted_images++;
                }
            }

            // Delete the hotel post
            if ( wp_delete_post( $hotel_id, true ) ) {
                $deleted_posts++;
            }
        }

        // Clear import-related options
        delete_option( $this->log_option );
        delete_option( $this->last_import_option );
        delete_option( $this->imported_ids_option );

        wp_send_json_success([
            'deleted_posts'  => $deleted_posts,
            'deleted_images' => $deleted_images,
        ]);
    }

    /**
     * AJAX handler to start batched import (WP Engine compatible)
     * Returns immediately and processes in background
     */
    public function ajax_start_batched_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        // Initialize progress tracking - DON'T store hotel data, just progress numbers
        $progress = [
            'status' => 'running',
            'phase' => 'phase1', // Phase 1: Create hotels (no images)
            'offset' => 0,
            'total_hotels' => 0, // Will be set after first fetch
            'hotels_processed' => 0,
            'images_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'drafted' => 0,
            'errors' => 0,
            'start_time' => time(),
        ];

        update_option( 'seminargo_batched_import_progress', $progress, false );

        // Clear old logs
        delete_option( $this->log_option );

        $this->log( 'info', '🚀 Starting batched import (WP Engine compatible)...' );
        $this->flush_logs();

        // Schedule immediate first batch to fetch hotels
        $scheduled = wp_schedule_single_event( time(), 'seminargo_process_import_batch' );
        
        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H8',
            'location' => 'ajax_start_batched_import:schedule',
            'message' => 'Scheduled batch event',
            'data' => [
                'scheduled' => $scheduled !== false,
                'scheduled_time' => time(),
                'disable_wp_cron' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
            ],
            'timestamp' => time() * 1000
        ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion
        
        // Force WP Cron to spawn immediately (WP Cron may not fire reliably on production)
        if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
            // #region agent log
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'ajax_start_batched_import:spawn_cron',
                'message' => 'Calling spawn_cron',
                'data' => [ 'before_spawn' => true ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            $spawn_result = spawn_cron();
            
            // #region agent log
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'ajax_start_batched_import:spawn_cron_result',
                'message' => 'spawn_cron result',
                'data' => [ 'spawn_result' => $spawn_result, 'after_spawn' => true ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
        }

        wp_send_json_success([
            'message' => 'Batched import started in background',
            'progress' => $progress,
        ]);
    }

    /**
     * AJAX handler to skip to Phase 2 (debugging feature)
     */
    public function ajax_skip_to_phase2() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        // Get total hotel count for progress tracking
        $total_hotels = wp_count_posts( 'hotel' )->publish + wp_count_posts( 'hotel' )->draft;

        // Initialize progress tracking for Phase 2 only
        $progress = [
            'status' => 'running',
            'phase' => 'phase2', // Start directly in Phase 2
            'offset' => 0, // Hotel batch offset
            'current_hotel_id' => null, // Current hotel being processed
            'current_image_index' => 0, // Current image index in current hotel (0 = start from beginning)
            'total_hotels' => $total_hotels,
            'hotels_processed' => $total_hotels, // Pretend Phase 1 is done
            'images_processed' => 0, // Count of hotels with all images processed
            'created' => 0,
            'updated' => 0,
            'drafted' => 0,
            'errors' => 0,
            'start_time' => time(),
        ];

        update_option( 'seminargo_batched_import_progress', $progress, false );

        // Clear old logs
        delete_option( $this->log_option );

        $this->log( 'info', '🔧 DEBUG MODE: Skipping Phase 1, starting directly with Phase 2 (image downloads)' );
        $this->log( 'success', '📸 PHASE 2: Downloading images...' );
        $this->flush_logs();

        // Schedule first Phase 2 batch immediately
        $scheduled = wp_schedule_single_event( time(), 'seminargo_process_import_batch' );
        
        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H8',
            'location' => 'ajax_skip_to_phase2:schedule',
            'message' => 'Scheduled Phase 2 batch event',
            'data' => [
                'scheduled' => $scheduled !== false,
                'scheduled_time' => time(),
                'disable_wp_cron' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
            ],
            'timestamp' => time() * 1000
        ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion
        
        // Force WP Cron to spawn immediately (WP Cron may not fire reliably on production)
        if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
            // #region agent log
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'ajax_skip_to_phase2:spawn_cron',
                'message' => 'Calling spawn_cron for Phase 2',
                'data' => [ 'before_spawn' => true ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            $spawn_result = spawn_cron();
            
            // #region agent log
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'ajax_skip_to_phase2:spawn_cron_result',
                'message' => 'spawn_cron result for Phase 2',
                'data' => [ 'spawn_result' => $spawn_result, 'after_spawn' => true ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
        }

        wp_send_json_success([
            'message' => 'Phase 2 started (Phase 1 skipped)',
            'progress' => $progress,
        ]);
    }

    /**
     * AJAX handler to get current import progress
     */
    public function ajax_get_import_progress() {
        $progress = get_option( 'seminargo_batched_import_progress', null );
        $logs = get_option( $this->log_option, [] );

        wp_send_json_success([
            'progress' => $progress,
            'logs' => array_reverse( $logs ),
        ]);
    }

    /**
     * Process a single batch (called by WP Cron)
     * Runs in background, no timeout issues
     */
    public function process_single_batch() {
        // Track request start time for timeout monitoring
        $request_start_time = time();
        $request_start_microtime = microtime( true );
        
        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        $log_data = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H1,H2,H3,H5,H7,H8',
            'location' => 'process_single_batch:entry',
            'message' => 'process_single_batch CALLED',
            'data' => [
                'memory_limit' => ini_get( 'memory_limit' ),
                'max_execution_time' => ini_get( 'max_execution_time' ),
                'memory_usage' => memory_get_usage( true ),
                'peak_memory' => memory_get_peak_usage( true ),
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo( 'version' ),
                'is_cron' => defined( 'DOING_CRON' ) && DOING_CRON,
                'is_ajax' => defined( 'DOING_AJAX' ) && DOING_AJAX,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
                'request_start_time' => $request_start_time,
                'request_start_microtime' => $request_start_microtime,
                'wp_engine_limit' => 60,
            ],
            'timestamp' => time() * 1000
        ];
        @file_put_contents( $log_path, json_encode( $log_data ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion
        
        $progress = get_option( 'seminargo_batched_import_progress', null );

        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H8',
            'location' => 'process_single_batch:progress_check',
            'message' => 'Checking progress option',
            'data' => [
                'progress_exists' => $progress !== null,
                'progress_status' => $progress['status'] ?? 'null',
                'progress_phase' => $progress['phase'] ?? 'null',
                'progress_offset' => $progress['offset'] ?? 'null',
            ],
            'timestamp' => time() * 1000
        ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion

        if ( ! $progress || $progress['status'] !== 'running' ) {
            // #region agent log
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'process_single_batch:early_exit',
                'message' => 'Early exit - not running',
                'data' => [ 'progress' => $progress ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            return; // Not running or already complete
        }

        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H3,H5', 'location' => 'process_single_batch:before_ini_set', 'message' => 'Before ini_set', 'data' => [ 'phase' => $progress['phase'] ?? 'unknown', 'offset' => $progress['offset'] ?? 0 ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion

        @ini_set( 'memory_limit', '1024M' );
        // WP Engine enforces 60-second max execution time (cannot be increased)
        // Set to 50 seconds to leave buffer for scheduling next batch
        @ini_set( 'max_execution_time', 50 );
        
        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H3,H5', 'location' => 'process_single_batch:after_ini_set', 'message' => 'After ini_set', 'data' => [ 'memory_limit' => ini_get( 'memory_limit' ), 'max_execution_time' => ini_get( 'max_execution_time' ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion

        $batch_size = 200;

        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H8',
            'location' => 'process_single_batch:before_try',
            'message' => 'Before try block',
            'data' => [
                'phase' => $progress['phase'] ?? 'null',
                'offset' => $progress['offset'] ?? 0,
                'batch_size' => $batch_size,
            ],
            'timestamp' => time() * 1000
        ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion

        try {
            // #region agent log
            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'process_single_batch:inside_try',
                'message' => 'Inside try block',
                'data' => [
                    'phase' => $progress['phase'] ?? 'null',
                    'offset' => $progress['offset'] ?? 0,
                ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            // #region agent log
            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'process_single_batch:before_phase_check',
                'message' => 'Before phase check',
                'data' => [
                    'phase' => $progress['phase'] ?? 'null',
                    'offset' => $progress['offset'] ?? 0,
                ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            // PHASE 1: FETCH & CREATE HOTELS (NO IMAGES) - Batched
            if ( $progress['phase'] === 'phase1' ) {
                $batch_size = 200;
                $offset = $progress['offset'];

                // Fetch batch from API
                $this->log( 'info', '📦 Fetching & processing batch: skip=' . $offset . ', limit=' . $batch_size );

                $batch_hotels = $this->fetch_hotels_batch_from_api( $offset, $batch_size );
                $batch_count = count( $batch_hotels );

                if ( $batch_count === 0 ) {
                    // No more hotels - move to Phase 2
                    $progress['phase'] = 'phase2';
                    $progress['offset'] = 0;
                    $this->log( 'success', "✅ PHASE 1 COMPLETE! All hotels created/updated (total: {$progress['hotels_processed']})" );
                    $this->log( 'success', '📸 PHASE 2: Downloading images...' );
                    $this->flush_logs();

                    update_option( 'seminargo_batched_import_progress', $progress, false );
                    
                    // CRITICAL: Directly execute Phase 2 immediately to avoid WP Cron delays on production
                    // This ensures Phase 2 starts immediately rather than waiting for cron
                    $this->log( 'info', '🔄 Starting Phase 2 immediately...' );
                    $this->flush_logs();
                    
                    // Recursively call ourselves to process Phase 2 immediately
                    // This bypasses WP Cron which may not fire reliably on production
                    $this->process_single_batch();
                    return;
                }

                // Process this batch
                foreach ( $batch_hotels as $hotel ) {
                    try {
                        $result = $this->process_hotel( $hotel, false ); // No images yet
                        if ( $result === 'created' ) {
                            $progress['created']++;
                        } elseif ( $result === 'updated' ) {
                            $progress['updated']++;
                        }
                        $progress['hotels_processed']++;
                    } catch ( Exception $e ) {
                        $progress['errors']++;
                        $this->log( 'error', '❌ Error: ' . $e->getMessage(), $hotel->businessName ?? '' );
                    }
                }

                // Update offset for next batch
                $progress['offset'] += $batch_size;

                // Estimate total (rough - will be accurate once we hit the end)
                if ( $progress['total_hotels'] === 0 && $batch_count === $batch_size ) {
                    $progress['total_hotels'] = 5000; // Rough estimate
                }

                // Log progress
                $this->log( 'info', "📊 Phase 1: Processed {$progress['hotels_processed']} hotels - Created: {$progress['created']}, Updated: {$progress['updated']}" );
                $this->flush_logs();

                update_option( 'seminargo_batched_import_progress', $progress, false );

                // Schedule next batch
                wp_schedule_single_event( time() + 1, 'seminargo_process_import_batch' );
                
                // Force WP Cron to spawn on production (WP Cron may not fire reliably)
                if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
                    spawn_cron();
                }
                
                return;
            }

            // #region agent log
            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H8',
                'location' => 'process_single_batch:before_phase2_check',
                'message' => 'Before Phase 2 check',
                'data' => [
                    'phase' => $progress['phase'] ?? 'null',
                    'offset' => $progress['offset'] ?? 0,
                    'will_enter_phase2' => ( $progress['phase'] ?? '' ) === 'phase2',
                ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion

            // PHASE 2: DOWNLOAD IMAGES - Batched
            if ( $progress['phase'] === 'phase2' ) {
                // #region agent log
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'H1,H2,H3,H5,H7,H8',
                    'location' => 'process_single_batch:phase2_entry',
                    'message' => 'Phase 2 ENTERED',
                    'data' => [
                        'offset' => $progress['offset'] ?? 0,
                        'images_processed' => $progress['images_processed'] ?? 0,
                        'memory_usage' => memory_get_usage( true ),
                        'peak_memory' => memory_get_peak_usage( true ),
                        'start_time' => $progress['start_time'] ?? time(),
                        'elapsed' => time() - ( $progress['start_time'] ?? time() ),
                        'wp_engine_limit' => 60,
                        'current_execution_time' => time() - ( $_SERVER['REQUEST_TIME'] ?? time() ),
                        'is_cron' => defined( 'DOING_CRON' ) && DOING_CRON,
                        'log_path' => $log_path,
                        'log_path_writable' => is_writable( dirname( $log_path ) ),
                    ],
                    'timestamp' => time() * 1000
                ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion
                
                // WP Engine enforces 60-second execution limit
                // NEW APPROACH: Process ONE image per request to avoid timeout
                // This way each request is fast (< 5 seconds) and we never hit the 60s limit
                $timeout_threshold = 50; // Exit at 50 seconds to leave 10s buffer
                
                // Check if we're resuming a partially processed hotel
                $current_hotel_id = $progress['current_hotel_id'] ?? null;
                $current_image_index = $progress['current_image_index'] ?? 0;
                
                // If we have a current hotel, we're resuming from a partial process
                if ( $current_hotel_id !== null ) {
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H7', 'location' => 'process_single_batch:phase2_resume_hotel', 'message' => 'Resuming partially processed hotel', 'data' => [ 'hotel_id' => $current_hotel_id, 'resume_from_image' => $current_image_index, 'offset' => $progress['offset'] ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    
                    // Fetch the specific hotel to resume
                    $batch_hotels = $this->fetch_hotels_batch_from_api( $progress['offset'], 1 );
                    if ( ! empty( $batch_hotels ) && $batch_hotels[0]->id == $current_hotel_id ) {
                        $hotel = $batch_hotels[0];
                    } else {
                        // Hotel not found or changed - reset and continue
                        $progress['current_hotel_id'] = null;
                        $progress['current_image_index'] = 0;
                        $current_hotel_id = null;
                        $current_image_index = 0;
                    }
                }
                
                // If no current hotel, fetch next batch
                if ( $current_hotel_id === null ) {
                $offset = $progress['offset'];
                    $batch_size = 10; // Fetch 10 hotels, but process images one-by-one
                    
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H3', 'location' => 'process_single_batch:phase2_fetch_start', 'message' => 'Fetching batch from API', 'data' => [ 'offset' => $offset, 'batch_size' => $batch_size ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion

                // Fetch batch from API (again, to get image data)
                    $api_fetch_start = microtime( true );
                $batch_hotels = $this->fetch_hotels_batch_from_api( $offset, $batch_size );
                    $api_fetch_time = ( microtime( true ) - $api_fetch_start ) * 1000;
                $batch_count = count( $batch_hotels );
                    $elapsed_after_fetch = time() - $request_start_time;
                        
                        // #region agent log
                        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H3,H7', 'location' => 'process_single_batch:phase2_fetch_complete', 'message' => 'Batch fetched from API', 'data' => [ 'batch_count' => $batch_count, 'offset' => $offset, 'api_fetch_time_ms' => round( $api_fetch_time, 2 ), 'elapsed_seconds' => $elapsed_after_fetch, 'wp_engine_limit' => 60, 'time_remaining' => 60 - $elapsed_after_fetch ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion

                if ( $batch_count === 0 ) {
                    // Phase 2 complete - finalize
                    $progress['phase'] = 'finalize';
                    $this->log( 'success', "✅ PHASE 2 COMPLETE! Images processed for {$progress['images_processed']} hotels" );
                    $this->flush_logs();

                    update_option( 'seminargo_batched_import_progress', $progress, false );
                    wp_schedule_single_event( time() + 1, 'seminargo_process_import_batch' );
                            
                            // Force WP Cron to spawn on production (WP Cron may not fire reliably)
                            if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
                                spawn_cron();
                            }
                            
                    return;
                }

                    // Start with first hotel
                    $hotel = $batch_hotels[0];
                    $progress['current_hotel_id'] = $hotel->id;
                    $progress['current_image_index'] = 0;
                    $current_image_index = 0;
                }
                
                // Process ONE image for the current hotel
                if ( $hotel && ! empty( $hotel->medias ) ) {
                    try {
                        // Find WordPress post for this hotel
                        $query_start = microtime( true );
                        $args = [
                            'post_type' => 'hotel',
                            'post_status' => [ 'publish', 'draft' ],
                            'meta_query' => [
                                [ 'key' => 'hotel_id', 'value' => $hotel->id ],
                            ],
                            'posts_per_page' => 1,
                            'fields' => 'ids',
                        ];
                        $query = new WP_Query( $args );
                        $query_time = ( microtime( true ) - $query_start ) * 1000;
                        
                        // #region agent log
                        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H3', 'location' => 'process_single_batch:phase2_query_complete', 'message' => 'WP_Query completed', 'data' => [ 'hotel_id' => $hotel->id ?? 'unknown', 'query_time_ms' => round( $query_time, 2 ), 'found_posts' => $query->found_posts ?? 0, 'post_count' => $query->post_count ?? 0 ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion

                        if ( $query->have_posts() ) {
                            $post_id = $query->posts[0];
                            $total_images = count( $hotel->medias );
                            
                            // #region agent log
                            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H7', 'location' => 'process_single_batch:phase2_process_single_image', 'message' => 'Processing single image', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $current_image_index, 'total_images' => $total_images, 'elapsed_seconds' => time() - $request_start_time ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                            // #endregion
                            
                            // Process ONE image - wrapped in try-catch to ensure sync never crashes
                            try {
                                $image_result = $this->process_single_image( $post_id, $hotel, $current_image_index );
                                
                                // Log error if image failed but continue
                                if ( ! empty( $image_result['error'] ) ) {
                                    // Error already logged in process_single_image, just continue
                                }
                                
                                // Update progress - ALWAYS move to next image even on error
                                $progress['current_image_index'] = $image_result['next_index'];
                                
                                // If all images for this hotel are done, move to next hotel
                                if ( $image_result['next_index'] >= $total_images ) {
                                    $progress['images_processed']++;
                                    $progress['current_hotel_id'] = null;
                                    $progress['current_image_index'] = 0;
                                    
                                    // Move to next hotel in batch
                                    $progress['offset']++;
                                    
                                    // #region agent log
                                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2', 'location' => 'process_single_batch:phase2_hotel_complete', 'message' => 'Hotel images complete - moving to next', 'data' => [ 'hotel_id' => $hotel->id ?? 'unknown', 'total_images' => $total_images, 'images_processed_count' => $progress['images_processed'] ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                                    // #endregion
                                }
                            } catch ( Exception $e ) {
                                // CRITICAL: Even if process_single_image throws an exception, continue to next image
                                $error_message = 'Fatal error processing image: ' . $e->getMessage();
                                $this->log( 'error', "❌ CRITICAL: {$error_message} - Continuing to next image", $hotel->businessName ?? '' );
                                
                                // #region agent log
                                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                                @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_single_batch:phase2_fatal_error', 'message' => 'Fatal error - continuing to next image', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $current_image_index, 'error' => $error_message, 'trace' => $e->getTraceAsString() ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                                // #endregion
                                
                                // Move to next image to prevent infinite loop
                                $progress['current_image_index'] = $current_image_index + 1;
                                
                                // If we've processed all images (or errored through all), move to next hotel
                                if ( $progress['current_image_index'] >= $total_images ) {
                                    $progress['images_processed']++;
                                    $progress['current_hotel_id'] = null;
                                    $progress['current_image_index'] = 0;
                                    $progress['offset']++;
                                }
                            }
                        } else {
                            // Hotel post not found - skip this hotel
                            $progress['current_hotel_id'] = null;
                            $progress['current_image_index'] = 0;
                            $progress['offset']++;
                            
                            // #region agent log
                            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H3,H4', 'location' => 'process_single_batch:phase2_hotel_not_found', 'message' => 'Hotel post not found in WordPress', 'data' => [ 'hotel_id' => $hotel->id ?? 'unknown' ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                            // #endregion
                        }
                    } catch ( Exception $e ) {
                        // #region agent log
                        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_single_batch:phase2_exception', 'message' => 'Exception in Phase 2 image processing', 'data' => [ 'hotel_id' => $hotel->id ?? 'unknown', 'error' => $e->getMessage() ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                        
                        $this->log( 'error', '❌ Image error: ' . $e->getMessage(), $hotel->businessName ?? '' );
                        // Move to next image on error
                        $progress['current_image_index'] = $current_image_index + 1;
                    }
                } else {
                    // Hotel has no images - move to next
                    $progress['current_hotel_id'] = null;
                    $progress['current_image_index'] = 0;
                    $progress['offset']++;
                    
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_single_batch:phase2_hotel_skip', 'message' => 'Hotel skipped - no medias', 'data' => [ 'hotel_id' => $hotel->id ?? 'unknown' ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                }

                // Log progress
                if ( isset( $hotel ) ) {
                    $this->log( 'info', "📊 Phase 2: Processed image " . ( $current_image_index + 1 ) . " for hotel {$hotel->id} ({$progress['images_processed']} hotels complete)" );
                }
                $this->flush_logs();

                // Save progress after processing batch(es)
                $save_start = microtime( true );
                $save_result = update_option( 'seminargo_batched_import_progress', $progress, false );
                $save_time = ( microtime( true ) - $save_start ) * 1000;
                $elapsed_before_save = time() - $request_start_time;
                
                // Verify progress was saved
                $verify_progress = get_option( 'seminargo_batched_import_progress', null );
                $save_verified = ( $verify_progress && isset( $verify_progress['offset'] ) && $verify_progress['offset'] === $progress['offset'] );

                // #region agent log
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'H1,H7,H8',
                    'location' => 'process_single_batch:phase2_save_progress',
                    'message' => 'Saving progress before scheduling next batch',
                    'data' => [
                        'offset' => $progress['offset'] ?? 0,
                        'images_processed' => $progress['images_processed'] ?? 0,
                        'save_result' => $save_result,
                        'save_time_ms' => round( $save_time, 2 ),
                        'save_verified' => $save_verified,
                        'verified_offset' => $verify_progress['offset'] ?? 'null',
                        'elapsed_seconds' => $elapsed_before_save,
                        'wp_engine_limit' => 60,
                        'time_remaining' => 60 - $elapsed_before_save,
                    ],
                    'timestamp' => time() * 1000
                ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion

                // #region agent log
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'H8',
                    'location' => 'process_single_batch:phase2_scheduling',
                    'message' => 'Scheduling next Phase 2 batch',
                    'data' => [
                        'offset' => $progress['offset'] ?? 0,
                        'images_processed' => $progress['images_processed'] ?? 0,
                        'disable_wp_cron' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
                    ],
                    'timestamp' => time() * 1000
                ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion

                // Schedule next batch
                $scheduled = wp_schedule_single_event( time() + 1, 'seminargo_process_import_batch' );
                
                // #region agent log
                @file_put_contents( $log_path, json_encode( [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'H8',
                    'location' => 'process_single_batch:phase2_scheduled',
                    'message' => 'Next batch scheduled',
                    'data' => [
                        'scheduled' => $scheduled !== false,
                        'scheduled_time' => time() + 1,
                    ],
                    'timestamp' => time() * 1000
                ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion
                
                // Force WP Cron to spawn on production (WP Cron may not fire reliably)
                if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
                    // #region agent log
                    @file_put_contents( $log_path, json_encode( [
                        'sessionId' => 'debug-session',
                        'runId' => 'run1',
                        'hypothesisId' => 'H8',
                        'location' => 'process_single_batch:phase2_spawn_cron',
                        'message' => 'Calling spawn_cron for Phase 2',
                        'data' => [ 'before_spawn' => true ],
                        'timestamp' => time() * 1000
                    ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    
                    $spawn_start = microtime( true );
                    $spawn_result = spawn_cron();
                    $spawn_time = ( microtime( true ) - $spawn_start ) * 1000;
                    $elapsed_after_spawn = time() - $request_start_time;
                    
                    // #region agent log
                    @file_put_contents( $log_path, json_encode( [
                        'sessionId' => 'debug-session',
                        'runId' => 'run1',
                        'hypothesisId' => 'H7,H8',
                        'location' => 'process_single_batch:phase2_spawn_result',
                        'message' => 'spawn_cron result for Phase 2',
                        'data' => [
                            'spawn_result' => $spawn_result,
                            'spawn_time_ms' => round( $spawn_time, 2 ),
                            'after_spawn' => true,
                            'elapsed_seconds' => $elapsed_after_spawn,
                            'wp_engine_limit' => 60,
                            'time_remaining' => 60 - $elapsed_after_spawn,
                        ],
                        'timestamp' => time() * 1000
                    ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    
                    // FALLBACK: If spawn_cron fails or WP Cron is disabled, trigger via HTTP request
                    // This is critical for WP Engine where WP Cron may not fire reliably
                    if ( ! $spawn_result || defined( 'DISABLE_WP_CRON' ) ) {
                        $http_fallback_start = microtime( true );
                        $cron_url = site_url( 'wp-cron.php?doing_wp_cron=' . time() );
                        $http_response = wp_remote_post( $cron_url, [
                            'timeout' => 0.01,
                            'blocking' => false,
                            'sslverify' => false,
                        ] );
                        $http_fallback_time = ( microtime( true ) - $http_fallback_start ) * 1000;
                        $elapsed_after_http = time() - $request_start_time;
                        
                        // #region agent log
                        @file_put_contents( $log_path, json_encode( [
                            'sessionId' => 'debug-session',
                            'runId' => 'run1',
                            'hypothesisId' => 'H6,H8',
                            'location' => 'process_single_batch:phase2_http_fallback',
                            'message' => 'Using HTTP fallback to trigger next batch',
                            'data' => [
                                'site_url' => site_url(),
                                'cron_url' => $cron_url,
                                'http_response_code' => is_wp_error( $http_response ) ? 'error' : wp_remote_retrieve_response_code( $http_response ),
                                'http_error' => is_wp_error( $http_response ) ? $http_response->get_error_message() : null,
                                'http_fallback_time_ms' => round( $http_fallback_time, 2 ),
                                'elapsed_seconds' => $elapsed_after_http,
                                'wp_engine_limit' => 60,
                                'time_remaining' => 60 - $elapsed_after_http,
                            ],
                            'timestamp' => time() * 1000
                        ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                    }
                }
                
                // On WP Engine, avoid recursive calls - rely on WP Cron instead
                // Recursive calls can cause issues with the 60-second limit
                // #region agent log
                $request_elapsed_final = time() - $request_start_time;
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H5,H7', 'location' => 'process_single_batch:phase2_no_recursive', 'message' => 'Skipping recursive call (WP Engine 60s limit)', 'data' => [ 'batches_processed' => $batches_processed, 'request_elapsed' => $request_elapsed_final, 'wp_engine_limit' => 60, 'will_use_cron' => true ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion
                
                // #region agent log
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H3,H5,H7', 'location' => 'process_single_batch:phase2_exit', 'message' => 'Phase 2 batch complete, exiting', 'data' => [ 'batches_processed' => $batches_processed, 'images_processed' => $progress['images_processed'] ?? 0, 'offset' => $progress['offset'] ?? 0, 'memory_usage' => memory_get_usage( true ), 'peak_memory' => memory_get_peak_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion
                
                return;
            }

            // FINALIZE: Draft removed hotels
            if ( $progress['phase'] === 'finalize' ) {
                $this->log( 'info', '🔍 Checking for removed hotels...' );

                // Fetch all hotel IDs from API one more time to get complete list
                $all_api_ids = [];
                $offset = 0;
                $batch_size = 200;
                $has_more = true;

                while ( $has_more ) {
                    $batch = $this->fetch_hotels_batch_from_api( $offset, $batch_size );
                    foreach ( $batch as $hotel ) {
                        $all_api_ids[] = strval( $hotel->id );
                    }

                    if ( count( $batch ) < $batch_size ) {
                        $has_more = false;
                    } else {
                        $offset += $batch_size;
                    }
                }

                // Draft hotels not in API
                $existing_wp_hotels = $this->get_all_wordpress_hotel_ids();
                $hotels_to_draft = array_diff( $existing_wp_hotels, $all_api_ids );

                if ( ! empty( $hotels_to_draft ) ) {
                    $this->log( 'info', '📤 Found ' . count( $hotels_to_draft ) . ' hotels to draft' );

                    foreach ( $hotels_to_draft as $wp_hotel_id ) {
                        if ( $this->draft_removed_hotel( $wp_hotel_id ) ) {
                            $progress['drafted']++;
                        }
                    }
                }

                // Save final stats
                update_option( $this->imported_ids_option, $all_api_ids );
                update_option( $this->last_import_option, [
                    'created' => $progress['created'],
                    'updated' => $progress['updated'],
                    'drafted' => $progress['drafted'],
                    'errors' => $progress['errors'],
                    'time' => time(),
                ]);

                $this->log( 'success', '✅ Import completed: ' . $progress['created'] . ' created, ' . $progress['updated'] . ' updated, ' . $progress['drafted'] . ' drafted, ' . $progress['errors'] . ' errors' );
                $this->flush_logs();

                // Mark as complete
                $progress['status'] = 'complete';
                $progress['phase'] = 'done';
                $progress['total_hotels'] = $progress['hotels_processed']; // Final count
                update_option( 'seminargo_batched_import_progress', $progress, false );
                return;
            }

        } catch ( Exception $e ) {
            // #region agent log
            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H4,H8',
                'location' => 'process_single_batch:exception',
                'message' => 'EXCEPTION in process_single_batch',
                'data' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'phase' => $progress['phase'] ?? 'unknown',
                ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            $this->log( 'error', '❌ Batch error: ' . $e->getMessage() );
            $this->flush_logs();

            // Schedule retry
            wp_schedule_single_event( time() + 5, 'seminargo_process_import_batch' );
            
            // Force WP Cron to spawn on production (WP Cron may not fire reliably)
            if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
                spawn_cron();
            }
        } catch ( Error $e ) {
            // #region agent log
            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
            @file_put_contents( $log_path, json_encode( [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H4,H8',
                'location' => 'process_single_batch:fatal_error',
                'message' => 'FATAL ERROR in process_single_batch',
                'data' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'phase' => $progress['phase'] ?? 'unknown',
                ],
                'timestamp' => time() * 1000
            ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            $this->log( 'error', '❌ Fatal error: ' . $e->getMessage() );
            $this->flush_logs();
        }
    }

    /**
     * WP-CLI command to import hotels (bypasses WP Engine timeouts)
     * Usage: wp seminargo import-hotels
     */
    public function cli_import_hotels( $args, $assoc_args ) {
        WP_CLI::line( '🚀 Starting hotel import via WP-CLI...' );
        WP_CLI::line( '⚠️  This may take 10-30 minutes depending on image count.' );
        WP_CLI::line( '' );

        $start_time = microtime( true );

        // Run the import
        $result = $this->run_import();

        $elapsed = round( microtime( true ) - $start_time, 2 );

        WP_CLI::line( '' );
        WP_CLI::success( sprintf(
            'Import completed in %s seconds! Created: %d, Updated: %d, Drafted: %d, Errors: %d',
            $elapsed,
            $result['created'],
            $result['updated'],
            $result['drafted'],
            $result['errors']
        ) );
    }

    /**
     * Add log entry
     */
    private function log( $type, $message, $hotel = null, $field = null, $old_value = null, $new_value = null ) {
        $entry = [
            'time'    => current_time( 'Y-m-d H:i:s' ),
            'type'    => $type,
            'message' => $message,
        ];

        if ( $hotel ) {
            $entry['hotel'] = $hotel;
        }
        if ( $field ) {
            $entry['field'] = $field;
        }
        if ( $old_value !== null ) {
            $entry['old_value'] = $old_value;
        }
        if ( $new_value !== null ) {
            $entry['new_value'] = $new_value;
        }

        // Add to batch
        $this->log_batch[] = $entry;

        // Flush batch if it reaches the batch size
        if ( count( $this->log_batch ) >= $this->log_batch_size ) {
            $this->flush_logs();
        }
    }

    /**
     * Flush batched logs to database
     */
    private function flush_logs() {
        if ( empty( $this->log_batch ) ) {
            return;
        }

        // Get existing logs
        $logs = get_option( $this->log_option, [] );

        // Add batched logs
        $logs = array_merge( $logs, $this->log_batch );

        // Keep only last 1000 entries
        if ( count( $logs ) > 1000 ) {
            $logs = array_slice( $logs, -1000 );
        }

        // Save to database
        update_option( $this->log_option, $logs, false ); // false = don't autoload

        // Clear batch
        $this->log_batch = [];
    }

    /**
     * Fetch hotels from API with pagination
     */
    private function fetch_hotels_from_api() {
        $all_hotels = [];
        $batch_size = 200; // Fetch 200 hotels per batch (balanced between performance and reliability)
        $max_hotels = PHP_INT_MAX; // No limit - fetch ALL hotels
        $skip = 0;
        $has_more = true;

        $this->log( 'info', '📥 Starting batched hotel fetch (batch size: ' . $batch_size . ', fetching ALL hotels)' );

        while ( $has_more ) {
            $this->log( 'info', '📦 Fetching batch: skip=' . $skip . ', limit=' . $batch_size );

            $query = '{
                hotelList(skip: ' . $skip . ', limit: ' . $batch_size . ') {
                    id
                    slug
                    refCode
                    name
                    businessName
                    businessAddress1
                    businessAddress2
                    businessAddress3
                    businessAddress4
                    businessEmail
                    businessZip
                    businessCity
                    businessCountry
                    locationLongitude
                    locationLatitude
                    distanceToNearestAirport
                    distanceToNearestRailroadStation
                    rating
                    maxCapacityRooms
                    maxCapacityPeople
                    hasActivePartnerContract
                    texts {
                        id
                        details
                        type
                        language
                    }
                    attributes {
                        id
                        attribute
                    }
                    medias {
                        id
                        name
                        mimeType
                        width
                        height
                        format
                        path
                        url
                        previewUrl
                    }
                    integrations {
                        directBooking
                    }
                    spaceId
                    space {
                        name
                    }
                    meetingRooms {
                        id
                        name
                        area
                        capacityUForm
                        capacityTheater
                        capacityParlament
                        capacityCircle
                        capacityBankett
                        capacityCocktail
                        capacityBlock
                        facilityId
                        facility {
                            id
                            sku
                            name
                            header
                            details
                        }
                    }
                    cancellationRules {
                        id
                        sequence
                        daysToEvent
                        minCapacity
                        maxCapacity
                        minOvernight
                        maxOvernight
                        minTotalGuests
                        maxTotalGuests
                        toleranceRate
                        rate
                    }
                }
            }';

            $response = wp_remote_post( $this->api_url, [
                'body'    => json_encode( [ 'query' => $query ] ),
                'headers' => [ 'Content-Type' => 'application/json' ],
                'timeout' => 120, // Increased timeout for larger batches
            ] );

            if ( is_wp_error( $response ) ) {
                throw new Exception( 'API Error: ' . $response->get_error_message() );
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );

            if ( isset( $data->errors ) ) {
                throw new Exception( 'GraphQL Error: ' . json_encode( $data->errors ) );
            }

            $batch_hotels = $data->data->hotelList ?? [];
            $batch_count = count( $batch_hotels );

            $this->log( 'info', '✅ Fetched ' . $batch_count . ' hotels in this batch' );
            $this->flush_logs(); // Flush after each API batch

            if ( $batch_count === 0 ) {
                // No more hotels to fetch
                $has_more = false;
            } else {
                // Add to collection
                $all_hotels = array_merge( $all_hotels, $batch_hotels );
                $skip += $batch_size;

                // If we got fewer hotels than the batch size, we're done
                if ( $batch_count < $batch_size ) {
                    $has_more = false;
                } else {
                    // Small delay between batches to not overload the API (0.5 seconds)
                    usleep( 500000 );
                }
            }
        }

        $this->log( 'success', '✅ FETCH COMPLETE! Total hotels: ' . count( $all_hotels ) );
        $this->log( 'info', '🏨 NOW PROCESSING HOTELS - Creating posts and downloading images...' );
        $this->flush_logs(); // Flush logs after fetch complete
        return $all_hotels;
    }

    /**
     * Run the import process
     */
    public function run_import() {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'drafted' => 0,
            'errors'  => 0,
            'time'    => time(),
        ];

        $this->log( 'info', '🚀 Starting hotel import...' );

        try {
            $hotels = $this->fetch_hotels_from_api();

            if ( empty( $hotels ) ) {
                $this->log( 'error', '⚠️ No hotels received from API' );
                $stats['errors']++;
                update_option( $this->last_import_option, $stats );
                return $stats;
            }

            $this->log( 'info', '📦 Received ' . count( $hotels ) . ' hotels from API' );

            // Get all API hotel IDs (as strings for comparison)
            $api_hotel_ids = [];
            foreach ( $hotels as $hotel ) {
                $api_hotel_ids[] = strval( $hotel->id );
            }

            // Get ALL existing WordPress hotel IDs (not just previously imported)
            $existing_wp_hotels = $this->get_all_wordpress_hotel_ids();
            $this->log( 'info', '📊 Found ' . count( $existing_wp_hotels ) . ' existing hotels in WordPress' );

            // ===== PHASE 1: CREATE/UPDATE ALL HOTEL POSTS (NO IMAGES) =====
            $this->log( 'success', '🏨 PHASE 1: Creating/updating hotel posts (FAST - no images yet)...' );

            $total_hotels = count( $hotels );
            $processed_count = 0;
            $batch_report_size = 25; // Report progress every 25 hotels for frequent updates
            $hotels_with_images = []; // Queue for image processing

            foreach ( $hotels as $hotel ) {
                try {
                    $result = $this->process_hotel( $hotel, false ); // false = skip images
                    if ( $result === 'created' ) {
                        $stats['created']++;
                    } elseif ( $result === 'updated' ) {
                        $stats['updated']++;
                    }

                    // Queue hotel for image processing if it has images
                    if ( ! empty( $hotel->medias ) ) {
                        $hotels_with_images[] = $hotel;
                    }

                    $processed_count++;

                    // Progress reporting every N hotels
                    if ( $processed_count % $batch_report_size === 0 ) {
                        $percent = round( ( $processed_count / $total_hotels ) * 100 );
                        $this->log( 'info', "📊 Phase 1 Progress: {$processed_count}/{$total_hotels} hotels ({$percent}%) - Created: {$stats['created']}, Updated: {$stats['updated']}" );
                        $this->flush_logs(); // Flush logs at progress milestones
                    }

                } catch ( Exception $e ) {
                    $this->log( 'error', '❌ Error processing hotel ' . $hotel->businessName . ': ' . $e->getMessage(), $hotel->businessName );
                    $stats['errors']++;
                    $processed_count++;
                }
            }

            $this->log( 'success', "✅ PHASE 1 COMPLETE! All {$total_hotels} hotels created/updated in WordPress" );
            $this->flush_logs(); // Flush logs after Phase 1

            // ===== PHASE 2: BATCH DOWNLOAD ALL IMAGES =====
            if ( ! empty( $hotels_with_images ) ) {
                $this->log( 'success', '📸 PHASE 2: Downloading images for ' . count( $hotels_with_images ) . ' hotels...' );

                $image_processed_count = 0;
                $total_with_images = count( $hotels_with_images );

                foreach ( $hotels_with_images as $hotel ) {
                    try {
                        // Find the post ID for this hotel
                        $args = [
                            'post_type'      => 'hotel',
                            'post_status'    => [ 'publish', 'draft' ],
                            'meta_query'     => [
                                [
                                    'key'   => 'hotel_id',
                                    'value' => $hotel->id,
                                ],
                            ],
                            'posts_per_page' => 1,
                            'fields'         => 'ids',
                        ];
                        $query = new WP_Query( $args );

                        if ( $query->have_posts() ) {
                            $post_id = $query->posts[0];
                            $this->process_hotel_images( $post_id, $hotel );
                        }

                        $image_processed_count++;

                        // Progress reporting every 10 hotels
                        if ( $image_processed_count % 10 === 0 ) {
                            $percent = round( ( $image_processed_count / $total_with_images ) * 100 );
                            $this->log( 'info', "📊 Phase 2 Progress: {$image_processed_count}/{$total_with_images} hotels ({$percent}%)" );
                            $this->flush_logs(); // Flush logs at progress milestones
                        }

                    } catch ( Exception $e ) {
                        $this->log( 'error', '❌ Error downloading images for ' . $hotel->businessName . ': ' . $e->getMessage(), $hotel->businessName );
                    }
                }

                $this->log( 'success', "✅ PHASE 2 COMPLETE! Images processed for {$total_with_images} hotels" );
                $this->flush_logs(); // Flush logs after Phase 2
            }

            // Draft ALL WordPress hotels that are NOT in the API feed
            $hotels_to_draft = array_diff( $existing_wp_hotels, $api_hotel_ids );

            if ( ! empty( $hotels_to_draft ) ) {
                $this->log( 'info', '📤 Found ' . count( $hotels_to_draft ) . ' hotels to set as draft (not in API)' );
            }

            foreach ( $hotels_to_draft as $wp_hotel_id ) {
                $drafted = $this->draft_removed_hotel( $wp_hotel_id );
                if ( $drafted ) {
                    $stats['drafted']++;
                }
            }

            // Save current API hotel IDs for reference
            update_option( $this->imported_ids_option, $api_hotel_ids );

            $this->log( 'success', '✅ Import completed: ' . $stats['created'] . ' created, ' . $stats['updated'] . ' updated, ' . $stats['drafted'] . ' drafted, ' . $stats['errors'] . ' errors' );

        } catch ( Exception $e ) {
            $this->log( 'error', '💥 Import failed: ' . $e->getMessage() );
            $stats['errors']++;
        }

        // Final flush of all remaining logs
        $this->flush_logs();

        update_option( $this->last_import_option, $stats );
        return $stats;
    }

    /**
     * Get all hotel IDs from WordPress database
     */
    private function get_all_wordpress_hotel_ids() {
        global $wpdb;

        $results = $wpdb->get_col(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = 'hotel_id'
             AND p.post_type = 'hotel'
             AND p.post_status = 'publish'
             AND pm.meta_value IS NOT NULL
             AND pm.meta_value != ''"
        );

        return array_map( 'strval', $results );
    }

    /**
     * Draft a hotel that was removed from the API feed
     */
    private function draft_removed_hotel( $hotel_id ) {
        $args = [
            'post_type'      => 'hotel',
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => 'hotel_id',
                    'value' => $hotel_id,
                ],
            ],
            'posts_per_page' => 1,
        ];

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            $post = $query->posts[0];
            wp_update_post( [
                'ID'          => $post->ID,
                'post_status' => 'draft',
            ] );
            $this->log( 'draft', '📤 Hotel removed from feed, set to draft: ' . $post->post_title, $post->post_title );
            return true;
        }

        return false;
    }

    /**
     * Process a single hotel
     */
    private function process_hotel( $hotel, $process_images = true ) {
        // Check if hotel exists
        $args = [
            'post_type'      => 'hotel',
            'post_status'    => [ 'publish', 'draft' ],
            'meta_query'     => [
                [
                    'key'   => 'hotel_id',
                    'value' => $hotel->id,
                ],
            ],
            'posts_per_page' => 1,
        ];

        $query = new WP_Query( $args );
        $is_new = ! $query->have_posts();
        $post_id = null;

        // Prepare content
        $content = '';
        if ( ! empty( $hotel->texts ) ) {
            foreach ( $hotel->texts as $text ) {
                if ( $text->language === 'de' && $text->type === 'description' ) {
                    $content = $text->details;
                    break;
                }
            }
            // Fallback to first text
            if ( empty( $content ) && ! empty( $hotel->texts[0]->details ) ) {
                $content = $hotel->texts[0]->details;
            }
        }

        // Use 'name' for display, fallback to 'businessName' if name is empty
        $hotel_title = ! empty( $hotel->name ) ? $hotel->name : $hotel->businessName;

        // Use API slug as WordPress post slug (sanitized)
        $wp_slug = ! empty( $hotel->slug ) ? sanitize_title( $hotel->slug ) : sanitize_title( $hotel_title );

        if ( $is_new ) {
            // Create new hotel
            $post_data = [
                'post_title'   => $hotel_title,
                'post_name'    => $wp_slug,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'hotel',
            ];

            $post_id = wp_insert_post( $post_data );

            if ( is_wp_error( $post_id ) ) {
                throw new Exception( $post_id->get_error_message() );
            }

            $this->log( 'success', '✨ Created new hotel: ' . $hotel_title, $hotel_title );

            // Set all meta fields for new hotel
            $this->update_hotel_meta( $post_id, $hotel, true );

            // Handle images (only if requested)
            if ( $process_images ) {
                $this->process_hotel_images( $post_id, $hotel );
            }

            return 'created';

        } else {
            // Update existing hotel
            $post = $query->posts[0];
            $post_id = $post->ID;
            $has_changes = false;

            // Check and update title (normalize whitespace for comparison)
            $normalized_title = trim( $hotel_title );
            $normalized_post_title = trim( $post->post_title );
            if ( $normalized_post_title !== $normalized_title ) {
                $this->log( 'update', 'Updated hotel: ' . $hotel_title, $hotel_title, 'title', $post->post_title, $hotel_title );
                $has_changes = true;
            }

            // Check and update slug
            if ( $post->post_name !== $wp_slug ) {
                $this->log( 'update', 'Updated hotel: ' . $hotel_title, $hotel_title, 'slug', $post->post_name, $wp_slug );
                $has_changes = true;
            }

            // Check and update content (normalize whitespace for comparison)
            $normalized_content = trim( $content );
            $normalized_post_content = trim( $post->post_content );
            if ( $normalized_post_content !== $normalized_content ) {
                $this->log( 'update', 'Updated hotel: ' . $hotel_title, $hotel_title, 'content', substr( $post->post_content, 0, 50 ) . '...', substr( $content, 0, 50 ) . '...' );
                $has_changes = true;
            }

            // Update post if changed
            if ( $has_changes || $post->post_status === 'draft' ) {
                wp_update_post( [
                    'ID'           => $post_id,
                    'post_title'   => $hotel_title,
                    'post_name'    => $wp_slug,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                ] );
            }

            // Update meta fields and check for changes
            $meta_changed = $this->update_hotel_meta( $post_id, $hotel, false );

            // Process images for existing hotels too (in case new images added) - only if requested
            if ( $process_images ) {
                $this->process_hotel_images( $post_id, $hotel );
            }

            if ( $has_changes || $meta_changed ) {
                return 'updated';
            }

            // Log that hotel was checked but no changes detected
            $this->log( 'info', 'Checked hotel: ' . $hotel_title . ' - no changes detected', $hotel_title );
            return 'unchanged';
        }
    }

    /**
     * Update hotel meta fields
     */
    private function update_hotel_meta( $post_id, $hotel, $is_new ) {
        $has_changes = false;
        $hotel_name = $hotel->businessName;

        // Extract stars from attributes
        $stars = $this->extract_stars_from_attributes( $hotel->attributes ?? [] );

        // Extract texts by type (German preferred)
        $texts = $this->extract_texts_by_type( $hotel->texts ?? [] );

        // Build full address
        $full_address = $this->build_full_address( $hotel );

        $meta_fields = [
            // Basic info
            'hotel_id'      => $hotel->id,
            'api_slug'      => $hotel->slug ?? '',
            'finder_url_slug'     => $this->finder_base_url . '?showHotelBySlug=' . ( $hotel->slug ?? '' ),
            'finder_url_refcode'  => $this->finder_base_url . '?showHotelByRefCode=' . ( $hotel->refCode ?? '' ),
            'finder_add_slug'     => $this->finder_base_url . '?addHotelBySlug=' . ( $hotel->slug ?? '' ),
            'finder_add_refcode'  => $this->finder_base_url . '?addHotelByRefCode=' . ( $hotel->refCode ?? '' ),
            'ref_code'      => $hotel->refCode ?? '',
            'hotel_name'    => $hotel->name ?? '',
            'business_name' => $hotel->businessName ?? '',

            // Address fields
            'business_address_1' => $hotel->businessAddress1 ?? '',
            'business_address_2' => $hotel->businessAddress2 ?? '',
            'business_address_3' => $hotel->businessAddress3 ?? '',
            'business_address_4' => $hotel->businessAddress4 ?? '',
            'business_email'     => $hotel->businessEmail ?? '',
            'business_zip'       => $hotel->businessZip ?? '',
            'business_city'      => $hotel->businessCity ?? '',
            'business_country'   => $hotel->businessCountry ?? '',
            'full_address'       => $full_address,

            // Location
            'location_longitude'                   => $hotel->locationLongitude,
            'location_latitude'                    => $hotel->locationLatitude,
            'distance_to_nearest_airport'          => $hotel->distanceToNearestAirport,
            'distance_to_nearest_railroad_station' => $hotel->distanceToNearestRailroadStation,

            // Rating & Stars
            'rating' => $hotel->rating,
            'stars'  => $stars,

            // Capacity (from API or calculated)
            'max_capacity_rooms'  => $hotel->maxCapacityRooms ?? 0,
            'max_capacity_people' => $hotel->maxCapacityPeople ?? 0,

            // Partner info
            'has_active_partner_contract' => $hotel->hasActivePartnerContract ?? false,
            'direct_booking'              => $hotel->integrations->directBooking ?? false,
            'space_name'                  => $hotel->space->name ?? '',
            'space_id'                    => $hotel->spaceId ?? 0,

            // Texts - German descriptions
            'description'    => $texts['HOTEL_DESCRIPTION'] ?? '',
            'arrival_car'    => $texts['ARRIVAL_CAR'] ?? '',
            'arrival_flight' => $texts['ARRIVAL_FLIGHT'] ?? '',
            'arrival_train'  => $texts['ARRIVAL_TRAIN'] ?? '',

            // All texts as JSON for reference - NORMALIZED encoding
            'texts_json' => json_encode( $hotel->texts ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),

            // Attributes (full JSON) - NORMALIZED encoding
            'attributes' => json_encode( $hotel->attributes ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),

            // Extracted attribute lists for easier filtering - NORMALIZED encoding
            'amenities_list' => json_encode( $this->extract_amenities( $hotel->attributes ?? [] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),

            // Meeting rooms (full JSON with facility details) - NORMALIZED encoding
            'meeting_rooms' => json_encode( $hotel->meetingRooms ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),

            // Cancellation rules - NORMALIZED encoding
            'cancellation_rules' => json_encode( $hotel->cancellationRules ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),

            // Media metadata (full JSON) - NORMALIZED encoding
            'medias_json' => json_encode( $hotel->medias ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
        ];

        // Calculate capacity from meeting rooms if API doesn't provide it
        $max_capacity = $hotel->maxCapacityPeople ?? 0;

        if ( $max_capacity == 0 && ! empty( $hotel->meetingRooms ) ) {
            foreach ( $hotel->meetingRooms as $room ) {
                $room_max = max(
                    $room->capacityTheater ?? 0,
                    $room->capacityParlament ?? 0,
                    $room->capacityBankett ?? 0,
                    $room->capacityCocktail ?? 0,
                    $room->capacityUForm ?? 0,
                    $room->capacityBlock ?? 0,
                    $room->capacityCircle ?? 0
                );
                $max_capacity = max( $max_capacity, $room_max );
            }
        }

        // ALWAYS count meeting rooms from meetingRooms array (NOT maxCapacityRooms which is hotel bedrooms!)
        $meeting_rooms_count = ! empty( $hotel->meetingRooms ) ? count( $hotel->meetingRooms ) : 0;

        $meta_fields['capacity'] = $max_capacity;
        $meta_fields['rooms'] = $meeting_rooms_count; // Number of MEETING rooms (Tagungsräume)

        foreach ( $meta_fields as $key => $value ) {
            $old_value = get_post_meta( $post_id, $key, true );
            $new_value = is_bool( $value ) ? ( $value ? '1' : '0' ) : $value;

            // Smart comparison to avoid false positives
            $has_real_change = $this->has_real_change( $key, $old_value, $new_value );

            if ( $has_real_change ) {
                update_post_meta( $post_id, $key, $new_value );

                // Also update ACF field if function exists
                if ( function_exists( 'update_field' ) ) {
                    update_field( $key, $new_value, $post_id );
                }

                // Only log updates for NON-JSON fields (JSON changes are just encoding normalization)
                $json_fields = [ 'texts_json', 'attributes', 'amenities_list', 'meeting_rooms', 'cancellation_rules', 'medias_json' ];
                $is_meaningful_change = ! in_array( $key, $json_fields );

                if ( ! $is_new && $is_meaningful_change ) {
                    // Check if values are actually different (normalize strings for comparison)
                    $values_different = is_string( $old_value ) && is_string( $new_value ) 
                        ? trim( $old_value ) !== trim( $new_value )
                        : $old_value != $new_value;
                    
                    if ( $values_different ) {
                    $this->log( 'update', 'Updated hotel: ' . $hotel_name, $hotel_name, $key, $old_value, $new_value );
                    } else {
                        // Log that field was checked but no changes detected
                        $this->log( 'info', 'Checked hotel: ' . $hotel_name . ' - ' . $key . ': no changes detected', $hotel_name );
                    }
                }
                $has_changes = true;
            }
        }

        return $has_changes;
    }

    /**
     * Smart comparison to detect REAL changes and ignore false positives
     */
    private function has_real_change( $key, $old_value, $new_value ) {
        // If both are empty, no change
        if ( empty( $old_value ) && empty( $new_value ) ) {
            return false;
        }

        // For JSON fields, normalize and compare
        $json_fields = [ 'texts_json', 'attributes', 'amenities_list', 'meeting_rooms', 'cancellation_rules', 'medias_json' ];
        if ( in_array( $key, $json_fields ) ) {
            $old_decoded = json_decode( $old_value, true );
            $new_decoded = json_decode( $new_value, true );

            // Normalize both by re-encoding with same options (fixes encoding differences)
            $old_normalized = json_encode( $old_decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            $new_normalized = json_encode( $new_decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

            // Compare normalized JSON strings
            return $old_normalized !== $new_normalized;
        }

        // For floating point numbers (coordinates, distances), round to 6 decimals
        $float_fields = [ 'location_longitude', 'location_latitude', 'distance_to_nearest_airport', 'distance_to_nearest_railroad_station', 'rating' ];
        if ( in_array( $key, $float_fields ) ) {
            $old_float = round( floatval( $old_value ), 6 );
            $new_float = round( floatval( $new_value ), 6 );
            return $old_float !== $new_float;
        }

        // For strings, trim whitespace
        if ( is_string( $old_value ) && is_string( $new_value ) ) {
            return trim( $old_value ) !== trim( $new_value );
        }

        // Default comparison
        return $old_value != $new_value;
    }

    /**
     * Extract star rating from attributes
     */
    private function extract_stars_from_attributes( $attributes ) {
        $star_map = [
            'CATEGORY_TWO_STARS'           => 2,
            'CATEGORY_THREE_STARS'         => 3,
            'CATEGORY_THREE_STARS_SUPERIOR' => 3.5,
            'CATEGORY_FOUR_STARS'          => 4,
            'CATEGORY_FOUR_STARS_SUPERIOR' => 4.5,
            'CATEGORY_FIVE_STARS'          => 5,
            'CATEGORY_FIVE_STARS_SUPERIOR' => 5.5,
        ];

        foreach ( $attributes as $attr ) {
            $attrName = is_object( $attr ) ? ( $attr->attribute ?? '' ) : ( $attr['attribute'] ?? '' );
            if ( isset( $star_map[ $attrName ] ) ) {
                return $star_map[ $attrName ];
            }
        }

        return 0;
    }

    /**
     * Extract texts by type, preferring German
     */
    private function extract_texts_by_type( $texts ) {
        $result = [
            'HOTEL_DESCRIPTION' => '',
            'ARRIVAL_CAR'       => '',
            'ARRIVAL_FLIGHT'    => '',
            'ARRIVAL_TRAIN'     => '',
        ];

        // First pass: get German texts
        foreach ( $texts as $text ) {
            $type = is_object( $text ) ? ( $text->type ?? '' ) : ( $text['type'] ?? '' );
            $lang = is_object( $text ) ? ( $text->language ?? '' ) : ( $text['language'] ?? '' );
            $details = is_object( $text ) ? ( $text->details ?? '' ) : ( $text['details'] ?? '' );

            if ( $lang === 'de' && isset( $result[ $type ] ) && empty( $result[ $type ] ) ) {
                $result[ $type ] = $details;
            }
        }

        // Second pass: fill in any empty ones with any language
        foreach ( $texts as $text ) {
            $type = is_object( $text ) ? ( $text->type ?? '' ) : ( $text['type'] ?? '' );
            $details = is_object( $text ) ? ( $text->details ?? '' ) : ( $text['details'] ?? '' );

            if ( isset( $result[ $type ] ) && empty( $result[ $type ] ) ) {
                $result[ $type ] = $details;
            }
        }

        return $result;
    }

    /**
     * Build full address string
     */
    private function build_full_address( $hotel ) {
        $parts = array_filter( [
            $hotel->businessAddress1 ?? '',
            $hotel->businessAddress2 ?? '',
            $hotel->businessAddress3 ?? '',
            $hotel->businessAddress4 ?? '',
        ] );

        $cityLine = array_filter( [
            $hotel->businessZip ?? '',
            $hotel->businessCity ?? '',
        ] );

        if ( ! empty( $cityLine ) ) {
            $parts[] = implode( ' ', $cityLine );
        }

        if ( ! empty( $hotel->businessCountry ) ) {
            $parts[] = $hotel->businessCountry;
        }

        return implode( ', ', $parts );
    }

    /**
     * Extract amenities from attributes for easier filtering
     */
    private function extract_amenities( $attributes ) {
        $amenities = [
            'room'     => [],
            'design'   => [],
            'activity' => [],
            'wellness' => [],
            'facility' => [],
            'ecolabel' => [],
        ];

        foreach ( $attributes as $attr ) {
            $attrName = is_object( $attr ) ? ( $attr->attribute ?? '' ) : ( $attr['attribute'] ?? '' );

            if ( strpos( $attrName, 'ROOM_' ) === 0 ) {
                $amenities['room'][] = $attrName;
            } elseif ( strpos( $attrName, 'DESIGN_' ) === 0 ) {
                $amenities['design'][] = $attrName;
            } elseif ( strpos( $attrName, 'ACTIVITY_' ) === 0 ) {
                $amenities['activity'][] = $attrName;
            } elseif ( strpos( $attrName, 'WELLNESS_' ) === 0 ) {
                $amenities['wellness'][] = $attrName;
            } elseif ( strpos( $attrName, 'HOTELFACILITY_' ) === 0 ) {
                $amenities['facility'][] = $attrName;
            } elseif ( strpos( $attrName, 'ECOLABEL_' ) === 0 ) {
                $amenities['ecolabel'][] = $attrName;
            }
        }

        return $amenities;
    }

    /**
     * Process a single image for a hotel (one-by-one approach for WP Engine)
     * CRITICAL: This function is designed to NEVER crash the sync - all errors are caught and logged
     * @param int $post_id WordPress post ID
     * @param object $hotel Hotel object from API
     * @param int $image_index Index of the image to process (0-based)
     * @return array ['completed' => bool, 'next_index' => int, 'downloaded' => int, 'skipped' => int, 'error' => string|null]
     */
    private function process_single_image( $post_id, $hotel, $image_index ) {
        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H4', 'location' => 'process_single_image:entry', 'message' => 'process_single_image started', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $image_index, 'total_images' => count( $hotel->medias ?? [] ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion
        
        if ( empty( $hotel->medias ) || $image_index >= count( $hotel->medias ) ) {
            return [ 'completed' => true, 'next_index' => $image_index, 'downloaded' => 0, 'skipped' => 0, 'error' => null ];
        }
        
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        
        $media = $hotel->medias[ $image_index ];
        $downloaded = 0;
        $skipped = 0;
        $error_message = null;
        
        try {
            $image_url = $media->previewUrl ?? $media->url;
            if ( empty( $image_url ) ) {
                $error_message = 'Empty image URL';
                $this->log( 'error', "⚠️ Image {$image_index} skipped: Empty URL", $hotel->businessName ?? '' );
                return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
            }
            
            $original_url = $image_url;
            $image_url = $this->encode_image_url( $image_url );
            
            // Check if image already exists
            try {
                $existing = get_posts( [
                    'post_type'      => 'attachment',
                    'meta_query'     => [
                        [
                            'key'   => '_seminargo_source_url',
                            'value' => $image_url,
                        ],
                    ],
                    'posts_per_page' => 1,
                ] );
            } catch ( Exception $e ) {
                $error_message = 'Database query error: ' . $e->getMessage();
                $this->log( 'error', "⚠️ Image {$image_index} error: {$error_message}", $hotel->businessName ?? '' );
                return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
            }
            
            if ( ! empty( $existing ) ) {
                try {
                    $attachment_id = $existing[0]->ID;
                    $attachment_file = get_attached_file( $attachment_id );
                    $attachment_exists = $attachment_file && file_exists( $attachment_file );
                    
                    if ( ! $attachment_exists ) {
                        // Orphaned attachment - delete and re-download
                        wp_delete_attachment( $attachment_id, true );
                        $existing = [];
                    } else {
                        // File exists - skip
                        $skipped = 1;
                        $attachment_id = $existing[0]->ID;
                    }
                } catch ( Exception $e ) {
                    $error_message = 'Attachment check error: ' . $e->getMessage();
                    $this->log( 'error', "⚠️ Image {$image_index} error: {$error_message}", $hotel->businessName ?? '' );
                    // Continue to download attempt
                    $existing = [];
                }
            }
            
            // Download if needed - with timeout protection
            if ( empty( $existing ) ) {
                $download_urls = [ $image_url ];
                if ( $original_url !== $image_url ) {
                    $download_urls[] = $original_url;
                }
                
                $tmp = false;
                $download_success = false;
                
                foreach ( $download_urls as $try_url ) {
                    try {
                        // Set a timeout for download_url (30 seconds max per image)
                        $download_start = time();
                        $timeout = 30; // 30 second timeout per image download
                        
                        // Use wp_remote_get with timeout instead of download_url for better control
                        $response = wp_remote_get( $try_url, [
                            'timeout' => $timeout,
                            'sslverify' => false,
                            'redirection' => 5,
                        ] );
                        
                        if ( is_wp_error( $response ) ) {
                            $error_message = 'Download error: ' . $response->get_error_message();
                            continue; // Try next URL
                        }
                        
                        $response_code = wp_remote_retrieve_response_code( $response );
                        if ( $response_code !== 200 ) {
                            $error_message = "Download failed: HTTP {$response_code}";
                            continue; // Try next URL
                        }
                        
                        $body = wp_remote_retrieve_body( $response );
                        if ( empty( $body ) ) {
                            $error_message = 'Download failed: Empty response';
                            continue; // Try next URL
                        }
                        
                        // Save to temp file
                        $tmp = wp_tempnam( 'seminargo_image_' );
                        if ( $tmp === false ) {
                            $error_message = 'Failed to create temp file';
                            continue;
                        }
                        
                        file_put_contents( $tmp, $body );
                        $image_url = $try_url;
                        $download_success = true;
                        break;
                        
                    } catch ( Exception $e ) {
                        $error_message = 'Download exception: ' . $e->getMessage();
                        if ( $tmp && file_exists( $tmp ) ) {
                            @unlink( $tmp );
                            $tmp = false;
                        }
                        continue; // Try next URL
                    }
                }
                
                if ( ! $download_success || $tmp === false ) {
                    $error_message = $error_message ?? 'Download failed: All URLs failed';
                    $this->log( 'error', "⚠️ Image {$image_index} download failed: {$error_message}", $hotel->businessName ?? '' );
                    // #region agent log
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H4', 'location' => 'process_single_image:download_failed', 'message' => 'Image download failed - continuing', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $image_index, 'error' => $error_message ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
                }
                
                // Move file and create attachment - with error handling
                try {
                    // Add filters to bypass WordPress file type validation
                    add_filter( 'upload_mimes', [ $this, 'allow_all_image_mimes' ], 999 );
                    add_filter( 'wp_check_filetype_and_ext', [ $this, 'bypass_filetype_check' ], 999, 4 );

                    // Detect file type from file content FIRST (most reliable)
                    $filetype = [ 'ext' => '', 'type' => '' ];
                    if ( function_exists( 'finfo_open' ) ) {
                        $finfo = finfo_open( FILEINFO_MIME_TYPE );
                        $mime = finfo_file( $finfo, $tmp );
                        finfo_close( $finfo );
                        
                        $mime_to_ext = [ 
                            'image/jpeg' => 'jpg', 
                            'image/png' => 'png', 
                            'image/gif' => 'gif', 
                            'image/webp' => 'webp',
                            'image/svg+xml' => 'svg',
                            'image/bmp' => 'bmp',
                            'image/tiff' => 'tiff',
                        ];
                        
                        if ( isset( $mime_to_ext[ $mime ] ) ) {
                            $filetype['ext'] = $mime_to_ext[ $mime ];
                            $filetype['type'] = $mime;
                        }
                    }
                    
                    // Fallback to WordPress detection if finfo failed
                    if ( empty( $filetype['type'] ) ) {
                        $wp_filetype = wp_check_filetype_and_ext( $tmp, basename( $image_url ) );
                        if ( ! empty( $wp_filetype['type'] ) && ! empty( $wp_filetype['ext'] ) ) {
                            $filetype = $wp_filetype;
                        }
                    }

                    // Ensure we have a valid file type - log if we can't detect
                    if ( empty( $filetype['type'] ) || empty( $filetype['ext'] ) ) {
                        // Log the issue for debugging
                        $this->log( 'error', "⚠️ Image {$image_index} MIME detection failed - URL: " . substr( $image_url, 0, 100 ), $hotel->businessName ?? '' );
                        // Fallback: assume JPEG if we can't detect
                        $filetype['ext'] = 'jpg';
                        $filetype['type'] = 'image/jpeg';
                    }
                    
                    // Get base filename and ensure it has the correct extension
                    $image_name = basename( $media->url ?? $media->path );
                    $image_name = sanitize_file_name( $image_name );
                    
                    // Remove any existing extension
                    $image_name = preg_replace( '/\.[^.]+$/', '', $image_name );
                    // Add the correct extension based on detected MIME type
                    $image_name .= '.' . $filetype['ext'];
                    
                    // Log before upload for debugging
                    $file_size = filesize( $tmp );
                    $this->log( 'info', "📤 Uploading image {$image_index}: {$image_name} ({$filetype['type']}, {$file_size} bytes)", $hotel->businessName ?? '' );
                    
                    $file_contents = file_get_contents( $tmp );
                    $upload = wp_upload_bits( $image_name, null, $file_contents );
                    @unlink( $tmp );
                    
                    // Remove filters after upload
                    remove_filter( 'upload_mimes', [ $this, 'allow_all_image_mimes' ], 999 );
                    remove_filter( 'wp_check_filetype_and_ext', [ $this, 'bypass_filetype_check' ], 999 );
                    
                    if ( $upload['error'] ) {
                        $error_message = 'Upload error: ' . $upload['error'];
                        // Enhanced error logging
                        $this->log( 'error', "⚠️ Image {$image_index} upload failed: {$error_message}", $hotel->businessName ?? '' );
                        $this->log( 'error', "   Filename: {$image_name}, MIME: {$filetype['type']}, Size: {$file_size}, URL: " . substr( $image_url, 0, 100 ), $hotel->businessName ?? '' );
                        return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
                    }
                    
                    $attachment_data = [
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => sanitize_file_name( pathinfo( $image_name, PATHINFO_FILENAME ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];
                    
                    $attachment_id = wp_insert_attachment( $attachment_data, $upload['file'], $post_id );
                    
                    if ( is_wp_error( $attachment_id ) ) {
                        $error_message = 'Attachment creation error: ' . $attachment_id->get_error_message();
                        $this->log( 'error', "⚠️ Image {$image_index} attachment failed: {$error_message}", $hotel->businessName ?? '' );
                        return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
                    }
                    
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
                    wp_update_attachment_metadata( $attachment_id, $attach_data );
                    update_post_meta( $attachment_id, '_seminargo_source_url', $image_url );
                    
                    $downloaded = 1;
                    
                } catch ( Exception $e ) {
                    // Remove filters on error
                    remove_filter( 'upload_mimes', [ $this, 'allow_all_image_mimes' ], 999 );
                    remove_filter( 'wp_check_filetype_and_ext', [ $this, 'bypass_filetype_check' ], 999 );
                    
                    $error_message = 'File processing error: ' . $e->getMessage();
                    if ( $tmp && file_exists( $tmp ) ) {
                        @unlink( $tmp );
                    }
                    $this->log( 'error', "⚠️ Image {$image_index} processing failed: {$error_message}", $hotel->businessName ?? '' );
                    return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
                }
            }
            
            // Set featured image on first image - with error handling
            if ( $image_index === 0 && isset( $attachment_id ) ) {
                try {
                    set_post_thumbnail( $post_id, $attachment_id );
                } catch ( Exception $e ) {
                    // Non-critical error - log but continue
                    $this->log( 'error', "⚠️ Failed to set featured image: " . $e->getMessage(), $hotel->businessName ?? '' );
                }
            }
            
            // Add to gallery - with error handling
            if ( isset( $attachment_id ) ) {
                try {
                    $gallery_ids = get_post_meta( $post_id, '_seminargo_gallery_ids', true );
                    if ( ! is_array( $gallery_ids ) ) {
                        $gallery_ids = [];
                    }
                    if ( ! in_array( $attachment_id, $gallery_ids ) ) {
                        $gallery_ids[] = $attachment_id;
                        update_post_meta( $post_id, '_seminargo_gallery_ids', $gallery_ids );
                    }
                } catch ( Exception $e ) {
                    // Non-critical error - log but continue
                    $this->log( 'error', "⚠️ Failed to update gallery: " . $e->getMessage(), $hotel->businessName ?? '' );
                }
            }
            
            // #region agent log
            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2', 'location' => 'process_single_image:success', 'message' => 'Image processed successfully', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $image_index, 'downloaded' => $downloaded, 'skipped' => $skipped ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => $downloaded, 'skipped' => $skipped, 'error' => null ];
            
        } catch ( Exception $e ) {
            // Catch-all for any unexpected errors
            $error_message = 'Unexpected error: ' . $e->getMessage();
            $this->log( 'error', "⚠️ Image {$image_index} unexpected error: {$error_message}", $hotel->businessName ?? '' );
            // #region agent log
            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_single_image:exception', 'message' => 'Exception caught - continuing', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $image_index, 'error' => $error_message, 'trace' => $e->getTraceAsString() ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
        }
    }

    /**
     * Process hotel images (legacy - processes all images at once)
     * @param int $post_id WordPress post ID
     * @param object $hotel Hotel object from API
     * @param int|null $request_start_time Start time of the request (for timeout checking)
     * @param int|null $timeout_threshold Timeout threshold in seconds (default: 40)
     * @return bool True if completed, false if timed out
     */
    private function process_hotel_images( $post_id, $hotel, $request_start_time = null, $timeout_threshold = 40 ) {
        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H4,H6', 'location' => 'process_hotel_images:entry', 'message' => 'process_hotel_images started', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'medias_count' => count( $hotel->medias ?? [] ), 'memory_before' => memory_get_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion
        
        if ( empty( $hotel->medias ) ) {
            // #region agent log
            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_hotel_images:early_exit', 'message' => 'Early exit - no medias', 'data' => [ 'post_id' => $post_id ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $total_images = count( $hotel->medias );
        $downloaded = 0;
        $skipped = 0;

        $first_image = true;
        $gallery_ids = [];
        $image_index = 0;
        $time_check_interval = 5; // Check time every 5 images

        foreach ( $hotel->medias as $media ) {
            $image_index++;
            
            // CRITICAL: Check time every N images to avoid timeout mid-hotel
            if ( $request_start_time !== null && $image_index % $time_check_interval === 0 ) {
                $elapsed = time() - $request_start_time;
                if ( $elapsed > $timeout_threshold ) {
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H7', 'location' => 'process_hotel_images:timeout_mid_hotel', 'message' => 'Timeout during image processing - breaking out', 'data' => [ 'post_id' => $post_id, 'image_index' => $image_index, 'total_images' => $total_images, 'images_processed' => $image_index - 1, 'elapsed_seconds' => $elapsed, 'wp_engine_limit' => 60, 'threshold' => $timeout_threshold, 'time_remaining' => 60 - $elapsed ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    return false; // Signal timeout - hotel partially processed
                }
            }
            
            // #region agent log
            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H4,H6', 'location' => 'process_hotel_images:image_start', 'message' => 'Processing image', 'data' => [ 'post_id' => $post_id, 'image_index' => $image_index, 'total_images' => $total_images, 'memory_before' => memory_get_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
            // #endregion
            
            try {
                $image_url = $media->previewUrl ?? $media->url;
                if ( empty( $image_url ) ) {
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_hotel_images:image_skip_empty', 'message' => 'Image skipped - empty URL', 'data' => [ 'image_index' => $image_index ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    continue;
                }

                // Store original URL for logging
                $original_url = $image_url;

                // URL encode the image URL to handle spaces and special characters
                $image_url = $this->encode_image_url( $image_url );

                // #region agent log
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'H1,H4,H6',
                    'location' => 'process_hotel_images:before_existence_check',
                    'message' => 'Before checking if image exists',
                    'data' => [
                        'image_index' => $image_index,
                        'post_id' => $post_id,
                        'original_url' => $original_url,
                        'encoded_url' => $image_url,
                        'urls_match' => $original_url === $image_url,
                    ],
                    'timestamp' => time() * 1000
                ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion

                // Check if image already exists (using encoded URL)
                $existing = get_posts( [
                    'post_type'      => 'attachment',
                    'meta_query'     => [
                        [
                            'key'   => '_seminargo_source_url',
                            'value' => $image_url,
                        ],
                    ],
                    'posts_per_page' => 1,
                ] );

                // #region agent log
                @file_put_contents( $log_path, json_encode( [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'H1,H3,H4,H6',
                    'location' => 'process_hotel_images:existence_check_result',
                    'message' => 'Existence check result',
                    'data' => [
                        'image_index' => $image_index,
                        'post_id' => $post_id,
                        'encoded_url_searched' => $image_url,
                        'found_attachments' => count( $existing ),
                        'attachment_id' => ! empty( $existing ) ? $existing[0]->ID : null,
                    ],
                    'timestamp' => time() * 1000
                ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion

                if ( ! empty( $existing ) ) {
                    $attachment_id = $existing[0]->ID;
                    $attachment_file = get_attached_file( $attachment_id );
                    $attachment_exists = $attachment_file && file_exists( $attachment_file );
                    
                    // CRITICAL FIX: If attachment exists in DB but file is missing, delete orphaned attachment
                    if ( ! $attachment_exists ) {
                        // #region agent log
                        @file_put_contents( $log_path, json_encode( [
                            'sessionId' => 'debug-session',
                            'runId' => 'run1',
                            'hypothesisId' => 'H2',
                            'location' => 'process_hotel_images:orphaned_attachment',
                            'message' => 'Found orphaned attachment - file missing, deleting and re-downloading',
                            'data' => [
                                'image_index' => $image_index,
                                'post_id' => $post_id,
                                'attachment_id' => $attachment_id,
                                'attachment_file' => $attachment_file,
                            ],
                            'timestamp' => time() * 1000
                        ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                        
                        // Delete orphaned attachment
                        wp_delete_attachment( $attachment_id, true );
                        $existing = []; // Clear so we proceed to download
                        $attachment_id = null; // Reset so new attachment ID will be set after download
                    } else {
                        // File exists - skip download
                        // #region agent log
                        @file_put_contents( $log_path, json_encode( [
                            'sessionId' => 'debug-session',
                            'runId' => 'run1',
                            'hypothesisId' => 'H4',
                            'location' => 'process_hotel_images:image_skip_exists',
                            'message' => 'Image skipped - already exists',
                            'data' => [
                                'image_index' => $image_index,
                                'attachment_id' => $attachment_id,
                            ],
                            'timestamp' => time() * 1000
                        ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                        
                    $skipped++;
                    }
                }
                
                // If no existing attachment (or it was orphaned and deleted), download it
                if ( empty( $existing ) ) {
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H6', 'location' => 'process_hotel_images:download_start', 'message' => 'Starting image download', 'data' => [ 'image_index' => $image_index, 'original_url' => $original_url, 'encoded_url' => $image_url, 'urls_to_try' => count( $original_url !== $image_url ? [ $image_url, $original_url ] : [ $image_url ] ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    
                    // Download image to temp file with retry logic
                    $tmp = false;
                    $download_urls = [ $image_url ]; // Try encoded URL first
                    
                    // If URL was modified by encoding, also try original as fallback
                    if ( $original_url !== $image_url ) {
                        $download_urls[] = $original_url;
                    }

                    $last_error = null;
                    $download_attempt = 0;
                    $total_download_start = microtime( true );
                    foreach ( $download_urls as $try_url ) {
                        $download_attempt++;
                        
                        // #region agent log
                        $download_start = microtime( true );
                        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2', 'location' => 'process_hotel_images:download_url_call', 'message' => 'Calling download_url', 'data' => [ 'image_index' => $image_index, 'attempt' => $download_attempt, 'url' => $try_url, 'memory_before' => memory_get_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                        
                        $tmp = download_url( $try_url );
                        
                        // #region agent log
                        $download_time = ( microtime( true ) - $download_start ) * 1000;
                        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H5', 'location' => 'process_hotel_images:download_url_result', 'message' => 'download_url result', 'data' => [ 'image_index' => $image_index, 'attempt' => $download_attempt, 'url' => $try_url, 'download_time_ms' => round( $download_time, 2 ), 'is_wp_error' => is_wp_error( $tmp ), 'error_message' => is_wp_error( $tmp ) ? $tmp->get_error_message() : null, 'error_code' => is_wp_error( $tmp ) ? $tmp->get_error_code() : null, 'tmp_file' => ( $tmp && ! is_wp_error( $tmp ) ) ? $tmp : null, 'memory_after' => memory_get_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                        
                        if ( ! is_wp_error( $tmp ) ) {
                            // Success - update image_url to the one that worked
                            $image_url = $try_url;
                            break;
                } else {
                            $last_error = $tmp;
                            $tmp = false; // Reset for next attempt
                        }
                    }
                    $total_download_time = ( microtime( true ) - $total_download_start ) * 1000;
                    
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H5', 'location' => 'process_hotel_images:download_complete', 'message' => 'Total download time', 'data' => [ 'image_index' => $image_index, 'total_download_time_ms' => round( $total_download_time, 2 ), 'download_attempts' => $download_attempt, 'success' => ! is_wp_error( $tmp ) && $tmp !== false ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion

                    if ( is_wp_error( $tmp ) || $tmp === false ) {
                        $error_msg = $last_error instanceof WP_Error ? $last_error->get_error_message() : ( $tmp instanceof WP_Error ? $tmp->get_error_message() : 'Unknown error' );
                        
                        // #region agent log
                        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H4,H6', 'location' => 'process_hotel_images:download_failed', 'message' => 'Image download failed', 'data' => [ 'image_index' => $image_index, 'url' => $image_url, 'error' => $error_msg, 'error_code' => $last_error instanceof WP_Error ? $last_error->get_error_code() : null ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                        
                        $this->log( 'error', 'Failed to download image: ' . $image_url . ' (Error: ' . $error_msg . ')', $hotel->businessName );
                        continue;
                    }

                    // Detect MIME type from actual file content
                    $filetype = wp_check_filetype_and_ext( $tmp, basename( $image_url ) );

                    // If MIME detection failed, use finfo to detect from file content
                    if ( ! $filetype['type'] && function_exists( 'finfo_open' ) ) {
                        $finfo = finfo_open( FILEINFO_MIME_TYPE );
                        $mime = finfo_file( $finfo, $tmp );
                        finfo_close( $finfo );

                        // Map MIME type to extension
                        $mime_to_ext = [
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/gif'  => 'gif',
                            'image/webp' => 'webp',
                        ];

                        if ( isset( $mime_to_ext[ $mime ] ) ) {
                            $filetype['ext'] = $mime_to_ext[ $mime ];
                            $filetype['type'] = $mime;
                        }
                    }

                    // Get base filename from URL
                    $image_name = basename( $media->url ?? $media->path );
                    $image_name = sanitize_file_name( $image_name );

                    // Add extension if missing
                    if ( ! empty( $filetype['ext'] ) ) {
                        // Remove any existing extension first
                        $image_name = preg_replace( '/\.[^.]+$/', '', $image_name );
                        // Add correct extension
                        $image_name .= '.' . $filetype['ext'];
                    } else {
                        // Fallback to .jpg if we still can't detect
                        if ( ! preg_match( '/\.(jpg|jpeg|png|gif|webp)$/i', $image_name ) ) {
                            $image_name .= '.jpg';
                            $filetype['type'] = 'image/jpeg';
                        }
                    }

                    // BYPASS WordPress security - move file directly to uploads
                    $upload_dir = wp_upload_dir();
                    $filename = wp_unique_filename( $upload_dir['path'], $image_name );
                    $filepath = $upload_dir['path'] . '/' . $filename;

                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H6', 'location' => 'process_hotel_images:file_move_start', 'message' => 'Moving file to uploads', 'data' => [ 'image_index' => $image_index, 'tmp_file' => $tmp, 'filepath' => $filepath, 'upload_dir_writable' => is_writable( $upload_dir['path'] ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion

                    // Move temp file to uploads directory
                    if ( ! rename( $tmp, $filepath ) ) {
                        // #region agent log
                        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H6', 'location' => 'process_hotel_images:file_move_failed', 'message' => 'Failed to move file', 'data' => [ 'image_index' => $image_index, 'tmp_file' => $tmp, 'filepath' => $filepath, 'error' => error_get_last() ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                        // #endregion
                        
                        @unlink( $tmp );
                        $this->log( 'error', 'Failed to move image file', $hotel->businessName );
                        continue;
                    }
                    
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H2,H6', 'location' => 'process_hotel_images:attachment_create_start', 'message' => 'Creating attachment', 'data' => [ 'image_index' => $image_index, 'filepath' => $filepath, 'mime_type' => $filetype['type'], 'memory_before' => memory_get_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion

                    // Create attachment directly (bypasses upload security)
                    $attachment_id = wp_insert_attachment( [
                        'guid'           => $upload_dir['url'] . '/' . $filename,
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
                        'post_status'    => 'inherit',
                    ], $filepath, $post_id );

                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H2,H4,H6', 'location' => 'process_hotel_images:attachment_create_result', 'message' => 'Attachment creation result', 'data' => [ 'image_index' => $image_index, 'attachment_id' => is_wp_error( $attachment_id ) ? null : $attachment_id, 'is_wp_error' => is_wp_error( $attachment_id ), 'error' => is_wp_error( $attachment_id ) ? $attachment_id->get_error_message() : null, 'memory_after' => memory_get_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion

                    if ( is_wp_error( $attachment_id ) ) {
                        @unlink( $filepath );
                        $this->log( 'error', 'Failed to create attachment: ' . $attachment_id->get_error_message(), $hotel->businessName );
                        continue;
                    }

                    // Generate thumbnails
                    $attach_data = wp_generate_attachment_metadata( $attachment_id, $filepath );
                    wp_update_attachment_metadata( $attachment_id, $attach_data );

                    // Store source URL for deduplication
                    update_post_meta( $attachment_id, '_seminargo_source_url', $image_url );
                    
                    // #region agent log
                    $stored_url_after = get_post_meta( $attachment_id, '_seminargo_source_url', true );
                    @file_put_contents( $log_path, json_encode( [
                        'sessionId' => 'debug-session',
                        'runId' => 'run1',
                        'hypothesisId' => 'H1,H4,H6',
                        'location' => 'process_hotel_images:meta_stored',
                        'message' => 'Stored source URL meta',
                        'data' => [
                            'image_index' => $image_index,
                            'attachment_id' => $attachment_id,
                            'url_stored' => $image_url,
                            'url_retrieved' => $stored_url_after,
                            'urls_match' => $image_url === $stored_url_after,
                        ],
                        'timestamp' => time() * 1000
                    ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    
                    $downloaded++;
                }

                $gallery_ids[] = $attachment_id;

                // Set first image as featured
                if ( $first_image ) {
                    // #region agent log
                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H2,H6', 'location' => 'process_hotel_images:set_thumbnail', 'message' => 'Setting featured image', 'data' => [ 'image_index' => $image_index, 'post_id' => $post_id, 'attachment_id' => $attachment_id ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                    // #endregion
                    
                    set_post_thumbnail( $post_id, $attachment_id );
                    $first_image = false;
                }
                
                // #region agent log
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H4', 'location' => 'process_hotel_images:image_complete', 'message' => 'Image processing complete', 'data' => [ 'image_index' => $image_index, 'total_images' => $total_images, 'downloaded' => $downloaded, 'skipped' => $skipped, 'memory_usage' => memory_get_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion

            } catch ( Exception $e ) {
                // #region agent log
                $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_hotel_images:image_exception', 'message' => 'Exception processing image', 'data' => [ 'image_index' => $image_index, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString() ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                // #endregion
                
                $this->log( 'error', 'Image processing error: ' . $e->getMessage(), $hotel->businessName );
            }
        }

        // Save gallery IDs and log summary
        if ( ! empty( $gallery_ids ) ) {
            update_post_meta( $post_id, 'gallery', $gallery_ids );
            if ( function_exists( 'update_field' ) ) {
                update_field( 'gallery', $gallery_ids, $post_id );
            }

            // Log image processing summary - ALWAYS log to show progress
            if ( $downloaded > 0 ) {
                $this->log( 'success', "📸 {$hotel->businessName}: {$downloaded} NEW images downloaded, {$skipped} already existed ({$total_images} total)", $hotel->businessName );
            } elseif ( $skipped > 0 ) {
                $this->log( 'info', "✓ {$hotel->businessName}: All {$skipped} images already exist, skipped download", $hotel->businessName );
            }
        }
        
        // #region agent log
        // #region agent log
        $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
        @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H4,H6', 'location' => 'process_hotel_images:exit', 'message' => 'process_hotel_images completed', 'data' => [ 'post_id' => $post_id, 'total_images' => $total_images, 'downloaded' => $downloaded, 'skipped' => $skipped, 'gallery_ids_count' => count( $gallery_ids ), 'memory_usage' => memory_get_usage( true ), 'peak_memory' => memory_get_peak_usage( true ) ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
        // #endregion
        
        return true; // Successfully completed all images
    }

    /**
     * URL encode image URLs to handle spaces and special characters
     * Fixes broken UTF-8 encoding from API (e.g., %C3_ should be %C3%9F for ß)
     */
    private function encode_image_url( $url ) {
        if ( empty( $url ) ) {
            return $url;
        }

        $parsed = parse_url( $url );
        if ( ! $parsed || ! isset( $parsed['path'] ) ) {
            return $url;
        }

        $path = $parsed['path'];

        // Fix broken UTF-8 encoding patterns from API
            // The API sends broken encoding where second byte is replaced with underscore
        // Common patterns:
        // - %C3_en = "ßen" (außen, Außenansicht) -> should be %C3%9Fen
        // - %C3_ = "ß" -> should be %C3%9F
        // - %C3%BC_ = "ü" -> should be %C3%BC
        
        // Fix %C3_ pattern (most common - represents ß)
        // This handles: au%C3_en, Au%C3_enansicht, etc.
        $path = preg_replace( '/%C3_([a-z])/i', '%C3%9F$1', $path );
        
        // Fix standalone %C3_ (not followed by letter)
        $path = str_replace( '%C3_', '%C3%9F', $path );

        // Fix other broken patterns with trailing underscore
        $path = str_replace( '%C3%BC_', '%C3%BC', $path ); // ü
        $path = str_replace( '%C3%A4_', '%C3%A4', $path ); // ä
        $path = str_replace( '%C3%B6_', '%C3%B6', $path ); // ö

        // Note: We only fix known broken patterns (German characters above)
        // Generic %XX_ patterns are left as-is to avoid corrupting URLs with other character encodings
        // If other broken patterns are found, they should be added as specific fixes above

        // Now properly encode any remaining unencoded characters
        // Split path into segments to preserve slashes
        $path_segments = explode( '/', $path );
        $encoded_segments = array_map( function( $segment ) {
            if ( empty( $segment ) ) {
                return $segment;
            }

            // If segment contains encoded characters, try to decode and re-encode properly
            if ( preg_match( '/%[0-9A-Fa-f]{2}/', $segment ) ) {
                // Try to decode - if it decodes successfully, re-encode
                $decoded = @rawurldecode( $segment );
                if ( $decoded !== false && $decoded !== $segment ) {
                    // Successfully decoded, now re-encode properly
            return rawurlencode( $decoded );
                }
                // If decode failed or returned same, check if it's already properly encoded
                // Properly encoded segments should decode without errors
                if ( ! preg_match( '/[^A-Za-z0-9._~%\-]/', $decoded ) ) {
                    return $segment; // Already properly encoded
                }
            }

            // Encode any unencoded special characters
            return rawurlencode( $segment );
        }, $path_segments );
        
        $encoded_path = implode( '/', $encoded_segments );

        // Rebuild URL
        $encoded_url = $parsed['scheme'] . '://' . $parsed['host'];
        if ( isset( $parsed['port'] ) ) {
            $encoded_url .= ':' . $parsed['port'];
        }
        $encoded_url .= $encoded_path;
        if ( isset( $parsed['query'] ) ) {
            $encoded_url .= '?' . $parsed['query'];
        }

        return $encoded_url;
    }

    /**
     * Run automatic batch import (called by cron)
     * Imports hotels in batches to handle large datasets
     */
    public function run_auto_import_batch() {
        // Check if auto-import is enabled
        if ( ! get_option( $this->auto_import_enabled_option, false ) ) {
            return;
        }

        // Use the same batched import system as manual "Fetch Now"
        // This ensures consistency and handles all hotels + images
        $existing_progress = get_option( 'seminargo_batched_import_progress', null );

        // If no import is running, start a new one
        if ( ! $existing_progress || $existing_progress['status'] !== 'running' ) {
            $this->log( 'info', '🤖 Auto-import: Starting new batched import...' );
            $this->flush_logs();

            // Initialize progress (same as manual import)
            $progress = [
                'status' => 'running',
                'phase' => 'phase1',
                'offset' => 0,
                'total_hotels' => 0,
                'hotels_processed' => 0,
                'images_processed' => 0,
                'created' => 0,
                'updated' => 0,
                'drafted' => 0,
                'errors' => 0,
                'start_time' => time(),
            ];

            update_option( 'seminargo_batched_import_progress', $progress, false );

            // Mark old auto-import as complete
            update_option( $this->auto_import_progress_option, [
                'offset' => 0,
                'total_imported' => 0,
                'is_complete' => true,
                'last_run' => time(),
            ] );

            // Schedule first batch immediately
            wp_schedule_single_event( time(), 'seminargo_process_import_batch' );
            
            // Force WP Cron to spawn on production (WP Cron may not fire reliably)
            if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
                spawn_cron();
            }
        } else {
            // Import already running, let it continue
            $this->log( 'info', '🤖 Auto-import: Batched import already in progress, skipping...' );
        }
    }

    /**
     * Fetch a specific batch of hotels from API
     */
    private function fetch_hotels_batch_from_api( $offset, $limit ) {
        $query = '{
            hotelList(skip: ' . $offset . ', limit: ' . $limit . ') {
                id
                slug
                refCode
                name
                businessName
                businessAddress1
                businessAddress2
                businessAddress3
                businessAddress4
                businessEmail
                businessZip
                businessCity
                businessCountry
                locationLongitude
                locationLatitude
                distanceToNearestAirport
                distanceToNearestRailroadStation
                rating
                maxCapacityRooms
                maxCapacityPeople
                hasActivePartnerContract
                texts {
                    id
                    details
                    type
                    language
                }
                attributes {
                    id
                    attribute
                }
                medias {
                    id
                    name
                    mimeType
                    width
                    height
                    format
                    path
                    url
                    previewUrl
                }
                integrations {
                    directBooking
                }
                spaceId
                space {
                    name
                }
                meetingRooms {
                    id
                    name
                    area
                    capacityUForm
                    capacityTheater
                    capacityParlament
                    capacityCircle
                    capacityBankett
                    capacityCocktail
                    capacityBlock
                    facilityId
                    facility {
                        id
                        sku
                        name
                        header
                        details
                    }
                }
            }
        }';

        $response = wp_remote_post( $this->api_url, [
            'body'    => json_encode( [ 'query' => $query ] ),
            'headers' => [ 'Content-Type' => 'application/json' ],
            'timeout' => 120,
        ] );

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'API Error: ' . $response->get_error_message() );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( isset( $data->errors ) ) {
            throw new Exception( 'GraphQL Error: ' . json_encode( $data->errors ) );
        }

        return $data->data->hotelList ?? [];
    }

    /**
     * AJAX: Toggle auto-import on/off
     */
    public function ajax_toggle_auto_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $enabled = isset( $_POST['enabled'] ) && $_POST['enabled'] === 'true';
        update_option( $this->auto_import_enabled_option, $enabled );

        // Re-register cron (will schedule or unschedule based on $enabled)
        $this->register_cron();

        wp_send_json_success( [
            'enabled' => $enabled,
            'message' => $enabled ? 'Auto-import enabled' : 'Auto-import disabled',
        ] );
    }

    /**
     * AJAX: Reset auto-import progress
     */
    public function ajax_reset_auto_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        update_option( $this->auto_import_progress_option, [
            'offset' => 0,
            'total_imported' => 0,
            'is_complete' => false,
            'last_run' => 0,
        ] );

        wp_send_json_success( [ 'message' => 'Progress reset' ] );
    }

    /**
     * AJAX: Get auto-import status
     */
    public function ajax_get_auto_import_status() {
        $enabled = get_option( $this->auto_import_enabled_option, false );
        $progress = get_option( $this->auto_import_progress_option, [
            'offset' => 0,
            'total_imported' => 0,
            'is_complete' => false,
            'last_run' => 0,
        ] );
        $next_run = wp_next_scheduled( 'seminargo_hotels_cron' );

        wp_send_json_success( [
            'enabled' => $enabled,
            'progress' => $progress,
            'next_run' => $next_run,
            'next_run_formatted' => $next_run ? date_i18n( 'Y-m-d H:i:s', $next_run ) : 'Not scheduled',
            'last_run_formatted' => ! empty( $progress['last_run'] ) && $progress['last_run'] > 0 ? date_i18n( 'Y-m-d H:i:s', $progress['last_run'] ) : null,
        ] );
    }
}

// Initialize the importer
new Seminargo_Hotel_Importer();

// Cleanup cron on theme switch
add_action( 'switch_theme', function() {
    wp_clear_scheduled_hook( 'seminargo_hotels_cron' );
} );
