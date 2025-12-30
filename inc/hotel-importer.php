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

    private $api_url = 'https://dev.seminargo.eu/pricelist/graphql';
    private $shop_url = 'https://lister-staging.seminargo.com/hotels/';
    private $log_option = 'seminargo_hotels_import_log';
    private $last_import_option = 'seminargo_hotels_last_import';
    private $imported_ids_option = 'seminargo_hotels_imported_ids';
    private $auto_import_enabled_option = 'seminargo_auto_import_enabled';
    private $auto_import_progress_option = 'seminargo_auto_import_progress';

    public function __construct() {
        // Admin menu
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

        // AJAX handlers
        add_action( 'wp_ajax_seminargo_fetch_hotels', [ $this, 'ajax_fetch_hotels' ] );
        add_action( 'wp_ajax_seminargo_get_logs', [ $this, 'ajax_get_logs' ] );
        add_action( 'wp_ajax_seminargo_clear_logs', [ $this, 'ajax_clear_logs' ] );
        add_action( 'wp_ajax_seminargo_toggle_auto_import', [ $this, 'ajax_toggle_auto_import' ] );
        add_action( 'wp_ajax_seminargo_reset_auto_import', [ $this, 'ajax_reset_auto_import' ] );
        add_action( 'wp_ajax_seminargo_get_auto_import_status', [ $this, 'ajax_get_auto_import_status' ] );

        // Cron
        add_action( 'init', [ $this, 'register_cron' ] );
        add_action( 'seminargo_hotels_cron', [ $this, 'run_auto_import_batch' ] );

        // Register hotel post type if not exists
        add_action( 'init', [ $this, 'register_post_type' ] );

        // Add cron interval
        add_filter( 'cron_schedules', [ $this, 'add_cron_interval' ] );

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

        // Show featured image
        if ( has_post_thumbnail( $post->ID ) ) {
            echo '<p><strong>' . esc_html__( 'Featured Image:', 'seminargo' ) . '</strong></p>';
            echo get_the_post_thumbnail( $post->ID, 'medium', [ 'style' => 'max-width: 100%; height: auto;' ] );
        }

        // Show media count
        echo '<p style="margin-top: 15px;"><strong>' . esc_html__( 'Total Media Files:', 'seminargo' ) . '</strong> ' . count( $medias ) . '</p>';

        // List media files
        if ( ! empty( $medias ) ) {
            echo '<div style="max-height: 200px; overflow-y: auto;">';
            foreach ( $medias as $media ) {
                $name = $media['name'] ?? 'Unknown';
                $url = $media['previewUrl'] ?? '';
                echo '<div style="margin-bottom: 5px; font-size: 11px;">';
                if ( $url ) {
                    echo '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $name ) . '</a>';
                } else {
                    echo esc_html( $name );
                }
                echo '</div>';
            }
            echo '</div>';
        }
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

        // Link to shop
        $shop_url = get_post_meta( $post->ID, 'shop_url', true );
        if ( $shop_url ) {
            echo '<p style="margin-top: 10px;"><a href="' . esc_url( $shop_url ) . '" target="_blank" class="button button-small">🔗 ' . esc_html__( 'View in Shop', 'seminargo' ) . '</a></p>';
        }
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
                            <td><?php echo ! empty( $last_import['time'] ) ? esc_html( date( 'Y-m-d H:i:s', $last_import['time'] ) ) : esc_html__( 'Never', 'seminargo' ); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Next Scheduled:', 'seminargo' ); ?></strong></td>
                            <td><?php echo $next_scheduled ? esc_html( date( 'Y-m-d H:i:s', $next_scheduled ) ) : esc_html__( 'Not scheduled', 'seminargo' ); ?></td>
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
                        <button id="btn-clear-logs" class="button" style="margin-left: 10px;">
                            🗑️ <?php esc_html_e( 'Clear Logs', 'seminargo' ); ?>
                        </button>
                    </div>

                    <div id="import-progress" style="display: none; margin-top: 20px;">
                        <div style="background: #f0f0f0; border-radius: 4px; padding: 3px;">
                            <div id="progress-bar" style="background: #2271b1; height: 20px; border-radius: 3px; width: 0%; transition: width 0.3s;"></div>
                        </div>
                        <p id="progress-text" style="margin-top: 10px; color: #666;"><?php esc_html_e( 'Starting import...', 'seminargo' ); ?></p>
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
                            <td><strong><?php esc_html_e( 'Shop URL:', 'seminargo' ); ?></strong></td>
                            <td><code style="font-size: 11px;"><?php echo esc_html( $this->shop_url ); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'Cron Schedule:', 'seminargo' ); ?></strong></td>
                            <td><?php esc_html_e( 'Every 12 hours (twicedaily)', 'seminargo' ); ?></td>
                        </tr>
                    </table>

                    <h3 style="margin-top: 20px;">ℹ️ <?php esc_html_e( 'How it works', 'seminargo' ); ?></h3>
                    <ul style="list-style: disc; margin-left: 20px; color: #666;">
                        <li><?php esc_html_e( 'New hotels from API → Created as published posts', 'seminargo' ); ?></li>
                        <li><?php esc_html_e( 'Existing hotels → Updated with changes logged', 'seminargo' ); ?></li>
                        <li><?php esc_html_e( 'Hotels missing from API → Set to draft status', 'seminargo' ); ?></li>
                        <li><?php esc_html_e( 'Images are downloaded and attached automatically', 'seminargo' ); ?></li>
                    </ul>
                </div>

                <!-- Import Information Card -->
                <div class="card" style="padding: 20px;">
                    <h2>ℹ️ <?php esc_html_e( 'How Import Works', 'seminargo' ); ?></h2>
                    <p style="color: #666; font-size: 13px; margin-top: 10px;">
                        <?php esc_html_e( 'Click "Fetch Now" to import ALL hotels in one complete run. The system will:', 'seminargo' ); ?>
                    </p>

                    <ul style="margin: 15px 0 15px 20px; color: #666; font-size: 13px; line-height: 1.8;">
                        <li><?php esc_html_e( '✓ Fetch ALL hotels from API (no limits)', 'seminargo' ); ?></li>
                        <li><?php esc_html_e( '✓ Process in 200-hotel batches', 'seminargo' ); ?></li>
                        <li><?php esc_html_e( '✓ Pace requests (0.5 second delay between batches)', 'seminargo' ); ?></li>
                        <li><?php esc_html_e( '✓ Run from start to finish without timeout', 'seminargo' ); ?></li>
                        <li><?php esc_html_e( '✓ Update existing hotels, create new ones', 'seminargo' ); ?></li>
                    </ul>

                    <div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 3px solid #2271b1; border-radius: 3px;">
                        <strong>💡 For Production (40,000+ hotels):</strong><br>
                        <?php esc_html_e( 'Use WP-CLI for better reliability: ', 'seminargo' ); ?>
                        <code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-size: 12px;">wp seminargo import-hotels --all</code>
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

            $('#btn-fetch-now').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('⏳ <?php echo esc_js( __( 'Importing...', 'seminargo' ) ); ?>');

                $('#import-progress').show();
                $('#progress-bar').css('width', '10%').css('background', '#AC2A6E');
                $('#progress-text').text('<?php echo esc_js( __( 'Connecting to API...', 'seminargo' ) ); ?>');

                // Auto-refresh logs every 2 seconds to show live progress
                var logRefreshInterval = setInterval(function() {
                    loadLogs();
                    $('#progress-bar').css('width', '50%'); // Show activity
                }, 2000);

                // Immediate first refresh
                setTimeout(function() { loadLogs(); }, 500);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'seminargo_fetch_hotels' },
                    timeout: 600000, // 10 minutes (matches PHP timeout)
                    success: function(response) {
                        clearInterval(logRefreshInterval);
                        btn.prop('disabled', false).text('🔄 <?php echo esc_js( __( 'Fetch Now', 'seminargo' ) ); ?>');

                        if (response.success) {
                            $('#progress-bar').css('width', '100%');
                            $('#progress-text').text('✅ <?php echo esc_js( __( 'Import completed!', 'seminargo' ) ); ?>');

                            $('#stat-created').text(response.data.created);
                            $('#stat-updated').text(response.data.updated);
                            $('#stat-drafted').text(response.data.drafted);
                            $('#stat-errors').text(response.data.errors);

                            loadLogs();

                            setTimeout(function() {
                                $('#import-progress').fadeOut();
                            }, 3000);
                        } else {
                            $('#progress-bar').css('background', '#ff6b6b').css('width', '100%');
                            $('#progress-text').text('❌ <?php echo esc_js( __( 'Error:', 'seminargo' ) ); ?> ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        clearInterval(logRefreshInterval);
                        btn.prop('disabled', false).text('🔄 <?php echo esc_js( __( 'Fetch Now', 'seminargo' ) ); ?>');
                        $('#progress-bar').css('background', '#ff6b6b').css('width', '100%');

                        if (status === 'timeout') {
                            $('#progress-text').text('⏱️ <?php echo esc_js( __( 'Request timeout after 10 minutes. Check server logs.', 'seminargo' ) ); ?>');
                        } else {
                            $('#progress-text').text('❌ <?php echo esc_js( __( 'Connection failed:', 'seminargo' ) ); ?> ' + (error || status));
                        }

                        loadLogs(); // Load logs even on error
                    }
                });
            });

            $('#btn-clear-logs').on('click', function() {
                if (confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs?', 'seminargo' ) ); ?>')) {
                    $.post(ajaxurl, { action: 'seminargo_clear_logs' }, function() {
                        loadLogs();
                    });
                }
            });

            $('#filter-errors, #filter-updates').on('change', function() {
                loadLogs();
            });

            // Initial load
            loadLogs();
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
     * Add log entry
     */
    private function log( $type, $message, $hotel = null, $field = null, $old_value = null, $new_value = null ) {
        $logs = get_option( $this->log_option, [] );

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

        $logs[] = $entry;

        // Keep only last 500 entries
        if ( count( $logs ) > 500 ) {
            $logs = array_slice( $logs, -500 );
        }

        update_option( $this->log_option, $logs );
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
                $error_msg = 'API Error at skip=' . $skip . ': ' . $response->get_error_message();
                $this->log( 'error', '❌ ' . $error_msg );
                throw new Exception( $error_msg );
            }

            $http_code = wp_remote_retrieve_response_code( $response );
            if ( $http_code !== 200 ) {
                $error_msg = 'HTTP Error ' . $http_code . ' at skip=' . $skip;
                $this->log( 'error', '❌ ' . $error_msg );
                throw new Exception( $error_msg );
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );

            if ( isset( $data->errors ) ) {
                $error_msg = 'GraphQL Error at skip=' . $skip . ': ' . json_encode( $data->errors );
                $this->log( 'error', '❌ ' . $error_msg );
                throw new Exception( $error_msg );
            }

            $batch_hotels = $data->data->hotelList ?? [];
            $batch_count = count( $batch_hotels );

            $this->log( 'info', '✅ Fetched ' . $batch_count . ' hotels in this batch (total so far: ' . ( count( $all_hotels ) + $batch_count ) . ')' );

            if ( $batch_count === 0 ) {
                // No more hotels to fetch
                $this->log( 'info', '🏁 Reached end of hotel list (batch returned 0 hotels)' );
                $has_more = false;
            } else {
                // Add to collection
                $all_hotels = array_merge( $all_hotels, $batch_hotels );
                $skip += $batch_size;

                // If we got fewer hotels than the batch size, we're done
                if ( $batch_count < $batch_size ) {
                    $this->log( 'info', '🏁 Reached end of hotel list (batch returned ' . $batch_count . ' < ' . $batch_size . ')' );
                    $has_more = false;
                } else {
                    // Small delay between batches to not overload the API (0.5 seconds)
                    usleep( 500000 );
                }
            }
        }

        $this->log( 'info', '✅ Total hotels fetched: ' . count( $all_hotels ) );
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

            // Process each hotel from API
            foreach ( $hotels as $hotel ) {
                try {
                    $result = $this->process_hotel( $hotel );
                    if ( $result === 'created' ) {
                        $stats['created']++;
                    } elseif ( $result === 'updated' ) {
                        $stats['updated']++;
                    }
                } catch ( Exception $e ) {
                    $this->log( 'error', '❌ Error processing hotel ' . $hotel->businessName . ': ' . $e->getMessage(), $hotel->businessName );
                    $stats['errors']++;
                }
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
    private function process_hotel( $hotel ) {
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

            // Handle images
            $this->process_hotel_images( $post_id, $hotel );

            return 'created';

        } else {
            // Update existing hotel
            $post = $query->posts[0];
            $post_id = $post->ID;
            $has_changes = false;

            // Check and update title
            if ( $post->post_title !== $hotel_title ) {
                $this->log( 'update', 'Updated hotel: ' . $hotel_title, $hotel_title, 'title', $post->post_title, $hotel_title );
                $has_changes = true;
            }

            // Check and update slug
            if ( $post->post_name !== $wp_slug ) {
                $this->log( 'update', 'Updated hotel: ' . $hotel_title, $hotel_title, 'slug', $post->post_name, $wp_slug );
                $has_changes = true;
            }

            // Check and update content
            if ( $post->post_content !== $content ) {
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

            if ( $has_changes || $meta_changed ) {
                return 'updated';
            }

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
            'shop_url'      => $this->shop_url . ( $hotel->slug ?? '' ),
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

            // All texts as JSON for reference
            'texts_json' => json_encode( $hotel->texts ?? [] ),

            // Attributes (full JSON)
            'attributes' => json_encode( $hotel->attributes ?? [] ),

            // Extracted attribute lists for easier filtering
            'amenities_list' => json_encode( $this->extract_amenities( $hotel->attributes ?? [] ) ),

            // Meeting rooms (full JSON with facility details)
            'meeting_rooms' => json_encode( $hotel->meetingRooms ?? [] ),

            // Cancellation rules
            'cancellation_rules' => json_encode( $hotel->cancellationRules ?? [] ),

            // Media metadata (full JSON)
            'medias_json' => json_encode( $hotel->medias ?? [] ),
        ];

        // Calculate capacity from meeting rooms if API doesn't provide it
        $max_capacity = $hotel->maxCapacityPeople ?? 0;
        $max_rooms = $hotel->maxCapacityRooms ?? 0;

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

        if ( $max_rooms == 0 && ! empty( $hotel->meetingRooms ) ) {
            $max_rooms = count( $hotel->meetingRooms );
        }

        $meta_fields['capacity'] = $max_capacity;
        $meta_fields['rooms'] = $max_rooms;

        foreach ( $meta_fields as $key => $value ) {
            $old_value = get_post_meta( $post_id, $key, true );
            $new_value = is_bool( $value ) ? ( $value ? '1' : '0' ) : $value;

            if ( $old_value != $new_value ) {
                update_post_meta( $post_id, $key, $new_value );

                // Also update ACF field if function exists
                if ( function_exists( 'update_field' ) ) {
                    update_field( $key, $new_value, $post_id );
                }

                if ( ! $is_new ) {
                    $this->log( 'update', 'Updated hotel: ' . $hotel_name, $hotel_name, $key, $old_value, $new_value );
                }
                $has_changes = true;
            }
        }

        return $has_changes;
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
     * Process hotel images
     */
    private function process_hotel_images( $post_id, $hotel ) {
        if ( empty( $hotel->medias ) ) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $first_image = true;
        $gallery_ids = [];

        foreach ( $hotel->medias as $media ) {
            try {
                $image_url = $media->previewUrl ?? $media->url;
                if ( empty( $image_url ) ) {
                    continue;
                }

                $image_name = basename( $media->url ?? $media->path );
                $image_name = sanitize_file_name( $image_name );

                // Check if image already exists
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

                if ( ! empty( $existing ) ) {
                    $attachment_id = $existing[0]->ID;
                } else {
                    // Download and create attachment
                    $tmp = download_url( $image_url );

                    if ( is_wp_error( $tmp ) ) {
                        $this->log( 'error', 'Failed to download image: ' . $image_url, $hotel->businessName );
                        continue;
                    }

                    $file_array = [
                        'name'     => $image_name,
                        'tmp_name' => $tmp,
                    ];

                    $attachment_id = media_handle_sideload( $file_array, $post_id );

                    if ( is_wp_error( $attachment_id ) ) {
                        @unlink( $tmp );
                        $this->log( 'error', 'Failed to create attachment: ' . $attachment_id->get_error_message(), $hotel->businessName );
                        continue;
                    }

                    // Store source URL for deduplication
                    update_post_meta( $attachment_id, '_seminargo_source_url', $image_url );
                }

                $gallery_ids[] = $attachment_id;

                // Set first image as featured
                if ( $first_image ) {
                    set_post_thumbnail( $post_id, $attachment_id );
                    $first_image = false;
                }

            } catch ( Exception $e ) {
                $this->log( 'error', 'Image processing error: ' . $e->getMessage(), $hotel->businessName );
            }
        }

        // Save gallery IDs
        if ( ! empty( $gallery_ids ) ) {
            update_post_meta( $post_id, 'gallery', $gallery_ids );
            if ( function_exists( 'update_field' ) ) {
                update_field( 'gallery', $gallery_ids, $post_id );
            }
        }
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

        // Increase limits for cron
        @ini_set( 'memory_limit', '512M' );
        @ini_set( 'max_execution_time', 300 ); // 5 minutes for cron

        // Get current progress
        $progress = get_option( $this->auto_import_progress_option, [
            'offset' => 0,
            'total_imported' => 0,
            'is_complete' => false,
            'last_run' => 0,
        ] );

        // Don't run if already complete
        if ( $progress['is_complete'] ) {
            return;
        }

        $batch_size = 500; // Import 500 hotels per cron run
        $offset = $progress['offset'];

        $this->log( 'info', "🤖 Auto-import batch started (offset: {$offset}, batch: {$batch_size})" );

        try {
            // Fetch batch from API
            $hotels = $this->fetch_hotels_batch_from_api( $offset, $batch_size );

            if ( empty( $hotels ) ) {
                // No more hotels - mark as complete
                $progress['is_complete'] = true;
                $progress['last_run'] = time();
                update_option( $this->auto_import_progress_option, $progress );
                $this->log( 'success', '✅ Auto-import completed! Total: ' . $progress['total_imported'] . ' hotels' );
                return;
            }

            $this->log( 'info', '📦 Fetched ' . count( $hotels ) . ' hotels in this batch' );

            // Process each hotel
            $created = 0;
            $updated = 0;
            $errors = 0;

            foreach ( $hotels as $hotel ) {
                try {
                    $result = $this->process_hotel( $hotel );
                    if ( $result === 'created' ) {
                        $created++;
                    } elseif ( $result === 'updated' ) {
                        $updated++;
                    }
                } catch ( Exception $e ) {
                    $errors++;
                    $this->log( 'error', '❌ Error processing hotel: ' . $e->getMessage(), $hotel->businessName ?? 'Unknown' );
                }
            }

            // Update progress
            $progress['offset'] += count( $hotels );
            $progress['total_imported'] += $created + $updated;
            $progress['last_run'] = time();

            // If we got fewer hotels than batch_size, we're done
            if ( count( $hotels ) < $batch_size ) {
                $progress['is_complete'] = true;
                $this->log( 'success', '✅ Auto-import completed! Total: ' . $progress['total_imported'] . ' hotels' );
            }

            update_option( $this->auto_import_progress_option, $progress );

            $this->log( 'success', "✅ Batch complete: {$created} created, {$updated} updated, {$errors} errors. Progress: {$progress['offset']} hotels" );

        } catch ( Exception $e ) {
            $this->log( 'error', '❌ Auto-import batch failed: ' . $e->getMessage() );
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
            'next_run_formatted' => $next_run ? date( 'Y-m-d H:i:s', $next_run ) : 'Not scheduled',
        ] );
    }
}

// Initialize the importer
new Seminargo_Hotel_Importer();

// Cleanup cron on theme switch
add_action( 'switch_theme', function() {
    wp_clear_scheduled_hook( 'seminargo_hotels_cron' );
} );
