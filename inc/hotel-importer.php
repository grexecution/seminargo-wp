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

    private $api_url;
    private $finder_base_url;
    private $log_option = 'seminargo_hotels_import_log';
    private $last_import_option = 'seminargo_hotels_last_import';
    private $imported_ids_option = 'seminargo_hotels_imported_ids';
    private $auto_import_enabled_option = 'seminargo_auto_import_enabled';
    private $auto_import_progress_option = 'seminargo_auto_import_progress';
    private $last_full_sync_option = 'seminargo_last_full_sync_time'; // Track when last full sync occurred
    private $sync_history_option = 'seminargo_sync_history'; // Stores history of past syncs

    // Log batching to avoid database thrashing
    private $log_batch = [];
    private $log_batch_size = 5; // Flush logs every 5 entries for real-time visibility

    public function __construct() {
        // Initialize URLs from centralized configuration
        $this->api_url = seminargo_get_api_url();
        $this->finder_base_url = seminargo_get_finder_url();
        
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
        add_action( 'wp_ajax_seminargo_force_reschedule_cron', [ $this, 'ajax_force_reschedule_cron' ] );
        add_action( 'wp_ajax_seminargo_get_sync_history', [ $this, 'ajax_get_sync_history' ] );
        add_action( 'wp_ajax_seminargo_stop_import', [ $this, 'ajax_stop_import' ] );
        add_action( 'wp_ajax_seminargo_resume_import', [ $this, 'ajax_resume_import' ] );
        add_action( 'wp_ajax_seminargo_execute_batch_direct', [ $this, 'ajax_execute_batch_direct' ] );
        add_action( 'wp_ajax_nopriv_seminargo_execute_batch_direct', [ $this, 'ajax_execute_batch_direct' ] ); // Allow non-auth for async call
        add_action( 'wp_ajax_seminargo_find_duplicates', [ $this, 'ajax_find_duplicates' ] );
        add_action( 'wp_ajax_seminargo_cleanup_duplicates', [ $this, 'ajax_cleanup_duplicates' ] );
        add_action( 'wp_ajax_seminargo_delete_hotel_images', [ $this, 'ajax_delete_hotel_images' ] );
        add_action( 'wp_ajax_seminargo_save_brevo_api_key', [ $this, 'ajax_save_brevo_api_key' ] );

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

        // Add custom columns to hotels list
        add_filter( 'manage_hotel_posts_columns', [ $this, 'add_hotel_columns' ] );
        add_action( 'manage_hotel_posts_custom_column', [ $this, 'render_featured_column' ], 10, 2 );

        // Column styles
        add_action( 'admin_head', [ $this, 'hotel_column_styles' ] );
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
            </style>';
        }
    }

    /**
     * Add custom columns to hotels list
     */
    public function add_hotel_columns( $columns ) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['hotel_image'] = 'Bild';
        $new_columns['title'] = $columns['title'];
        $new_columns['hotel_location'] = 'Standort';
        $new_columns['hotel_rating'] = 'Bewertung';
        $new_columns['hotel_rooms'] = 'Räume';
        $new_columns['hotel_capacity'] = 'Kapazität';
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
        }
    }


    /**
     * Add custom cron interval
     */
    public function add_cron_interval( $schedules ) {
        $schedules['every_four_hours'] = [
            'interval' => 14400, // 4 hours in seconds
            'display'  => __( 'Every 4 Hours (6x daily)', 'seminargo' ),
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
            // Schedule to run every 4 hours when auto-import is enabled (6x daily)
            // Only schedule if NOT already scheduled (avoid rescheduling on every page load)
            $next_time = time() + ( 4 * HOUR_IN_SECONDS ); // 4 hours from now
            wp_schedule_event( $next_time, 'every_four_hours', 'seminargo_hotels_cron' );
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
            'hotel_debug_image_urls',
            '🔍 ' . __( 'Debug: API Image URLs', 'seminargo' ),
            [ $this, 'render_debug_image_urls_meta_box' ],
            'hotel',
            'normal',
            'low'
        );

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
            .hotel-media-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .hotel-media-thumb { position: relative; aspect-ratio: 1; overflow: hidden; border-radius: 6px; border: 2px solid #e5e7eb; background: #f9fafb; transition: all 0.2s; }
            .hotel-media-thumb:hover { border-color: #AC2A6E; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            .hotel-media-thumb.featured { border-color: #AC2A6E; }
            .hotel-media-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s; }
            .hotel-media-thumb:hover img { transform: scale(1.05); }
            .hotel-media-badge { position: absolute; top: 4px; right: 4px; background: rgba(172, 42, 110, 0.95); color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
        </style>';

        // API Images (show previews from API)
        echo '<p style="margin-bottom: 12px; font-size: 14px; font-weight: 600; color: #374151;">🖼️ ' . sprintf( esc_html__( '%d Images from API', 'seminargo' ), count( $medias ) ) . '</p>';

        if ( ! empty( $medias ) ) {
            echo '<div class="hotel-media-gallery">';
            foreach ( array_slice( $medias, 0, 12 ) as $index => $media ) {
                $preview_url = $media['previewUrl'] ?? $media['url'] ?? '';
                $name = $media['name'] ?? 'Image ' . $index;

                if ( $preview_url ) {
                    echo '<a href="' . esc_url( $preview_url ) . '" target="_blank" class="hotel-media-thumb' . ( $index === 0 ? ' featured' : '' ) . '" title="' . esc_attr( $name ) . ' - Click to view full size">';
                    if ( $index === 0 ) {
                        echo '<span class="hotel-media-badge">⭐</span>';
                    }
                    echo '<img src="' . esc_url( $preview_url ) . '" alt="' . esc_attr( $name ) . '" loading="lazy" />';
                    echo '</a>';
                }
            }
            echo '</div>';

            if ( count( $medias ) > 12 ) {
                echo '<p style="margin-top: 8px; font-size: 11px; color: #666; text-align: center;">' . sprintf( esc_html__( '+ %d more images', 'seminargo' ), count( $medias ) - 12 ) . '</p>';
            }
        } else {
            echo '<p style="color: #999; font-style: italic; text-align: center; padding: 20px;">' . esc_html__( 'No images from API', 'seminargo' ) . '</p>';
        }

        // Info message
        echo '<div style="margin-top: 12px; padding: 10px; background: #f0f9ff; border-left: 3px solid #2271b1; border-radius: 4px; font-size: 11px; color: #1e40af;">';
        echo '<strong>ℹ️ ' . esc_html__( 'Images displayed via API', 'seminargo' ) . '</strong><br>';
        echo esc_html__( 'Images load directly from API servers. No local storage needed.', 'seminargo' );
        echo '</div>';
    }

    /**
     * Render API Info meta box
     */
    public function render_api_info_meta_box( $post ) {
        $fields = [
            'hotel_id'                    => __( 'API Hotel ID', 'seminargo' ),
            'ref_code'                    => __( 'Reference Code', 'seminargo' ),
            'api_slug'                    => __( 'API Slug (Current)', 'seminargo' ),
            'mig_slug'                    => __( 'Old Slug (migSlug)', 'seminargo' ),
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
     * Render Debug Image URLs meta box
     * Shows all API image URLs for debugging
     */
    public function render_debug_image_urls_meta_box( $post ) {
        $medias_json = get_post_meta( $post->ID, 'medias_json', true );
        $medias = json_decode( $medias_json, true ) ?: [];

        if ( empty( $medias ) ) {
            echo '<p style="color: #999;">' . esc_html__( 'No API image data available', 'seminargo' ) . '</p>';
            return;
        }

        echo '<p style="font-size: 12px; color: #666; margin-bottom: 15px;">';
        echo sprintf( esc_html__( 'Showing %d image URLs from API (stored in medias_json)', 'seminargo' ), count( $medias ) );
        echo '</p>';

        echo '<div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">';
        echo '<table style="width: 100%; font-size: 11px; border-collapse: collapse;">';
        echo '<thead>';
        echo '<tr style="background: #fff; position: sticky; top: 0;">';
        echo '<th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">#</th>';
        echo '<th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">Name</th>';
        echo '<th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">URL (Full)</th>';
        echo '<th style="padding: 8px; text-align: left; border-bottom: 2px solid #ddd;">Preview URL</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ( $medias as $index => $media ) {
            $url = $media['url'] ?? '';
            $preview_url = $media['previewUrl'] ?? '';
            $name = $media['name'] ?? 'N/A';
            $id = $media['id'] ?? '';

            $bg_color = ( $index % 2 === 0 ) ? '#fff' : '#f5f5f5';

            echo '<tr style="background: ' . $bg_color . ';">';
            echo '<td style="padding: 8px; border-bottom: 1px solid #ddd; vertical-align: top;"><strong>' . esc_html( $index ) . '</strong></td>';
            echo '<td style="padding: 8px; border-bottom: 1px solid #ddd; vertical-align: top;">';
            echo '<strong>' . esc_html( $name ) . '</strong><br>';
            echo '<small style="color: #999;">ID: ' . esc_html( $id ) . '</small>';
            echo '</td>';
            echo '<td style="padding: 8px; border-bottom: 1px solid #ddd; word-break: break-all; font-family: monospace; vertical-align: top;">';
            if ( $url ) {
                echo '<a href="' . esc_url( $url ) . '" target="_blank" style="color: #2271b1;">' . esc_html( $url ) . '</a>';
            } else {
                echo '<span style="color: #999;">-</span>';
            }
            echo '</td>';
            echo '<td style="padding: 8px; border-bottom: 1px solid #ddd; word-break: break-all; font-family: monospace; vertical-align: top;">';
            if ( $preview_url ) {
                echo '<a href="' . esc_url( $preview_url ) . '" target="_blank" style="color: #2271b1;">' . esc_html( $preview_url ) . '</a>';
            } else {
                echo '<span style="color: #999;">-</span>';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '<p style="margin-top: 10px; font-size: 11px; color: #666;">';
        echo '💡 <strong>Note:</strong> These are the URLs stored from the API. ';
        echo 'WordPress downloads these during Phase 2. ';
        echo 'If URLs change in the API, Phase 1 updates this data, and Phase 2 will download new images.';
        echo '</p>';
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $last_import = get_option( $this->last_import_option, [] );
        $next_scheduled = wp_next_scheduled( 'seminargo_hotels_cron' );
        $total_hotels = wp_count_posts( 'hotel' )->publish;
        ?>
        <div class="wrap seminargo-sync-page">
            <div class="seminargo-sync-header" style="background: linear-gradient(135deg, #AC2A6E 0%, #8A1F56 100%); color: white; margin: 0 0 32px 0; padding: 32px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 24px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <h1 style="margin: 0 0 12px 0; font-size: 32px; color: white;">🏨 <?php esc_html_e( 'Hotel Synchronisation', 'seminargo' ); ?></h1>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; opacity: 0.95;">
                            <div>
                                <div style="font-size: 11px; opacity: 0.8; margin-bottom: 4px;"><?php esc_html_e( 'Total Hotels', 'seminargo' ); ?></div>
                                <div style="font-size: 20px; font-weight: 600;"><?php echo number_format( $total_hotels ); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 11px; opacity: 0.8; margin-bottom: 4px;"><?php esc_html_e( 'Last Sync', 'seminargo' ); ?></div>
                                <div style="font-size: 16px; font-weight: 500;"><?php echo ! empty( $last_import['time'] ) ? date_i18n( 'j. M Y, H:i', $last_import['time'] ) : __( 'Never', 'seminargo' ); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 11px; opacity: 0.8; margin-bottom: 4px;"><?php esc_html_e( 'Next Scheduled', 'seminargo' ); ?></div>
                                <div style="font-size: 16px; font-weight: 500;"><?php echo $next_scheduled ? date_i18n( 'j. M, H:i', $next_scheduled ) : __( 'Not scheduled', 'seminargo' ); ?></div>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
                        <button id="btn-fetch-now" class="button" style="background: rgba(255,255,255,0.95); color: #AC2A6E; border: 1px solid rgba(255,255,255,0.3); padding: 6px 16px; font-size: 13px; font-weight: 600;">
                            🔄 <?php esc_html_e( 'Start Sync', 'seminargo' ); ?>
                        </button>
                        <button id="btn-stop-import" class="button" style="display: none; background: rgba(239, 68, 68, 0.9); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 6px 16px; font-size: 13px; backdrop-filter: blur(10px);">
                            ⏹ <?php esc_html_e( 'Stop', 'seminargo' ); ?>
                        </button>
                        <button id="btn-resume-import" class="button" style="display: none; background: rgba(16, 185, 129, 0.9); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 6px 16px; font-size: 13px; backdrop-filter: blur(10px);">
                            ▶ <?php esc_html_e( 'Resume', 'seminargo' ); ?>
                        </button>
                        <button id="btn-clear-logs" class="button" style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.25); padding: 6px 16px; font-size: 13px;">
                            🗑️ <?php esc_html_e( 'Clear Logs', 'seminargo' ); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Full-Width Progress Bar (when sync running) -->
            <div id="sync-progress-hero" style="display: none; margin-bottom: 32px;">
                <div style="background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span id="phase-icon" style="font-size: 32px;">🚀</span>
                            <div>
                                <h2 id="phase-name" style="margin: 0; font-size: 20px; color: #111827;">Starting Import...</h2>
                                <p id="current-action" style="margin: 4px 0 0 0; color: #6b7280; font-size: 14px;">Initializing...</p>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 48px; font-weight: 700; color: #AC2A6E; line-height: 1;" id="overall-percent">0%</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                                <span id="time-elapsed">0s</span> | ETA: <span id="time-remaining">...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Full-width progress bar -->
                    <div style="background: #f3f4f6; border-radius: 12px; height: 24px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);">
                        <div id="progress-bar" style="background: linear-gradient(90deg, #AC2A6E 0%, #d64a94 100%); height: 100%; width: 0%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px; box-shadow: 0 2px 8px rgba(172, 42, 110, 0.3);"></div>
                    </div>

                    <!-- Stats Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 20px;">
                        <div style="background: #f0f9ff; padding: 12px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Hotels</div>
                            <div style="font-size: 24px; font-weight: 700; color: #2271b1;"><span id="hotels-processed">0</span> / <span id="hotels-total">0</span></div>
                        </div>
                        <div style="background: #f0fdf4; padding: 12px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Created</div>
                            <div style="font-size: 24px; font-weight: 700; color: #10b981;" id="live-created">0</div>
                        </div>
                        <div style="background: #fffbeb; padding: 12px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Updated</div>
                            <div style="font-size: 24px; font-weight: 700; color: #f59e0b;" id="live-updated">0</div>
                        </div>
                        <div style="background: #fef2f2; padding: 12px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Errors</div>
                            <div style="font-size: 24px; font-weight: 700; color: #ef4444;"><span id="live-errors">0</span></div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- MASSIVE Live Logs Area (Primary Focus) -->
            <div style="background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
                <h2 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 700; color: #111827;">
                    📋 <?php esc_html_e( 'Live Sync Logs', 'seminargo' ); ?>
                </h2>
                <div style="margin-bottom: 16px; display: flex; gap: 20px; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="filter-errors" style="width: 18px; height: 18px;" />
                        <span style="font-size: 14px; color: #374151;"><?php esc_html_e( 'Only errors', 'seminargo' ); ?></span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="filter-updates" style="width: 18px; height: 18px;" />
                        <span style="font-size: 14px; color: #374151;"><?php esc_html_e( 'Only updates', 'seminargo' ); ?></span>
                    </label>
                </div>
                <div id="logs-container" style="max-height: 800px; min-height: 400px; overflow-y: auto; background: #1d2327; color: #fff; padding: 20px; border-radius: 6px; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 13px; line-height: 1.6; box-shadow: inset 0 2px 8px rgba(0,0,0,0.3);">
                    <p style="color: #72aee6;"><?php esc_html_e( 'No logs yet. Click "Start Sync" to begin.', 'seminargo' ); ?></p>
                </div>
            </div>

            <!-- Advanced Settings (Collapsible) -->
            <div style="margin-bottom: 24px;">
                <h2 style="margin-bottom: 16px; font-size: 24px; font-weight: 700; color: #111827;">
                    ⚙️ <?php esc_html_e( 'Advanced Settings', 'seminargo' ); ?>
                </h2>

                <!-- Environment Configuration -->
                <details class="settings-accordion" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <summary style="cursor: pointer; font-size: 16px; font-weight: 600; color: #374151; user-select: none; list-style: none;">
                        <span>🔀 <?php esc_html_e( 'Environment Configuration', 'seminargo' ); ?></span>
                    </summary>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6;">
                        <?php
                        $current_env = seminargo_get_environment();
                        $is_production = $current_env === 'production';
                        ?>
                        <form method="post" action="" id="seminargo-environment-form">
                            <?php wp_nonce_field( 'seminargo_environment_nonce' ); ?>
                            <input type="hidden" name="action" value="seminargo_toggle_environment">
                            <input type="hidden" name="seminargo_environment" id="environment-value" value="<?php echo esc_attr( $current_env ); ?>">

                            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 15px;">
                                <div style="flex: 1; text-align: center;">
                                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 5px; color: #f59e0b;">🟡 Staging</div>
                                    <div style="font-size: 11px; color: #666;">Development</div>
                                </div>

                                <label class="seminargo-toggle-switch" style="cursor: pointer;">
                                    <input type="checkbox" id="environment-toggle" <?php checked( $is_production ); ?>>
                                    <span class="seminargo-toggle-slider"></span>
                                </label>

                                <div style="flex: 1; text-align: center;">
                                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 5px; color: #10b981;">🟢 Production</div>
                                    <div style="font-size: 11px; color: #666;">Live Site</div>
                                </div>
                            </div>

                            <div id="toggle-status" style="padding: 12px; background: <?php echo $is_production ? '#d1fae5' : '#fef3c7'; ?>; border-radius: 6px; margin-bottom: 12px; text-align: center; font-weight: 600; font-size: 14px; color: <?php echo $is_production ? '#065f46' : '#92400e'; ?>;">
                                <?php echo $is_production ? '🟢 PRODUCTION' : '🟡 STAGING'; ?>
                            </div>

                            <button type="submit" class="button button-primary" id="save-environment-btn" style="width: 100%; padding: 12px;">
                                <?php esc_html_e( 'Save Environment', 'seminargo' ); ?>
                            </button>
                        </form>
                    </div>
                </details>

                <!-- Auto-Import Settings -->
                <details class="settings-accordion" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <summary style="cursor: pointer; font-size: 16px; font-weight: 600; color: #374151; user-select: none; list-style: none;">
                        <span>🤖 <?php esc_html_e( 'Auto-Import Settings', 'seminargo' ); ?></span>
                    </summary>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6;">
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                            <?php esc_html_e( 'Automatically syncs every 4 hours (6x daily). Full sync weekly, incremental 6 times per day.', 'seminargo' ); ?>
                        </p>

                        <div id="auto-import-status" style="margin-bottom: 16px; padding: 12px; background: #f9fafb; border-radius: 6px;">
                            <p style="margin: 0; color: #6b7280;"><?php esc_html_e( 'Loading status...', 'seminargo' ); ?></p>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <button id="btn-toggle-auto-import" class="button button-primary">
                                <?php esc_html_e( 'Enable Auto-Import', 'seminargo' ); ?>
                            </button>
                            <button id="btn-reset-progress" class="button">
                                ↻ <?php esc_html_e( 'Reset Progress', 'seminargo' ); ?>
                            </button>
                            <button id="btn-fix-schedule" class="button" style="background: #f59e0b; color: white; border-color: #f59e0b;">
                                🔧 <?php esc_html_e( 'Fix Schedule (4h)', 'seminargo' ); ?>
                            </button>
                        </div>
                    </div>
                </details>

                <!-- Duplicate Cleanup -->
                <details class="settings-accordion" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <summary style="cursor: pointer; font-size: 16px; font-weight: 600; color: #374151; user-select: none; list-style: none;">
                        <span>🔍 <?php esc_html_e( 'Duplicate Hotel Cleanup', 'seminargo' ); ?></span>
                    </summary>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6;">
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                            <?php esc_html_e( 'Find and remove duplicate hotels. Duplicates detected by matching hotel_id or ref_code.', 'seminargo' ); ?>
                        </p>

                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
                            <button id="btn-find-duplicates" class="button" style="background: #f59e0b; color: white; border-color: #f59e0b;">
                                🔍 <?php esc_html_e( 'Find Duplicates', 'seminargo' ); ?>
                            </button>
                            <button id="btn-cleanup-duplicates-dry" class="button" style="background: #3b82f6; color: white; border-color: #3b82f6;">
                                🧪 <?php esc_html_e( 'Dry Run', 'seminargo' ); ?>
                            </button>
                            <button id="btn-cleanup-duplicates" class="button" style="background: #dc3232; color: white; border-color: #dc3232;">
                                🗑️ <?php esc_html_e( 'Remove Duplicates', 'seminargo' ); ?>
                            </button>
                        </div>

                        <div id="duplicate-results" style="display: none;">
                            <div id="duplicate-summary" style="padding: 12px; background: #f9fafb; border-radius: 6px; margin-bottom: 10px;"></div>
                            <div id="duplicate-details" style="max-height: 400px; overflow-y: auto; padding: 12px; background: #f9fafb; border-radius: 6px; font-size: 12px;"></div>
                        </div>
                    </div>
                </details>

                <!-- Image Management (NEW) -->
                <details class="settings-accordion" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <summary style="cursor: pointer; font-size: 16px; font-weight: 600; color: #374151; user-select: none; list-style: none;">
                        <span>🖼️ <?php esc_html_e( 'Image Management', 'seminargo' ); ?></span>
                    </summary>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6;">
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                            <?php esc_html_e( 'Manage hotel images in the WordPress media library.', 'seminargo' ); ?>
                        </p>

                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <button id="btn-delete-hotel-images" class="button" style="background: #dc3232; color: white; border-color: #dc3232;">
                                🗑️ <?php esc_html_e( 'Delete Hotel Images', 'seminargo' ); ?>
                            </button>
                            <button id="btn-delete-all-hotels" class="button" style="background: #ef4444; color: white; border-color: #ef4444;">
                                💣 <?php esc_html_e( 'Delete All Hotels & Images', 'seminargo' ); ?>
                            </button>
                        </div>

                        <p style="margin-top: 12px; font-size: 13px; color: #991b1b; background: #fef2f2; padding: 12px; border-radius: 6px; border-left: 3px solid #dc2626;">
                            ⚠️ <strong>Delete Hotel Images:</strong> Removes all hotel attachments from media library (hotel posts kept)<br>
                            ⚠️ <strong>Delete All Hotels:</strong> Removes everything (posts + images)
                        </p>
                    </div>
                </details>

                <!-- Newsletter Settings -->
                <details class="settings-accordion" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <summary style="cursor: pointer; font-size: 16px; font-weight: 600; color: #374151; user-select: none; list-style: none;">
                        <span>📧 <?php esc_html_e( 'Newsletter Settings (Brevo)', 'seminargo' ); ?></span>
                    </summary>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6;">
                        <?php $brevo_api_key = get_option( 'seminargo_brevo_api_key', '' ); ?>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                            <?php esc_html_e( 'Configure Brevo API key for newsletter integration (footer + hotel newsletter landing page).', 'seminargo' ); ?>
                        </p>

                        <div style="margin-bottom: 16px;">
                            <label for="brevo-api-key" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                                <?php esc_html_e( 'Brevo API Key', 'seminargo' ); ?>
                            </label>
                            <input type="text" id="brevo-api-key" value="<?php echo esc_attr( $brevo_api_key ); ?>"
                                   placeholder="xkeysib-..."
                                   style="width: 100%; padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-family: monospace; font-size: 13px;">
                            <p style="margin-top: 6px; font-size: 12px; color: #6b7280;">
                                <?php esc_html_e( 'Get your API key from: Brevo Dashboard → Account → API Keys', 'seminargo' ); ?>
                            </p>
                        </div>

                        <button type="button" id="save-brevo-api-key" class="button button-primary">
                            💾 <?php esc_html_e( 'Save API Key', 'seminargo' ); ?>
                        </button>

                        <?php if ( ! empty( $brevo_api_key ) ) : ?>
                            <span style="margin-left: 12px; color: #10b981; font-size: 14px;">✅ <?php esc_html_e( 'API Key configured', 'seminargo' ); ?></span>
                        <?php else : ?>
                            <span style="margin-left: 12px; color: #ef4444; font-size: 14px;">⚠️ <?php esc_html_e( 'API Key not set', 'seminargo' ); ?></span>
                        <?php endif; ?>
                    </div>
                </details>

                <!-- API Configuration -->
                <details class="settings-accordion" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <summary style="cursor: pointer; font-size: 16px; font-weight: 600; color: #374151; user-select: none; list-style: none;">
                        <span>🔌 <?php esc_html_e( 'API Configuration & Info', 'seminargo' ); ?></span>
                    </summary>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f3f4f6;">
                        <table style="width: 100%; font-size: 13px;">
                            <tr>
                                <td style="padding: 8px 0; font-weight: 600; color: #374151;">API Endpoint:</td>
                                <td style="padding: 8px 0;"><code style="font-size: 11px; background: #f9fafb; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html( $this->api_url ); ?></code></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 600; color: #374151;">Finder Base URL:</td>
                                <td style="padding: 8px 0;"><code style="font-size: 11px; background: #f9fafb; padding: 4px 8px; border-radius: 4px;"><?php echo esc_html( $this->finder_base_url ); ?></code></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 600; color: #374151;">Cron Schedule:</td>
                                <td style="padding: 8px 0;">Every 4 hours (6x daily)</td>
                            </tr>
                        </table>

                        <div style="margin-top: 16px; padding: 12px; background: #f0f9ff; border-left: 3px solid #2271b1; border-radius: 4px;">
                            <p style="margin: 0; font-size: 13px; color: #1e40af;"><strong>ℹ️ Sync Process:</strong> Batched processing (200 hotels/batch) | ~10-15 min for 4800 hotels | Images via API URLs</p>
                        </div>
                    </div>
                </details>
            </div>

            <!-- Sync History Section -->
            <div style="background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
                <h2 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 700; color: #111827;">
                    📅 <?php esc_html_e( 'Sync History', 'seminargo' ); ?>
                </h2>
                <p style="color: #666; font-size: 13px; margin-top: 10px;">
                    <?php esc_html_e( 'View past sync runs including completed, stuck, and failed imports. Each entry shows stats, duration, and last 100 log entries.', 'seminargo' ); ?>
                </p>
                <button id="btn-load-history" class="button" style="margin-top: 15px;">
                    📜 <?php esc_html_e( 'Load Sync History', 'seminargo' ); ?>
                </button>
                <button id="btn-refresh-history" class="button" style="margin-left: 10px; display: none;">
                    🔄 <?php esc_html_e( 'Refresh', 'seminargo' ); ?>
                </button>
                <div id="sync-history-container" style="margin-top: 20px; display: none;">
                    <p style="color: #868e96;"><?php esc_html_e( 'Loading history...', 'seminargo' ); ?></p>
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
            
            /* Toggle Switch Styles */
            .seminargo-toggle-switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 30px;
            }
            
            .seminargo-toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .seminargo-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #f59e0b;
                transition: .4s;
                border-radius: 30px;
            }
            
            .seminargo-toggle-slider:before {
                position: absolute;
                content: "";
                height: 22px;
                width: 22px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .seminargo-toggle-switch input:checked + .seminargo-toggle-slider {
                background-color: #10b981;
            }
            
            .seminargo-toggle-switch input:checked + .seminargo-toggle-slider:before {
                transform: translateX(30px);
            }
            
            .seminargo-toggle-switch input:focus + .seminargo-toggle-slider {
                box-shadow: 0 0 1px #10b981;
            }

            /* Progress bar animation for duplicate cleanup */
            @keyframes progress-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }

            /* Accordion styling */
            .settings-accordion summary {
                cursor: pointer;
                user-select: none;
                transition: color 0.2s ease;
            }

            .settings-accordion summary::-webkit-details-marker {
                display: none;
            }

            .settings-accordion summary::before {
                content: '▶';
                display: inline-block;
                margin-right: 8px;
                font-size: 12px;
                transition: transform 0.3s ease;
                color: #AC2A6E;
            }

            .settings-accordion[open] summary::before {
                transform: rotate(90deg);
            }

            .settings-accordion summary:hover {
                color: #AC2A6E;
            }

            /* Responsive */
            @media (max-width: 768px) {
                #logs-container {
                    max-height: 400px !important;
                    min-height: 300px !important;
                }
                .seminargo-sync-header {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }
            }
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

                        // Progress is 10-90% for Phase 1
                        var overallPercent = 10 + (percent * 0.8);
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
                    $('#current-action').text('All hotels synced successfully. Finalizing...');
                    $('#progress-bar').css('width', '90%').text('90%');
                    $('#overall-percent').text('90%');
                }
                // Phase 2 UI updates removed - no longer downloading images
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
                $('#sync-progress-hero').show();
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

                // Reset UI and show STOP/RESUME buttons
                $('#sync-progress-hero').show();
                $('#btn-fetch-now').hide();
                $('#btn-stop-import').show();
                $('#btn-resume-import').show();
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
                                    $('#btn-fetch-now').prop('disabled', false).text('🔄 <?php echo esc_js( __( 'Fetch Now', 'seminargo' ) ); ?>').show();
                                    $('#btn-stop-import').hide();
                                    $('#btn-resume-import').hide();

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
                                        $('#sync-progress-hero').fadeOut(2000);
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

            $('#btn-stop-import').on('click', function() {
                if (!confirm('⏹ Stop Import?\n\nThis will cancel the current import.\nProgress will be saved to history.\n\nContinue?')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('⏹ <?php echo esc_js( __( 'Stopping...', 'seminargo' ) ); ?>');

                $.post(ajaxurl, { action: 'seminargo_stop_import' }, function(response) {
                    if (response.success) {
                        // Stop polling
                        if (window.progressPollingInterval) {
                            clearInterval(window.progressPollingInterval);
                        }

                        // Hide progress UI
                        $('#sync-progress-hero').fadeOut();
                        $('#btn-fetch-now').prop('disabled', false).text('🔄 <?php echo esc_js( __( 'Fetch Now', 'seminargo' ) ); ?>').show();
                        $btn.hide();

                        alert('✅ Import stopped successfully');
                        loadLogs();
                    } else {
                        alert('❌ Error: ' + (response.data || 'Failed to stop import'));
                        $btn.prop('disabled', false).text('⏹ <?php echo esc_js( __( 'STOP Import', 'seminargo' ) ); ?>');
                    }
                }).fail(function() {
                    alert('❌ Server error occurred');
                    $btn.prop('disabled', false).text('⏹ <?php echo esc_js( __( 'STOP Import', 'seminargo' ) ); ?>');
                });
            });

            $('#btn-resume-import').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('▶ <?php echo esc_js( __( 'Resuming...', 'seminargo' ) ); ?>');

                $.post(ajaxurl, { action: 'seminargo_resume_import' }, function(response) {
                    $btn.prop('disabled', false).text('▶ <?php echo esc_js( __( 'RESUME / Continue', 'seminargo' ) ); ?>');

                    if (response.success) {
                        $('#current-action').text('✅ Resume triggered - processing should continue...');

                        // Keep polling active
                        if (!window.progressPollingInterval) {
                            startProgressPolling();
                        }
                    } else {
                        alert('❌ Error: ' + (response.data || 'Failed to resume import'));
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('▶ <?php echo esc_js( __( 'RESUME / Continue', 'seminargo' ) ); ?>');
                    alert('❌ Server error occurred');
                });
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

            $('#btn-delete-hotel-images').on('click', function() {
                if (!confirm('⚠️ Delete Hotel Images?\n\nThis will PERMANENTLY DELETE all hotel images from the media library.\n\nHotel posts will be KEPT (only images removed).\n\nImages will show via API URLs after deletion.\n\nContinue?')) {
                    return;
                }

                var confirmText = prompt('Type "DELETE IMAGES" to confirm:');
                if (confirmText !== 'DELETE IMAGES') {
                    alert('❌ Cancelled. You did not type "DELETE IMAGES" correctly.');
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('🔥 <?php echo esc_js( __( 'Deleting images...', 'seminargo' ) ); ?>');

                $.post(ajaxurl, { action: 'seminargo_delete_hotel_images' }, function(response) {
                    $btn.prop('disabled', false).text('🗑️ <?php echo esc_js( __( 'Delete Hotel Images', 'seminargo' ) ); ?>');

                    if (response.success) {
                        var message = '✅ Successfully deleted ' + response.data.deleted_images + ' hotel images!\n\n';
                        if (response.data.orphaned_found > 0) {
                            message += '📦 Found & removed ' + response.data.orphaned_found + ' orphaned images\n';
                        }
                        message += '\nHotel posts were kept.\nPage will reload...';
                        alert(message);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (response.data || 'Unknown error'));
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('🗑️ <?php echo esc_js( __( 'Delete Hotel Images', 'seminargo' ) ); ?>');
                    alert('❌ Server error occurred');
                });
            });

            $('#filter-errors, #filter-updates').on('change', function() {
                loadLogs();
            });

            // Duplicate Cleanup
            $('#btn-find-duplicates').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('🔍 Searching for duplicates...');

                // Show searching message
                var searchingHtml = '<div style="padding: 15px; background: #eff6ff; border-left: 3px solid #3b82f6; border-radius: 4px;">';
                searchingHtml += '<strong style="color: #1e40af;">🔍 Searching for duplicates...</strong><br>';
                searchingHtml += '<span style="color: #666; font-size: 12px;">Scanning database for matching hotel_id and ref_code values...</span>';
                searchingHtml += '</div>';
                $('#duplicate-summary').html(searchingHtml);
                $('#duplicate-details').html('');
                $('#duplicate-results').show();

                $.post(ajaxurl, { action: 'seminargo_find_duplicates' }, function(response) {
                    $btn.prop('disabled', false).text('🔍 <?php echo esc_js( __( 'Find Duplicates', 'seminargo' ) ); ?>');

                    if (response.success) {
                        var data = response.data;

                        if (data.total_duplicates === 0) {
                            // No duplicates found
                            var html = '<div style="padding: 15px; background: #d1fae5; border-left: 3px solid #10b981; border-radius: 4px;">';
                            html += '<strong style="color: #059669; font-size: 16px;">✅ No Duplicates Found!</strong><br>';
                            html += '<span style="color: #666;">Your database is clean. All hotels are unique.</span>';
                            html += '</div>';
                            $('#duplicate-summary').html(html);
                            $('#duplicate-details').html('');
                        } else {
                            // Duplicates found
                            var html = '<div style="padding: 15px; background: #fef2f2; border-left: 3px solid #dc2626; border-radius: 4px; margin-bottom: 15px;">';
                            html += '<strong style="color: #dc2626; font-size: 16px;">⚠️ Found ' + data.total_duplicates + ' Duplicate Groups</strong><br><br>';
                            html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">';
                            html += '<div style="background: white; padding: 10px; border-radius: 4px; text-align: center;">';
                            html += '<div style="font-size: 20px; font-weight: bold; color: #dc2626;">' + data.total_to_remove + '</div>';
                            html += '<div style="font-size: 12px; color: #666;">To Remove</div>';
                            html += '</div>';
                            html += '<div style="background: white; padding: 10px; border-radius: 4px; text-align: center;">';
                            html += '<div style="font-size: 20px; font-weight: bold; color: #10b981;">' + data.total_duplicates + '</div>';
                            html += '<div style="font-size: 12px; color: #666;">To Keep</div>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';

                            $('#duplicate-summary').html(html);

                            var details = '<div style="font-size: 11px; background: #f9fafb; padding: 10px; border-radius: 4px;">';
                            details += '<strong style="margin-bottom: 10px; display: block;">Preview (showing first 20 groups):</strong>';
                            data.duplicates.slice(0, 20).forEach(function(group, idx) {
                                details += '<div style="padding: 10px; margin-bottom: 10px; background: white; border-radius: 4px; border-left: 3px solid #f59e0b;">';
                                details += '<strong>Group ' + (idx + 1) + ':</strong> ' + group.type + ' = "' + group.value + '" (' + group.count + ' duplicates)<br>';
                                details += '<span style="color: #10b981;">✓ Keep:</span> #' + group.keep.id + ' "' + group.keep.title + '" (most complete)<br>';
                                group.remove.forEach(function(hotel) {
                                    details += '<span style="color: #dc2626;">✗ Remove:</span> #' + hotel.id + ' "' + hotel.title + '"<br>';
                                });
                                details += '</div>';
                            });
                            if (data.total_duplicates > 20) {
                                details += '<div style="padding: 10px; background: #fffbeb; border-radius: 4px; text-align: center; color: #92400e;">';
                                details += '... and ' + (data.total_duplicates - 20) + ' more duplicate groups';
                                details += '</div>';
                            }
                            details += '</div>';

                            $('#duplicate-details').html(details);
                        }

                        $('#duplicate-results').show();
                    } else {
                        var errorHtml = '<div style="padding: 15px; background: #fef2f2; border-left: 3px solid #dc2626; border-radius: 4px;">';
                        errorHtml += '<strong style="color: #dc2626;">❌ Error</strong><br>';
                        errorHtml += '<span style="color: #666;">' + (response.data || 'Unknown error') + '</span>';
                        errorHtml += '</div>';
                        $('#duplicate-summary').html(errorHtml);
                        $('#duplicate-details').html('');
                        $('#duplicate-results').show();
                    }
                }).fail(function(xhr, status, error) {
                    $btn.prop('disabled', false).text('🔍 <?php echo esc_js( __( 'Find Duplicates', 'seminargo' ) ); ?>');

                    var errorHtml = '<div style="padding: 15px; background: #fef2f2; border-left: 3px solid #dc2626; border-radius: 4px;">';
                    errorHtml += '<strong style="color: #dc2626;">❌ Server Error</strong><br>';
                    errorHtml += '<span style="color: #666;">Status: ' + status + '<br>Error: ' + error + '</span>';
                    errorHtml += '</div>';
                    $('#duplicate-summary').html(errorHtml);
                    $('#duplicate-details').html('');
                    $('#duplicate-results').show();
                });
            });

            $('#btn-cleanup-duplicates-dry').on('click', function() {
                if (!confirm('Run a dry run to preview what would be removed? (No changes will be made)')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('🧪 Running...');

                $.post(ajaxurl, { 
                    action: 'seminargo_cleanup_duplicates',
                    dry_run: 'true'
                }, function(response) {
                    $btn.prop('disabled', false).text('🧪 <?php echo esc_js( __( 'Dry Run (Preview)', 'seminargo' ) ); ?>');
                    
                    if (response.success) {
                        var data = response.data;
                        var html = '<div style="padding: 15px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px; margin-bottom: 15px;">';
                        html += '<strong style="color: #92400e;">DRY RUN RESULTS (No changes made)</strong><br>';
                        html += '<span style="color: #666;">Would remove: ' + data.removed + ' hotels</span><br>';
                        html += '<span style="color: #666;">Would keep: ' + data.kept + ' hotels</span>';
                        html += '</div>';
                        
                        var details = '<div style="font-size: 11px; max-height: 300px; overflow-y: auto;">';
                        data.details.forEach(function(detail) {
                            details += '<div style="padding: 5px; margin-bottom: 5px; background: #f9fafb; border-radius: 3px;">' + detail + '</div>';
                        });
                        details += '</div>';
                        
                        $('#duplicate-summary').html(html);
                        $('#duplicate-details').html(details);
                        $('#duplicate-results').show();
                    } else {
                        alert('❌ Error: ' + response.data);
                    }
                });
            });

            $('#btn-cleanup-duplicates').on('click', function() {
                if (!confirm('⚠️ WARNING!\n\nThis will move duplicate hotels to TRASH.\n\nAre you sure you want to proceed?')) {
                    return;
                }

                var confirmText = prompt('Type "REMOVE" in capital letters to confirm:');
                if (confirmText !== 'REMOVE') {
                    alert('❌ Cancelled. You did not type "REMOVE" correctly.');
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('🗑️ Removing duplicates...');

                // Show processing message
                var processingHtml = '<div style="padding: 15px; background: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 4px; margin-bottom: 15px;">';
                processingHtml += '<strong style="color: #92400e;">⏳ Processing...</strong><br>';
                processingHtml += '<span style="color: #666;">This may take 30-60 seconds for large datasets. Please wait...</span><br>';
                processingHtml += '<div style="margin-top: 10px; background: #f3f4f6; height: 8px; border-radius: 4px; overflow: hidden;">';
                processingHtml += '<div style="background: linear-gradient(90deg, #AC2A6E, #d64a94); height: 100%; width: 0%; animation: progress-pulse 2s ease-in-out infinite;" id="duplicate-progress-bar"></div>';
                processingHtml += '</div>';
                processingHtml += '</div>';

                $('#duplicate-summary').html(processingHtml);
                $('#duplicate-details').html('');
                $('#duplicate-results').show();

                // Animate progress bar
                var progressWidth = 0;
                var progressTimer = setInterval(function() {
                    progressWidth += 5;
                    if (progressWidth > 95) progressWidth = 95; // Cap at 95% until actually done
                    $('#duplicate-progress-bar').css('width', progressWidth + '%');
                }, 200); // Increase every 200ms

                $.post(ajaxurl, {
                    action: 'seminargo_cleanup_duplicates',
                    dry_run: 'false'
                }, function(response) {
                    clearInterval(progressTimer);
                    $('#duplicate-progress-bar').css('width', '100%');

                    $btn.prop('disabled', false).text('🗑️ <?php echo esc_js( __( 'Remove Duplicates', 'seminargo' ) ); ?>');

                    if (response.success) {
                        var data = response.data;
                        var html = '<div style="padding: 15px; background: #d1fae5; border-left: 3px solid #10b981; border-radius: 4px; margin-bottom: 15px;">';
                        html += '<strong style="color: #059669; font-size: 16px;">✅ CLEANUP COMPLETE</strong><br><br>';
                        html += '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px;">';
                        html += '<div style="background: #ecfdf5; padding: 10px; border-radius: 4px; text-align: center;">';
                        html += '<div style="font-size: 24px; font-weight: bold; color: #10b981;">' + data.removed + '</div>';
                        html += '<div style="font-size: 12px; color: #666;">Removed</div>';
                        html += '</div>';
                        html += '<div style="background: #f0fdf4; padding: 10px; border-radius: 4px; text-align: center;">';
                        html += '<div style="font-size: 24px; font-weight: bold; color: #059669;">' + data.kept + '</div>';
                        html += '<div style="font-size: 12px; color: #666;">Kept</div>';
                        html += '</div>';
                        if (data.errors > 0) {
                            html += '<div style="background: #fef2f2; padding: 10px; border-radius: 4px; text-align: center;">';
                            html += '<div style="font-size: 24px; font-weight: bold; color: #dc2626;">' + data.errors + '</div>';
                            html += '<div style="font-size: 12px; color: #666;">Errors</div>';
                            html += '</div>';
                        }
                        html += '</div>';
                        html += '</div>';

                        var details = '<div style="font-size: 11px; max-height: 300px; overflow-y: auto; background: #f9fafb; padding: 10px; border-radius: 4px;">';
                        details += '<strong style="margin-bottom: 10px; display: block;">Detailed Log:</strong>';
                        data.details.forEach(function(detail) {
                            var color = detail.includes('ERROR') ? '#dc2626' : (detail.includes('Removed') ? '#10b981' : '#666');
                            details += '<div style="padding: 5px; margin-bottom: 5px; color: ' + color + ';">' + detail + '</div>';
                        });
                        details += '</div>';

                        $('#duplicate-summary').html(html);
                        $('#duplicate-details').html(details);
                        $('#duplicate-results').show();

                        alert('✅ Duplicate Cleanup Complete!\n\n' +
                              '✓ Removed: ' + data.removed + ' duplicate hotels\n' +
                              '✓ Kept: ' + data.kept + ' unique hotels\n' +
                              (data.errors > 0 ? '⚠ Errors: ' + data.errors + '\n\n' : '\n') +
                              'Page will reload in 3 seconds to refresh counts...');

                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        alert('❌ Error during cleanup: ' + (response.data || 'Unknown error'));
                    }
                }).fail(function(xhr, status, error) {
                    clearInterval(progressTimer);
                    $btn.prop('disabled', false).text('🗑️ <?php echo esc_js( __( 'Remove Duplicates', 'seminargo' ) ); ?>');

                    var errorHtml = '<div style="padding: 15px; background: #fef2f2; border-left: 3px solid #dc2626; border-radius: 4px;">';
                    errorHtml += '<strong style="color: #dc2626;">❌ SERVER ERROR</strong><br>';
                    errorHtml += '<span style="color: #666;">Status: ' + status + '</span><br>';
                    errorHtml += '<span style="color: #666;">Error: ' + error + '</span>';
                    errorHtml += '</div>';

                    $('#duplicate-summary').html(errorHtml);
                    alert('❌ Server error occurred during cleanup: ' + error);
                });
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

            $('#btn-fix-schedule').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('🔧 <?php echo esc_js( __( 'Fixing...', 'seminargo' ) ); ?>');

                $.post(ajaxurl, { action: 'seminargo_force_reschedule_cron' }, function(response) {
                    $btn.prop('disabled', false).text('🔧 <?php echo esc_js( __( 'Fix Schedule (12h)', 'seminargo' ) ); ?>');

                    if (response.success) {
                        console.log('[FIX SCHEDULE] Debug:', response.data);
                        alert('✅ Schedule fixed!\n\n' +
                              'Old Next Run: ' + response.data.old_next_run + '\n' +
                              'New Next Run: ' + response.data.next_run_formatted + '\n' +
                              'Cleared: ' + response.data.cleared + ' events\n\n' +
                              'Page will reload...');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (response.data || 'Failed to reschedule'));
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('🔧 <?php echo esc_js( __( 'Fix Schedule (12h)', 'seminargo' ) ); ?>');
                    alert('❌ Server error occurred');
                });
            });

            /**
             * Check if an import is already running and resume progress UI
             * CRITICAL FIX: Restores progress display after page refresh
             */
            function checkAndResumeRunningImport() {
                $.post(ajaxurl, { action: 'seminargo_get_import_progress' }, function(resp) {
                    if (!resp.success || !resp.data.progress) return;

                    var prog = resp.data.progress;

                    // Only resume if status is 'running'
                    if (prog.status !== 'running') return;

                    console.log('🔄 Resuming running import...', prog);

                    // Show progress UI and STOP/RESUME buttons
                    $('#sync-progress-hero').show();
                    $('#btn-fetch-now').prop('disabled', true).text('⏳ <?php echo esc_js( __( 'Import Running...', 'seminargo' ) ); ?>').hide();
                    $('#btn-stop-import').show();
                    $('#btn-resume-import').show();

                    // Restore progress values
                    $('#hotels-total').text(prog.total_hotels || 0);
                    $('#hotels-processed').text(prog.hotels_processed || 0);
                    $('#live-created').text(prog.created || 0);
                    $('#live-updated').text(prog.updated || 0);
                    $('#images-processed').text(prog.images_processed || 0);

                    // Set phase display
                    if (prog.phase === 'phase1') {
                        $('#phase-icon').text('🏨');
                        $('#phase-name').text('<?php echo esc_js( __( 'Phase 1: Creating Hotels', 'seminargo' ) ); ?>');
                        $('#current-action').text('<?php echo esc_js( __( 'Processing hotel data...', 'seminargo' ) ); ?>');
                    } else if (prog.phase === 'finalize') {
                        $('#phase-icon').text('✨');
                        $('#phase-name').text('<?php echo esc_js( __( 'Finalizing Import...', 'seminargo' ) ); ?>');
                        $('#current-action').text('<?php echo esc_js( __( 'Cleaning up and finalizing...', 'seminargo' ) ); ?>');
                    }

                    // Calculate and display progress percentage
                    if (prog.total_hotels > 0) {
                        var percent = 0;
                        if (prog.phase === 'phase1') {
                            // Phase 1: 10-90%
                            percent = 10 + ((prog.hotels_processed / prog.total_hotels) * 80);
                        } else if (prog.phase === 'finalize') {
                            // Finalize: 90-100%
                            percent = 90 + ((prog.hotels_processed / prog.total_hotels) * 10);
                        }
                        $('#progress-bar').css('width', Math.round(percent) + '%').text(Math.round(percent) + '%');
                        $('#overall-percent').text(Math.round(percent) + '%');
                    }

                    // Calculate elapsed time
                    if (prog.start_time) {
                        var elapsed = Math.floor(Date.now() / 1000 - prog.start_time);
                        var mins = Math.floor(elapsed / 60);
                        var secs = elapsed % 60;
                        $('#time-elapsed').text(mins > 0 ? mins + 'm ' + secs + 's' : secs + 's');

                        // Set importStartTime for ongoing calculations
                        importStartTime = Date.now() - (elapsed * 1000);
                    }

                    // Start polling for updates
                    startProgressPolling();

                    console.log('✅ Progress UI resumed successfully');
                });
            }

            /**
             * Start polling for progress updates with AUTO-RESUME
             * Extracted as reusable function
             */
            function startProgressPolling() {
                // Clear any existing interval
                if (window.progressPollingInterval) {
                    clearInterval(window.progressPollingInterval);
                }

                // Track last progress change for auto-resume detection
                var lastProgressSnapshot = null;
                var lastProgressChangeTime = Date.now();
                var stallDetectionSeconds = 15; // Auto-resume if no change for 15 seconds
                var autoResumeAttempts = 0;
                var maxAutoResumeAttempts = 50; // Max auto-resumes (enough for full sync)

                window.progressPollingInterval = setInterval(function() {
                    $.post(ajaxurl, { action: 'seminargo_get_import_progress' }, function(resp) {
                        if (!resp.success) return;

                        var prog = resp.data.progress;
                        var logs = resp.data.logs;

                        // Update logs
                        if (logs) {
                            renderLogs(logs);
                            updateProgressUI(logs);
                        }

                        // Update progress display
                        if (prog) {
                            var currentSnapshot = JSON.stringify({
                                offset: prog.offset,
                                hotels_processed: prog.hotels_processed,
                                images_processed: prog.images_processed
                            });

                            // AUTO-RESUME: Detect if progress has stalled
                            if (currentSnapshot !== lastProgressSnapshot) {
                                // Progress changed - update timestamp
                                lastProgressChangeTime = Date.now();
                                lastProgressSnapshot = currentSnapshot;
                                autoResumeAttempts = 0; // Reset counter on progress
                            } else {
                                // No change detected
                                var stallTime = Math.floor((Date.now() - lastProgressChangeTime) / 1000);

                                if (stallTime > stallDetectionSeconds && prog.status === 'running') {
                                    // Import is stalled - auto-resume!
                                    if (autoResumeAttempts < maxAutoResumeAttempts) {
                                        autoResumeAttempts++;
                                        console.log('[AUTO-RESUME] Stalled for ' + stallTime + 's - triggering resume (attempt ' + autoResumeAttempts + ')');

                                        $('#current-action').html('⚠️ <strong>Stalled detected - Auto-resuming...</strong> (attempt ' + autoResumeAttempts + ')');

                                        // Trigger resume
                                        $.post(ajaxurl, { action: 'seminargo_resume_import' }, function(resumeResp) {
                                            if (resumeResp.success) {
                                                console.log('[AUTO-RESUME] ✅ Resume triggered');
                                                lastProgressChangeTime = Date.now(); // Reset stall timer
                                            }
                                        });
                                    } else {
                                        // Too many auto-resume attempts - show warning
                                        $('#current-action').html('⛔ <strong>Import appears stuck</strong> - Click STOP and try again');
                                        console.warn('[AUTO-RESUME] Max attempts reached (' + maxAutoResumeAttempts + ') - giving up');
                                    }
                                }
                            }

                            $('#hotels-total').text(prog.total_hotels || 0);
                            $('#hotels-processed').text(prog.hotels_processed || 0);
                            $('#live-created').text(prog.created || 0);
                            $('#live-updated').text(prog.updated || 0);
                            $('#images-processed').text(prog.images_processed || 0);

                            // Update progress bar
                            if (prog.total_hotels > 0) {
                                var percent = 0;
                                if (prog.phase === 'phase1') {
                                    percent = 10 + ((prog.hotels_processed / prog.total_hotels) * 80);
                                } else if (prog.phase === 'finalize') {
                                    percent = 90 + ((prog.hotels_processed / prog.total_hotels) * 10);
                                }
                                $('#progress-bar').css('width', Math.round(percent) + '%').text(Math.round(percent) + '%');
                                $('#overall-percent').text(Math.round(percent) + '%');
                            }

                            // Check if complete
                            if (prog.status === 'complete' || prog.phase === 'done') {
                                clearInterval(window.progressPollingInterval);
                                $('#phase-icon').text('✅');
                                $('#phase-name').text('<?php echo esc_js( __( 'Import Complete!', 'seminargo' ) ); ?>');
                                $('#current-action').text('<?php echo esc_js( __( 'All done!', 'seminargo' ) ); ?>');
                                $('#progress-bar').css('width', '100%').text('100%');
                                $('#overall-percent').text('100%');
                                $('#time-remaining').text('<?php echo esc_js( __( 'Complete!', 'seminargo' ) ); ?>');
                                $('#btn-fetch-now').prop('disabled', false).text('🔄 <?php echo esc_js( __( 'Fetch Now', 'seminargo' ) ); ?>').show();
                                $('#btn-stop-import').hide();
                                $('#btn-resume-import').hide();
                            }
                        }
                    });
                }, 2000); // Poll every 2 seconds
            }

            // Initial loads
            loadLogs();
            loadAutoImportStatus();
            checkAndResumeRunningImport(); // NEW: Check for running import on page load

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
            
            // Environment Toggle Handler
            $('#environment-toggle').on('change', function() {
                var isProduction = $(this).is(':checked');
                $('#environment-value').val(isProduction ? 'production' : 'staging');
                
                // Update status display
                var statusDiv = $('#toggle-status');
                if (isProduction) {
                    statusDiv.css({
                        'background': '#d1fae5',
                        'color': '#065f46'
                    }).html('🟢 <?php echo esc_js( __( 'PRODUCTION', 'seminargo' ) ); ?>');
                } else {
                    statusDiv.css({
                        'background': '#fef3c7',
                        'color': '#92400e'
                    }).html('🟡 <?php echo esc_js( __( 'STAGING', 'seminargo' ) ); ?>');
                }
            });
            
            $('#seminargo-environment-form').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var submitBtn = $('#save-environment-btn');
                var originalText = submitBtn.text();
                
                submitBtn.prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'seminargo' ) ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'seminargo_toggle_environment',
                        environment: $('#environment-value').val(),
                        _wpnonce: '<?php echo wp_create_nonce( 'seminargo_environment_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            submitBtn.text('<?php echo esc_js( __( 'Saved!', 'seminargo' ) ); ?>').css('background', '#10b981');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                            submitBtn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js( __( 'Error saving environment. Please try again.', 'seminargo' ) ); ?>');
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            /**
             * Load and display sync history
             */
            function loadSyncHistory() {
                var $container = $('#sync-history-container');
                var $loadBtn = $('#btn-load-history');
                var $refreshBtn = $('#btn-refresh-history');

                $loadBtn.prop('disabled', true).text('<?php echo esc_js( __( 'Loading...', 'seminargo' ) ); ?>');
                $container.html('<p style="color: #868e96; text-align: center; padding: 20px;"><?php echo esc_js( __( 'Loading history...', 'seminargo' ) ); ?></p>').show();

                $.post(ajaxurl, { action: 'seminargo_get_sync_history' }, function(response) {
                    $loadBtn.prop('disabled', false).text('📜 <?php echo esc_js( __( 'Load Sync History', 'seminargo' ) ); ?>').hide();
                    $refreshBtn.show();

                    if (!response.success) {
                        $container.html('<p style="color: #ef4444;"><?php echo esc_js( __( 'Error loading history', 'seminargo' ) ); ?></p>');
                        return;
                    }

                    var history = response.data.history || [];

                    if (history.length === 0) {
                        $container.html('<p style="color: #868e96; text-align: center; padding: 20px;"><?php echo esc_js( __( 'No sync history yet. Run your first import to see history here.', 'seminargo' ) ); ?></p>');
                        return;
                    }

                    // Render history entries
                    var html = '<div style="display: flex; flex-direction: column; gap: 15px;">';

                    history.forEach(function(entry, index) {
                        var statusColor = '#868e96';
                        var statusIcon = '⏹';
                        var statusText = entry.status;

                        if (entry.status === 'complete') {
                            statusColor = '#10b981';
                            statusIcon = '✅';
                            statusText = 'Completed';
                        } else if (entry.status === 'failed') {
                            statusColor = '#ef4444';
                            statusIcon = '❌';
                            statusText = 'Failed';
                        } else if (entry.status === 'running') {
                            statusColor = '#3b82f6';
                            statusIcon = '⏳';
                            statusText = 'Running';
                        }

                        var syncTypeIcon = entry.is_full_sync ? '🌐' : '⚡';
                        var syncTypeText = entry.is_full_sync ? 'Full Sync' : 'Incremental';
                        var duration = entry.duration || 0;
                        var durationText = duration > 3600 ? (duration / 3600).toFixed(1) + 'h' : (duration / 60).toFixed(0) + 'm';

                        html += '<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">';

                        // Header
                        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
                        html += '<div>';
                        html += '<span style="font-weight: 600; color: ' + statusColor + ';">' + statusIcon + ' ' + statusText + '</span>';
                        html += '<span style="margin-left: 15px; color: #6b7280;">' + syncTypeIcon + ' ' + syncTypeText + '</span>';
                        html += '</div>';
                        html += '<div style="font-size: 12px; color: #9ca3af;">' + entry.date + ' (' + durationText + ')</div>';
                        html += '</div>';

                        // Stats
                        html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; margin-bottom: 10px;">';
                        html += '<div style="background: #f0fdf4; padding: 8px; border-radius: 4px; text-align: center;">';
                        html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Processed</div>';
                        html += '<div style="font-size: 16px; font-weight: bold; color: #2271b1;">' + (entry.stats.hotels_processed || 0) + '</div>';
                        html += '</div>';
                        html += '<div style="background: #f0fdf4; padding: 8px; border-radius: 4px; text-align: center;">';
                        html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Created</div>';
                        html += '<div style="font-size: 16px; font-weight: bold; color: #10b981;">' + (entry.stats.created || 0) + '</div>';
                        html += '</div>';
                        html += '<div style="background: #fffbeb; padding: 8px; border-radius: 4px; text-align: center;">';
                        html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Updated</div>';
                        html += '<div style="font-size: 16px; font-weight: bold; color: #f59e0b;">' + (entry.stats.updated || 0) + '</div>';
                        html += '</div>';
                        html += '<div style="background: #fef2f2; padding: 8px; border-radius: 4px; text-align: center;">';
                        html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Errors</div>';
                        html += '<div style="font-size: 16px; font-weight: bold; color: #ef4444;">' + (entry.stats.errors || 0) + '</div>';
                        html += '</div>';
                        html += '<div style="background: #eff6ff; padding: 8px; border-radius: 4px; text-align: center;">';
                        html += '<div style="font-size: 11px; color: #666; text-transform: uppercase;">Images</div>';
                        html += '<div style="font-size: 16px; font-weight: bold; color: #3b82f6;">' + (entry.stats.images_processed || 0) + '</div>';
                        html += '</div>';
                        html += '</div>';

                        // Expandable logs
                        if (entry.logs && entry.logs.length > 0) {
                            html += '<details style="margin-top: 10px;">';
                            html += '<summary style="cursor: pointer; color: #6b7280; font-size: 13px;">📋 View Logs (' + entry.logs.length + ' entries)</summary>';
                            html += '<div style="max-height: 300px; overflow-y: auto; background: #1d2327; color: #fff; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 11px; margin-top: 10px;">';

                            entry.logs.slice().reverse().forEach(function(log) {
                                var logColor = '#72aee6';
                                if (log.type === 'error') logColor = '#ff6b6b';
                                else if (log.type === 'success') logColor = '#51cf66';
                                else if (log.type === 'update') logColor = '#ffd43b';

                                html += '<div style="padding: 3px 0; border-bottom: 1px solid #333; color: ' + logColor + ';">';
                                html += '<span style="color: #868e96;">[' + log.time + ']</span> ';
                                html += log.message;
                                html += '</div>';
                            });

                            html += '</div>';
                            html += '</details>';
                        }

                        html += '</div>';
                    });

                    html += '</div>';
                    $container.html(html);
                }).fail(function() {
                    $loadBtn.prop('disabled', false).text('📜 <?php echo esc_js( __( 'Load Sync History', 'seminargo' ) ); ?>');
                    $container.html('<p style="color: #ef4444;"><?php echo esc_js( __( 'Failed to load history', 'seminargo' ) ); ?></p>');
                });
            }

            // Sync History button handlers
            $('#btn-load-history, #btn-refresh-history').on('click', function() {
                loadSyncHistory();
            });

            // Save Brevo API Key
            $('#save-brevo-api-key').on('click', function() {
                var $btn = $(this);
                var apiKey = $('#brevo-api-key').val().trim();

                if (!apiKey) {
                    alert('<?php echo esc_js( __( 'Bitte geben Sie einen API Key ein.', 'seminargo' ) ); ?>');
                    return;
                }

                $btn.prop('disabled', true).text('💾 <?php echo esc_js( __( 'Saving...', 'seminargo' ) ); ?>');

                $.post(ajaxurl, {
                    action: 'seminargo_save_brevo_api_key',
                    api_key: apiKey
                }, function(response) {
                    $btn.prop('disabled', false).text('💾 <?php echo esc_js( __( 'Save API Key', 'seminargo' ) ); ?>');

                    if (response.success) {
                        alert('✅ <?php echo esc_js( __( 'API Key saved successfully!', 'seminargo' ) ); ?>');
                        location.reload();
                    } else {
                        alert('❌ ' + (response.data || '<?php echo esc_js( __( 'Error saving API key', 'seminargo' ) ); ?>'));
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('💾 <?php echo esc_js( __( 'Save API Key', 'seminargo' ) ); ?>');
                    alert('❌ <?php echo esc_js( __( 'Server error occurred', 'seminargo' ) ); ?>');
                });
            });
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
     * AJAX handler to delete ONLY hotel images (keep hotel posts)
     * Detects hotel images by:
     * 1. post_parent = hotel post ID (attached images)
     * 2. _seminargo_source_url meta key (orphaned hotel images)
     */
    public function ajax_delete_hotel_images() {
        // Security check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        global $wpdb;
        $deleted_images = 0;
        $image_ids_to_delete = [];

        // METHOD 1: Get images attached to hotel posts (via post_parent)
        $hotels = get_posts([
            'post_type'      => 'hotel',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ]);

        foreach ( $hotels as $hotel_id ) {
            $attachments = get_posts([
                'post_type'      => 'attachment',
                'posts_per_page' => -1,
                'post_parent'    => $hotel_id,
                'fields'         => 'ids',
            ]);

            foreach ( $attachments as $attachment_id ) {
                $image_ids_to_delete[ $attachment_id ] = true;
            }
        }

        // METHOD 2: Get orphaned hotel images (have _seminargo_source_url meta but no valid post_parent)
        // These are images that were attached to deleted hotels
        $orphaned_images = $wpdb->get_col( "
            SELECT DISTINCT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_seminargo_source_url'
            AND p.post_type = 'attachment'
        " );

        foreach ( $orphaned_images as $attachment_id ) {
            $image_ids_to_delete[ $attachment_id ] = true;
        }

        // Delete all identified hotel images
        foreach ( array_keys( $image_ids_to_delete ) as $attachment_id ) {
            if ( wp_delete_attachment( $attachment_id, true ) ) {
                $deleted_images++;
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                __( 'Successfully deleted %d hotel images (including %d orphaned). Hotel posts were kept.', 'seminargo' ),
                $deleted_images,
                count( $orphaned_images )
            ),
            'deleted_images' => $deleted_images,
            'orphaned_found' => count( $orphaned_images ),
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

        // NOTE: Do NOT archive logs here - archive only when sync completes
        // Archiving here clears logs immediately, making UI show "No logs yet"

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

        // NOTE: Do NOT archive logs here - archive only when sync completes

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
                    // No more hotels - Phase 1 complete, skip to finalize
                    // PHASE 2 REMOVED: Images are now displayed via API URLs, not downloaded
                    $progress['phase'] = 'finalize';
                    $progress['offset'] = 0;
                    $this->log( 'success', "✅ PHASE 1 COMPLETE! All hotels created/updated (total: {$progress['hotels_processed']})" );
                    $this->log( 'info', '⏭️ Skipping Phase 2 (images displayed via API URLs)' );
                    $this->flush_logs();

                    update_option( 'seminargo_batched_import_progress', $progress, false );

                    // Move directly to finalize
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

            // PHASE 2: REMOVED - Images now displayed via API URLs
            // Skip directly to finalize if somehow phase2 is set
            if ( $progress['phase'] === 'phase2' ) {
                // Redirect to finalize
                $progress['phase'] = 'finalize';
                $this->log( 'info', '⏭️ Phase 2 skipped (images displayed via API URLs)' );
                update_option( 'seminargo_batched_import_progress', $progress, false );
                // Fall through to finalize below
            }

            // OLD Phase 2 code commented out - keeping for reference but not executing
            if ( false && $progress['phase'] === 'phase2_disabled' ) {
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
                // OPTIMIZED: Process MULTIPLE images per request (10 images) to speed up import
                // With system cron (not pseudo-cron), we have more reliable execution
                // Increased from 3 to 10 to reduce total executions by 70% (40k images = 4k executions vs 13k)
                $timeout_threshold = 50; // Exit at 50 seconds to leave 10s buffer
                $images_per_request = 10; // Process 10 images per request (optimized for speed)
                
                // CRITICAL: Calculate max time per image to prevent timeout
                // With 50s threshold and 10 images, we have ~5s per image budget
                // Account for overhead (DB queries, file operations, etc.) - use 4s per image max
                $max_time_per_image = 4; // Maximum seconds allowed per image download/processing
                
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
                        // OPTIMIZATION: Cache hotel post_id lookup (only query once per hotel, not per image)
                        static $hotel_post_cache = [];
                        $hotel_id_str = strval( $hotel->id );
                        
                        if ( ! isset( $hotel_post_cache[ $hotel_id_str ] ) ) {
                            // Find WordPress post for this hotel (only if not cached)
                            $query_start = microtime( true );
                            // OPTIMIZATION: Use direct meta query instead of WP_Query for better performance
                            global $wpdb;
                            $post_id = $wpdb->get_var( $wpdb->prepare(
                                "SELECT p.ID FROM {$wpdb->posts} p
                                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                                WHERE p.post_type = 'hotel'
                                AND p.post_status IN ('publish', 'draft')
                                AND pm.meta_key = 'hotel_id'
                                AND pm.meta_value = %s
                                LIMIT 1",
                                $hotel_id_str
                            ) );
                            $query_time = ( microtime( true ) - $query_start ) * 1000;
                            
                            // Cache the result (even if null)
                            $hotel_post_cache[ $hotel_id_str ] = $post_id ? intval( $post_id ) : null;
                            
                            // #region agent log
                            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H3', 'location' => 'process_single_batch:phase2_query_complete', 'message' => 'Hotel post lookup completed', 'data' => [ 'hotel_id' => $hotel->id ?? 'unknown', 'query_time_ms' => round( $query_time, 2 ), 'post_id' => $post_id ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                            // #endregion
                        } else {
                            $post_id = $hotel_post_cache[ $hotel_id_str ];
                        }

                        if ( $post_id ) {
                            $total_images = count( $hotel->medias );
                            
                            // #region agent log
                            $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                            @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H1,H2,H7', 'location' => 'process_single_batch:phase2_process_images', 'message' => 'Processing images batch', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $current_image_index, 'total_images' => $total_images, 'images_per_request' => $images_per_request, 'elapsed_seconds' => time() - $request_start_time ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                            // #endregion
                            
                            // OPTIMIZATION: Batch-check existing images for fast skipping
                            // This allows us to quickly skip through hotels where all images already exist
                            // OPTIMIZED: Only check images we're about to process (not all remaining)
                            $batch_check_start = microtime( true );
                            $end_index = min( $current_image_index + $images_per_request, $total_images );
                            $existing_images_map = $this->batch_check_existing_images( $hotel, $current_image_index, $end_index );
                            $batch_check_time = ( microtime( true ) - $batch_check_start ) * 1000;
                            
                            if ( $batch_check_time > 100 ) {
                                $this->log( 'info', "⚡ Batch-checked {$total_images} images in " . round( $batch_check_time, 2 ) . "ms", $hotel->businessName ?? '' );
                            }
                            
                            // Process MULTIPLE images per request (optimized for speed)
                            $images_processed_this_request = 0;
                            $current_idx = $current_image_index;
                            
                            while ( $current_idx < $total_images && $images_processed_this_request < $images_per_request ) {
                                // Check timeout before each image
                                $elapsed = time() - $request_start_time;
                                $time_remaining = $timeout_threshold - $elapsed;
                                
                                // CRITICAL: Don't start new image if we don't have enough time
                                if ( $elapsed > $timeout_threshold ) {
                                    $this->log( 'info', "⏱️ Timeout threshold reached ({$elapsed}s) - stopping at image {$current_idx}", $hotel->businessName ?? '' );
                                    break;
                                }
                                
                                // CRITICAL: Don't start image if remaining time is less than max_time_per_image
                                if ( $time_remaining < $max_time_per_image ) {
                                    $this->log( 'info', "⏱️ Insufficient time remaining ({$time_remaining}s < {$max_time_per_image}s) - stopping at image {$current_idx}", $hotel->businessName ?? '' );
                                    break;
                                }
                                
                                // Process ONE image - wrapped in try-catch to ensure sync never crashes
                                // Pass the existing_images_map for fast lookup and remaining time budget
                                try {
                                    $image_start_time = time();
                                    $image_result = $this->process_single_image( $post_id, $hotel, $current_idx, $existing_images_map, $time_remaining );
                                    
                                    // CRITICAL: Check timeout AFTER each image to prevent exceeding threshold
                                    $image_elapsed = time() - $image_start_time;
                                    $total_elapsed = time() - $request_start_time;
                                    if ( $total_elapsed > $timeout_threshold ) {
                                        $this->log( 'info', "⏱️ Timeout threshold exceeded after image ({$total_elapsed}s) - stopping", $hotel->businessName ?? '' );
                                        break;
                                    }
                                    
                                    // Log error if image failed but continue
                                    if ( ! empty( $image_result['error'] ) ) {
                                        // Error already logged in process_single_image, just continue
                                    }
                                    
                                    $images_processed_this_request++;
                                    $current_idx = $image_result['next_index'];
                                    
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
                                        
                                        break; // Hotel complete, exit loop
                                    }
                                } catch ( Exception $e ) {
                                    // CRITICAL: Even if process_single_image throws an exception, continue to next image
                                    $error_message = 'Fatal error processing image: ' . $e->getMessage();
                                    $this->log( 'error', "❌ CRITICAL: {$error_message} - Continuing to next image", $hotel->businessName ?? '' );
                                    
                                    // Move to next image even on error
                                    $current_idx++;
                                    $images_processed_this_request++;
                                    
                                    // #region agent log
                                    $log_path = dirname( __FILE__ ) . '/../.cursor/debug.log';
                                    @file_put_contents( $log_path, json_encode( [ 'sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'H4', 'location' => 'process_single_batch:phase2_fatal_error', 'message' => 'Fatal error - continuing to next image', 'data' => [ 'post_id' => $post_id, 'hotel_id' => $hotel->id ?? 'unknown', 'image_index' => $current_idx, 'error' => $error_message, 'trace' => $e->getTraceAsString() ], 'timestamp' => time() * 1000 ] ) . "\n", FILE_APPEND | LOCK_EX );
                                    // #endregion
                                }
                            }
                            
                            // Update progress with current index
                            $progress['current_image_index'] = $current_idx;
                            
                            // If we've processed all images, move to next hotel
                            if ( $current_idx >= $total_images ) {
                                $progress['images_processed']++;
                                $progress['current_hotel_id'] = null;
                                $progress['current_image_index'] = 0;
                                $progress['offset']++;
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

                // CRITICAL: Verify progress was saved before continuing
                $verify_progress = get_option( 'seminargo_batched_import_progress', null );
                $save_verified = ( $verify_progress && isset( $verify_progress['offset'] ) && $verify_progress['offset'] === $progress['offset'] );

                // If save failed, retry once with cache bypass
                if ( ! $save_result || ! $save_verified ) {
                    $this->log( 'warning', '⚠️ Progress save failed or verification failed. Retrying with cache bypass...' );

                    // Force cache clear and retry
                    wp_cache_delete( 'seminargo_batched_import_progress', 'options' );
                    $retry_save = update_option( 'seminargo_batched_import_progress', $progress, false );

                    // Verify retry
                    $verify_retry = get_option( 'seminargo_batched_import_progress', null );
                    $retry_verified = ( $verify_retry && isset( $verify_retry['offset'] ) && $verify_retry['offset'] === $progress['offset'] );

                    if ( ! $retry_save || ! $retry_verified ) {
                        // CRITICAL FAILURE - cannot continue without progress persistence
                        $this->log( 'error', '❌ CRITICAL: Failed to save progress after retry! Aborting batch to prevent data loss.' );
                        $this->flush_logs();
                        return; // Do NOT schedule next batch
                    }

                    $this->log( 'success', '✅ Progress save retry succeeded.' );
                    $save_verified = true;
                }

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

                // Update last full sync timestamp if this was a full sync
                if ( isset( $progress['is_full_sync'] ) && $progress['is_full_sync'] ) {
                    update_option( $this->last_full_sync_option, time(), false );
                    $this->log( 'success', '📅 Full sync completed. Next full sync in 7 days.' );
                    $this->flush_logs();
                }

                // Mark as complete
                $progress['status'] = 'complete';
                $progress['phase'] = 'done';
                $progress['total_hotels'] = $progress['hotels_processed']; // Final count
                update_option( 'seminargo_batched_import_progress', $progress, false );

                // IMPORTANT: Archive logs to history before next sync
                $this->archive_current_logs_to_history( $progress );

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
     * Archive current logs to sync history
     * Saves logs with metadata before clearing for next sync
     */
    private function archive_current_logs_to_history( $progress = [] ) {
        // Flush any remaining logs first
        $this->flush_logs();

        // Get current logs
        $current_logs = get_option( $this->log_option, [] );

        // If no logs, nothing to archive
        if ( empty( $current_logs ) ) {
            return;
        }

        // Get existing history
        $history = get_option( $this->sync_history_option, [] );

        // Create history entry
        $history_entry = [
            'id' => time() . '_' . wp_generate_password( 6, false ), // Unique ID
            'timestamp' => time(),
            'date' => current_time( 'Y-m-d H:i:s' ),
            'status' => $progress['status'] ?? 'unknown',
            'phase' => $progress['phase'] ?? 'unknown',
            'sync_type' => $progress['sync_type'] ?? 'manual',
            'is_full_sync' => $progress['is_full_sync'] ?? false,
            'stats' => [
                'hotels_processed' => $progress['hotels_processed'] ?? 0,
                'images_processed' => $progress['images_processed'] ?? 0,
                'created' => $progress['created'] ?? 0,
                'updated' => $progress['updated'] ?? 0,
                'drafted' => $progress['drafted'] ?? 0,
                'errors' => $progress['errors'] ?? 0,
                'total_hotels' => $progress['total_hotels'] ?? 0,
            ],
            'duration' => isset( $progress['start_time'] ) ? ( time() - $progress['start_time'] ) : 0,
            'logs' => array_slice( $current_logs, -100 ), // Keep last 100 log entries for this sync
        ];

        // Add to history array
        array_unshift( $history, $history_entry ); // Add to beginning

        // Keep only last 20 syncs
        if ( count( $history ) > 20 ) {
            $history = array_slice( $history, 0, 20 );
        }

        // Save history
        update_option( $this->sync_history_option, $history, false );

        // Now clear current logs for next sync
        delete_option( $this->log_option );
    }

    /**
     * Get sync history
     * Returns array of past syncs with logs
     */
    public function get_sync_history( $limit = 20 ) {
        $history = get_option( $this->sync_history_option, [] );
        return array_slice( $history, 0, $limit );
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
                    migSlug
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
        // Convert hotel ID to string for consistent comparison (prevent type mismatch duplicates)
        $hotel_id_str = strval( $hotel->id );
        $ref_code = $hotel->refCode ?? '';
        
        // Check if hotel exists - check BOTH hotel_id AND ref_code as backup
        // This prevents duplicates even if hotel_id type mismatch occurred
        $args = [
            'post_type'      => 'hotel',
            'post_status'    => [ 'publish', 'draft' ],
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'   => 'hotel_id',
                    'value' => $hotel_id_str,
                ],
            ],
            'posts_per_page' => 1,
        ];
        
        // Add ref_code check if available (as backup duplicate detection)
        if ( ! empty( $ref_code ) ) {
            $args['meta_query'][] = [
                'key'   => 'ref_code',
                'value' => $ref_code,
            ];
        }

        $query = new WP_Query( $args );
        $is_new = ! $query->have_posts();
        $post_id = null;
        
        // If found by ref_code but hotel_id doesn't match, update the hotel_id to fix data inconsistency
        if ( ! $is_new && ! empty( $ref_code ) ) {
            $found_post_id = $query->posts[0]->ID;
            $existing_hotel_id = get_post_meta( $found_post_id, 'hotel_id', true );
            
            // If hotel_id doesn't match, update it (fixes type mismatch issues)
            if ( strval( $existing_hotel_id ) !== $hotel_id_str ) {
                update_post_meta( $found_post_id, 'hotel_id', $hotel_id_str );
                $this->log( 'info', '🔧 Fixed hotel_id type mismatch for: ' . ( $hotel->businessName ?? $hotel->name ?? 'Unknown' ) );
            }
        }

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
            'hotel_id'      => strval( $hotel->id ), // Store as string for consistency
            'api_slug'      => $hotel->slug ?? '',
            'mig_slug'      => $hotel->migSlug ?? '', // Store as-is from API (e.g., Jugendgästehaus_Bad_Ischl with actual ä)
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
     * Batch check existing images for a hotel (optimization for fast skipping)
     * @param object $hotel Hotel object from API
     * @param int $start_index Starting image index
     * @param int $end_index Ending image index (exclusive) - OPTIMIZED: only check range we need
     * @return array Map of image URLs to attachment IDs (if exists)
     */
    private function batch_check_existing_images( $hotel, $start_index, $end_index ) {
        if ( empty( $hotel->medias ) ) {
            return [];
        }
        
        global $wpdb;
        $existing_map = [];
        $urls_to_check = [];
        
        // Collect all URLs to check (both encoded and original)
        // OPTIMIZED: Only check the range we need (start_index to end_index)
        for ( $i = $start_index; $i < $end_index && $i < count( $hotel->medias ); $i++ ) {
            $media = $hotel->medias[ $i ];
            $image_url = $media->previewUrl ?? $media->url;
            if ( empty( $image_url ) ) {
                continue;
            }
            
            $original_url = $image_url;
            $encoded_url = $this->encode_image_url( $image_url );
            
            $urls_to_check[] = $encoded_url;
            if ( $original_url !== $encoded_url ) {
                $urls_to_check[] = $original_url;
            }
        }
        
        if ( empty( $urls_to_check ) ) {
            return [];
        }
        
        // OPTIMIZATION: Single SQL query to check all URLs at once
        $placeholders = implode( ',', array_fill( 0, count( $urls_to_check ), '%s' ) );
        $query = $wpdb->prepare(
            "SELECT post_id, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = %s 
            AND meta_value IN ($placeholders)",
            array_merge( [ '_seminargo_source_url' ], $urls_to_check )
        );
        
        $results = $wpdb->get_results( $query );
        
        // Build map: URL => attachment_id
        foreach ( $results as $row ) {
            $attachment_id = intval( $row->post_id );
            // Verify it's actually an attachment
            $post_type = get_post_type( $attachment_id );
            if ( $post_type === 'attachment' ) {
                $existing_map[ $row->meta_value ] = $attachment_id;
            }
        }
        
        return $existing_map;
    }

    /**
     * Process a single image for a hotel (one-by-one approach for WP Engine)
     * CRITICAL: This function is designed to NEVER crash the sync - all errors are caught and logged
     * @param int $post_id WordPress post ID
     * @param object $hotel Hotel object from API
     * @param int $image_index Index of the image to process (0-based)
     * @param array $existing_images_map Optional: Pre-checked map of existing images (URL => attachment_id)
     * @param int $time_budget Optional: Maximum seconds allowed for this image (prevents timeout)
     * @return array ['completed' => bool, 'next_index' => int, 'downloaded' => int, 'skipped' => int, 'error' => string|null]
     */
    private function process_single_image( $post_id, $hotel, $image_index, $existing_images_map = null, $time_budget = null ) {
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
            
            // Check if image already exists - use batch map if provided, otherwise query individually
            $existing = null;
            $attachment_id = null;
            
            if ( $existing_images_map !== null ) {
                // OPTIMIZATION: Use pre-checked batch map (much faster)
                if ( isset( $existing_images_map[ $image_url ] ) ) {
                    $attachment_id = $existing_images_map[ $image_url ];
                } elseif ( $original_url !== $image_url && isset( $existing_images_map[ $original_url ] ) ) {
                    $attachment_id = $existing_images_map[ $original_url ];
                }
                
                if ( $attachment_id ) {
                    $existing = [ (object) [ 'ID' => $attachment_id ] ];
                }
            } else {
                // Fallback: Individual query (slower but works if batch map not provided)
                $urls_to_check = [ $image_url ]; // Encoded URL (primary)
                if ( $original_url !== $image_url ) {
                    $urls_to_check[] = $original_url; // Also check original URL
                }
                
                try {
                    global $wpdb;
                    foreach ( $urls_to_check as $check_url ) {
                        $attachment_id = $wpdb->get_var( $wpdb->prepare(
                            "SELECT post_id FROM {$wpdb->postmeta} 
                            WHERE meta_key = %s AND meta_value = %s 
                            LIMIT 1",
                            '_seminargo_source_url',
                            $check_url
                        ) );
                        
                        if ( $attachment_id ) {
                            $post_type = get_post_type( $attachment_id );
                            if ( $post_type === 'attachment' ) {
                                $existing = [ (object) [ 'ID' => $attachment_id ] ];
                                break;
                            }
                        }
                    }
                } catch ( Exception $e ) {
                    $error_message = 'Database query error: ' . $e->getMessage();
                    $this->log( 'error', "⚠️ Image {$image_index} error: {$error_message}", $hotel->businessName ?? '' );
                    return [ 'completed' => false, 'next_index' => $image_index + 1, 'downloaded' => 0, 'skipped' => 0, 'error' => $error_message ];
                }
            }
            
            if ( ! empty( $existing ) ) {
                try {
                    $attachment_id = $existing[0]->ID;
                    $attachment_file = get_attached_file( $attachment_id );
                    $attachment_exists = $attachment_file && file_exists( $attachment_file );
                    
                    if ( ! $attachment_exists ) {
                        // Orphaned attachment - delete and re-download
                        $this->log( 'info', "🗑️ Image {$image_index} orphaned (DB exists, file missing) - deleting and re-downloading", $hotel->businessName ?? '' );
                        wp_delete_attachment( $attachment_id, true );
                        $existing = [];
                    } else {
                        // File exists - verify it's attached to this hotel post
                        $current_parent = wp_get_post_parent_id( $attachment_id );
                        
                        // If not attached to this hotel, attach it (but don't re-download)
                        if ( $current_parent !== $post_id ) {
                            wp_update_post( [
                                'ID' => $attachment_id,
                                'post_parent' => $post_id,
                            ] );
                            $this->log( 'info', "🔗 Image {$image_index} already exists, attached to hotel {$post_id}", $hotel->businessName ?? '' );
                        } else {
                            $this->log( 'info', "⏭️ Image {$image_index} already exists and attached - skipping", $hotel->businessName ?? '' );
                        }
                        
                        // Update source URL to current encoded URL (in case it was stored with original URL)
                        $stored_url = get_post_meta( $attachment_id, '_seminargo_source_url', true );
                        if ( $stored_url !== $image_url ) {
                            update_post_meta( $attachment_id, '_seminargo_source_url', $image_url );
                        }
                        
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
                        // CRITICAL: Calculate dynamic timeout based on time budget to prevent exceeding threshold
                        // If time_budget is provided, use it (minus 2s buffer for processing overhead)
                        // Otherwise, use default 12s (matching max_time_per_image)
                        $download_start = time();
                        if ( $time_budget !== null && $time_budget > 0 ) {
                            // Reserve 2 seconds for file operations, DB writes, etc.
                            $timeout = max( 5, min( $time_budget - 2, 12 ) ); // Min 5s, max 12s, but respect budget
                        } else {
                            $timeout = 12; // Default: 12 second timeout per image download (safer for batch processing)
                        }
                        
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
            // Only set if we don't already have a featured image
            if ( $image_index === 0 && isset( $attachment_id ) ) {
                try {
                    $current_thumbnail = get_post_thumbnail_id( $post_id );
                    if ( empty( $current_thumbnail ) ) {
                        set_post_thumbnail( $post_id, $attachment_id );
                        $this->log( 'info', "⭐ Image {$image_index} set as featured image", $hotel->businessName ?? '' );
                    }
                } catch ( Exception $e ) {
                    // Non-critical error - log but continue
                    $this->log( 'error', "⚠️ Failed to set featured image: " . $e->getMessage(), $hotel->businessName ?? '' );
                }
            }
            
            // Add to gallery - with error handling (for both downloaded and skipped images)
            if ( isset( $attachment_id ) ) {
                try {
                    // Use the same meta key as process_hotel_images for consistency
                    $gallery_ids = get_post_meta( $post_id, 'gallery', true );
                    if ( ! is_array( $gallery_ids ) ) {
                        $gallery_ids = [];
                    }
                    if ( ! in_array( $attachment_id, $gallery_ids ) ) {
                        $gallery_ids[] = $attachment_id;
                        update_post_meta( $post_id, 'gallery', $gallery_ids );
                        
                        // Also update ACF field if available
                        if ( function_exists( 'update_field' ) ) {
                            update_field( 'gallery', $gallery_ids, $post_id );
                        }
                        
                        // Also maintain the legacy meta key for backwards compatibility
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

        // CRITICAL: Detect stuck processes (running > 2 hours)
        if ( $existing_progress && $existing_progress['status'] === 'running' ) {
            $running_time = time() - ( $existing_progress['start_time'] ?? time() );

            if ( $running_time > 7200 ) { // 2 hours = stuck
                $this->log( 'error', '⚠️ STUCK PROCESS DETECTED! Process has been running for ' . round($running_time / 3600, 1) . ' hours. Resetting...' );

                // Mark as failed and archive logs
                $existing_progress['status'] = 'failed';
                $existing_progress['error'] = 'Timeout after ' . $running_time . ' seconds';
                $existing_progress['reset_time'] = time();
                $existing_progress['phase'] = 'timeout'; // Mark phase as timeout

                $this->flush_logs();

                // IMPORTANT: Archive stuck process logs to history
                $this->archive_current_logs_to_history( $existing_progress );

                // Clear the progress to start fresh
                $existing_progress = null;

                $this->log( 'info', '✅ Stuck process reset. Starting new import...' );
                $this->flush_logs();
            }
        }

        // If no import is running, start a new one
        if ( ! $existing_progress || $existing_progress['status'] !== 'running' ) {
            // OPTIMIZATION: Determine if we need full sync or incremental
            // Full sync: Once per week (all hotels + all images)
            // Incremental: Every other run (only new/updated hotels)
            $last_full_sync = get_option( $this->last_full_sync_option, 0 );
            $time_since_full_sync = time() - $last_full_sync;
            $one_week = 7 * 24 * 60 * 60; // 7 days in seconds

            $is_full_sync = ( $time_since_full_sync > $one_week );
            $sync_type = $is_full_sync ? 'FULL' : 'INCREMENTAL';

            $this->log( 'info', "🤖 Auto-import: Starting new {$sync_type} sync..." );
            if ( $is_full_sync ) {
                $this->log( 'info', "📅 Last full sync was " . round($time_since_full_sync / 86400, 1) . " days ago" );
            } else {
                $this->log( 'info', "⚡ Incremental sync - processing only new/updated hotels" );
            }
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
                'sync_type' => $sync_type, // Track if this is FULL or INCREMENTAL
                'is_full_sync' => $is_full_sync,
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
                migSlug
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

    /**
     * AJAX: Force reschedule cron to twicedaily
     */
    public function ajax_force_reschedule_cron() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        // Get info before clearing
        $old_next_run = wp_next_scheduled( 'seminargo_hotels_cron' );
        $old_schedule = false;
        if ( $old_next_run ) {
            $crons = _get_cron_array();
            foreach ( $crons as $timestamp => $cron ) {
                if ( isset( $cron['seminargo_hotels_cron'] ) ) {
                    $old_schedule = $cron['seminargo_hotels_cron'];
                    break;
                }
            }
        }

        // Force clear ALL scheduled events for this hook
        $cleared = wp_clear_scheduled_hook( 'seminargo_hotels_cron' );

        // Manually schedule with every_four_hours (4 hours = 6x daily)
        $next_time = time() + ( 4 * HOUR_IN_SECONDS ); // Exactly 4 hours from now
        $scheduled = wp_schedule_event( $next_time, 'every_four_hours', 'seminargo_hotels_cron' );

        $new_next_run = wp_next_scheduled( 'seminargo_hotels_cron' );

        wp_send_json_success( [
            'message' => 'Cron rescheduled to every 4 hours (6x daily)',
            'cleared' => $cleared,
            'old_next_run' => $old_next_run ? date_i18n( 'Y-m-d H:i:s', $old_next_run ) : 'None',
            'next_run' => $new_next_run,
            'next_run_formatted' => $new_next_run ? date_i18n( 'Y-m-d H:i:s', $new_next_run ) : 'Not scheduled',
            'scheduled_result' => $scheduled,
        ] );
    }

    /**
     * AJAX: Save Brevo API Key
     */
    public function ajax_save_brevo_api_key() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( 'API key cannot be empty' );
        }

        // Validate format (starts with xkeysib-)
        if ( strpos( $api_key, 'xkeysib-' ) !== 0 ) {
            wp_send_json_error( 'Invalid API key format. Should start with xkeysib-' );
        }

        update_option( 'seminargo_brevo_api_key', $api_key );

        wp_send_json_success( [
            'message' => 'API Key saved successfully',
        ] );
    }

    /**
     * AJAX: Get sync history
     * Returns last 20 sync runs with logs and stats
     */
    public function ajax_get_sync_history() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $limit = isset( $_GET['limit'] ) ? intval( $_GET['limit'] ) : 20;
        $history = $this->get_sync_history( $limit );

        wp_send_json_success( [
            'history' => $history,
            'total' => count( $history ),
        ] );
    }

    /**
     * AJAX: Stop/cancel current import
     */
    public function ajax_stop_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $progress = get_option( 'seminargo_batched_import_progress', null );

        if ( ! $progress || $progress['status'] !== 'running' ) {
            wp_send_json_error( 'No import is currently running' );
            return;
        }

        $this->log( 'warning', '⏹ Import manually stopped by user' );
        $this->flush_logs();

        // Archive logs before stopping
        $progress['status'] = 'cancelled';
        $progress['phase'] = 'cancelled';
        $this->archive_current_logs_to_history( $progress );

        // Clear progress
        delete_option( 'seminargo_batched_import_progress' );

        wp_send_json_success( [
            'message' => 'Import stopped successfully',
        ] );
    }

    /**
     * AJAX: Resume/continue stalled import
     * DIRECTLY executes the next batch instead of relying on cron
     */
    public function ajax_resume_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $progress = get_option( 'seminargo_batched_import_progress', null );

        if ( ! $progress || $progress['status'] !== 'running' ) {
            wp_send_json_error( 'No import is currently running' );
            return;
        }

        $this->log( 'info', '▶ Manually resuming import - executing batch directly...' );
        $this->flush_logs();

        // CRITICAL: Don't rely on wp-cron - execute batch DIRECTLY
        // This bypasses unreliable spawn_cron() and wp_remote_post()
        // Call process_single_batch() in background via async HTTP to this same endpoint

        // Trigger via separate async request to avoid timeout
        $resume_url = admin_url( 'admin-ajax.php?action=seminargo_execute_batch_direct' );
        wp_remote_post( $resume_url, [
            'timeout' => 0.01,
            'blocking' => false,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        ] );

        $this->log( 'success', '✅ Resume triggered - executing next batch directly' );
        $this->flush_logs();

        wp_send_json_success( [
            'message' => 'Batch execution triggered',
            'progress' => $progress,
        ] );
    }

    /**
     * AJAX: Execute batch directly (called by resume)
     * Runs process_single_batch() synchronously
     */
    public function ajax_execute_batch_direct() {
        // No permission check - this is triggered internally
        // Only execute if import is running
        $progress = get_option( 'seminargo_batched_import_progress', null );

        if ( ! $progress || $progress['status'] !== 'running' ) {
            return; // Silently exit if not running
        }

        // Execute the batch directly
        $this->process_single_batch();

        // No response needed - this runs async
        exit;
    }

    /**
     * AJAX: Find duplicate hotels
     */
    public function ajax_find_duplicates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $duplicates = $this->find_duplicate_hotels();
        
        wp_send_json_success( [
            'duplicates' => $duplicates,
            'total_duplicates' => count( $duplicates ),
            'total_to_remove' => array_sum( array_map( function( $group ) {
                return count( $group ) - 1; // Keep 1, remove the rest
            }, $duplicates ) ),
        ] );
    }

    /**
     * AJAX: Cleanup duplicate hotels
     */
    public function ajax_cleanup_duplicates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $dry_run = isset( $_POST['dry_run'] ) && $_POST['dry_run'] === 'true';
        $result = $this->cleanup_duplicate_hotels( $dry_run );
        
        wp_send_json_success( $result );
    }

    /**
     * Find duplicate hotels by hotel_id or ref_code
     */
    private function find_duplicate_hotels() {
        global $wpdb;
        
        // Find duplicates by hotel_id
        $duplicates_by_id = $wpdb->get_results( "
            SELECT pm.meta_value as hotel_id, COUNT(*) as count, GROUP_CONCAT(p.ID) as post_ids
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'hotel'
            AND p.post_status IN ('publish', 'draft')
            AND pm.meta_key = 'hotel_id'
            AND pm.meta_value != ''
            GROUP BY pm.meta_value
            HAVING COUNT(*) > 1
        ", ARRAY_A );

        // Find duplicates by ref_code
        $duplicates_by_ref = $wpdb->get_results( "
            SELECT pm.meta_value as ref_code, COUNT(*) as count, GROUP_CONCAT(p.ID) as post_ids
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'hotel'
            AND p.post_status IN ('publish', 'draft')
            AND pm.meta_key = 'ref_code'
            AND pm.meta_value != ''
            GROUP BY pm.meta_value
            HAVING COUNT(*) > 1
        ", ARRAY_A );

        $duplicate_groups = [];

        // Process hotel_id duplicates
        foreach ( $duplicates_by_id as $dup ) {
            $post_ids = array_map( 'intval', explode( ',', $dup['post_ids'] ) );
            $hotel_id = $dup['hotel_id'];
            
            // Get hotel details
            $hotels = [];
            foreach ( $post_ids as $post_id ) {
                $hotel = get_post( $post_id );
                if ( $hotel ) {
                    $hotels[] = [
                        'id' => $post_id,
                        'title' => $hotel->post_title,
                        'hotel_id' => get_post_meta( $post_id, 'hotel_id', true ),
                        'ref_code' => get_post_meta( $post_id, 'ref_code', true ),
                        'has_images' => has_post_thumbnail( $post_id ),
                        'meta_count' => $this->count_meta_fields( $post_id ),
                        'date' => $hotel->post_date,
                    ];
                }
            }
            
            // Sort by completeness (most complete first)
            usort( $hotels, function( $a, $b ) {
                if ( $a['meta_count'] !== $b['meta_count'] ) {
                    return $b['meta_count'] - $a['meta_count'];
                }
                if ( $a['has_images'] !== $b['has_images'] ) {
                    return $b['has_images'] ? 1 : -1;
                }
                return strtotime( $b['date'] ) - strtotime( $a['date'] );
            });
            
            $duplicate_groups[] = [
                'type' => 'hotel_id',
                'value' => $hotel_id,
                'count' => count( $hotels ),
                'keep' => $hotels[0],
                'remove' => array_slice( $hotels, 1 ),
            ];
        }

        // Process ref_code duplicates (only if not already in hotel_id duplicates)
        foreach ( $duplicates_by_ref as $dup ) {
            $post_ids = array_map( 'intval', explode( ',', $dup['post_ids'] ) );
            $ref_code = $dup['ref_code'];
            
            // Skip if already handled by hotel_id
            $already_handled = false;
            foreach ( $duplicate_groups as $group ) {
                if ( in_array( $post_ids[0], array_column( $group['remove'], 'id' ) ) || 
                     $group['keep']['id'] === $post_ids[0] ) {
                    $already_handled = true;
                    break;
                }
            }
            
            if ( $already_handled ) {
                continue;
            }
            
            // Get hotel details
            $hotels = [];
            foreach ( $post_ids as $post_id ) {
                $hotel = get_post( $post_id );
                if ( $hotel ) {
                    $hotels[] = [
                        'id' => $post_id,
                        'title' => $hotel->post_title,
                        'hotel_id' => get_post_meta( $post_id, 'hotel_id', true ),
                        'ref_code' => get_post_meta( $post_id, 'ref_code', true ),
                        'has_images' => has_post_thumbnail( $post_id ),
                        'meta_count' => $this->count_meta_fields( $post_id ),
                        'date' => $hotel->post_date,
                    ];
                }
            }
            
            // Sort by completeness
            usort( $hotels, function( $a, $b ) {
                if ( $a['meta_count'] !== $b['meta_count'] ) {
                    return $b['meta_count'] - $a['meta_count'];
                }
                if ( $a['has_images'] !== $b['has_images'] ) {
                    return $b['has_images'] ? 1 : -1;
                }
                return strtotime( $b['date'] ) - strtotime( $a['date'] );
            });
            
            $duplicate_groups[] = [
                'type' => 'ref_code',
                'value' => $ref_code,
                'count' => count( $hotels ),
                'keep' => $hotels[0],
                'remove' => array_slice( $hotels, 1 ),
            ];
        }

        return $duplicate_groups;
    }

    /**
     * Count meta fields for a post (to determine completeness)
     */
    private function count_meta_fields( $post_id ) {
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} 
            WHERE post_id = %d 
            AND meta_key NOT LIKE '\_%'
        ", $post_id ) );
        return intval( $count );
    }

    /**
     * Cleanup duplicate hotels
     */
    private function cleanup_duplicate_hotels( $dry_run = true ) {
        $duplicates = $this->find_duplicate_hotels();
        
        $removed = 0;
        $kept = 0;
        $errors = 0;
        $details = [];

        foreach ( $duplicates as $group ) {
            $keep_id = $group['keep']['id'];
            $remove_ids = array_column( $group['remove'], 'id' );
            
            foreach ( $remove_ids as $remove_id ) {
                if ( $dry_run ) {
                    $details[] = sprintf(
                        '[DRY RUN] Would remove hotel #%d "%s" (keep #%d "%s")',
                        $remove_id,
                        $group['remove'][array_search( $remove_id, array_column( $group['remove'], 'id' ) )]['title'],
                        $keep_id,
                        $group['keep']['title']
                    );
                    $removed++;
                } else {
                    // Move to trash (safer than permanent delete)
                    $result = wp_trash_post( $remove_id );
                    if ( $result ) {
                        $details[] = sprintf(
                            'Removed duplicate hotel #%d "%s" (kept #%d "%s")',
                            $remove_id,
                            $group['remove'][array_search( $remove_id, array_column( $group['remove'], 'id' ) )]['title'],
                            $keep_id,
                            $group['keep']['title']
                        );
                        $removed++;
                    } else {
                        $details[] = sprintf( 'ERROR: Failed to remove hotel #%d', $remove_id );
                        $errors++;
                    }
                }
            }
            $kept++;
        }

        return [
            'dry_run' => $dry_run,
            'removed' => $removed,
            'kept' => $kept,
            'errors' => $errors,
            'details' => $details,
        ];
    }
}

// Initialize the importer
new Seminargo_Hotel_Importer();

// Cleanup cron on theme switch
add_action( 'switch_theme', function() {
    wp_clear_scheduled_hook( 'seminargo_hotels_cron' );
} );
