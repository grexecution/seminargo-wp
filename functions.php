<?php
/**
 * seminargo Theme Functions
 *
 * @package Seminargo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Define theme constants
 */
define( 'SEMINARGO_VERSION', '3.0.1' );
define( 'SEMINARGO_THEME_PATH', get_template_directory() );
define( 'SEMINARGO_THEME_URL', get_template_directory_uri() );
define( 'SEMINARGO_ASSETS_PATH', SEMINARGO_THEME_PATH . '/assets/' );
define( 'SEMINARGO_ASSETS_URL', SEMINARGO_THEME_URL . '/assets/' );
define( 'SEMINARGO_INC_PATH', SEMINARGO_THEME_PATH . '/inc/' );

/**
 * Environment Configuration
 * 
 * Get environment from database option (set via admin toggle)
 * Falls back to 'production' if not set
 */
function seminargo_get_environment() {
    $env = get_option( 'seminargo_environment', 'production' );
    return in_array( $env, [ 'staging', 'production' ] ) ? $env : 'production';
}

// Define constant for backward compatibility
if ( ! defined( 'SEMINARGO_ENV' ) ) {
    define( 'SEMINARGO_ENV', seminargo_get_environment() );
}

/**
 * Get API endpoint URL based on environment
 * 
 * @return string API GraphQL endpoint URL
 */
function seminargo_get_api_url() {
    $env = seminargo_get_environment();
    if ( $env === 'production' ) {
        return 'https://lister.seminargo.com/pricelist/graphql';
    } else {
        return 'https://lister-staging.seminargo.com/pricelist/graphql';
    }
}

/**
 * Get Finder base URL based on environment
 *
 * @return string Finder base URL
 */
function seminargo_get_finder_url() {
    $env = seminargo_get_environment();
    if ( $env === 'production' ) {
        return 'https://lister.seminargo.com/';
    } else {
        return 'https://lister-staging.seminargo.com/';
    }
}

/**
 * Get Platform Widget URL based on environment
 * 
 * @return string Platform widget base URL
 */
function seminargo_get_platform_widget_url() {
    $env = seminargo_get_environment();
    if ( $env === 'production' ) {
        return 'https://platform-widget.prod.seminargo.eu/';
    } else {
        return 'https://platform-widget.dev.seminargo.eu/';
    }
}

/**
 * Get Platform Widget JS URL based on environment
 * 
 * @return string Platform widget JavaScript URL
 */
function seminargo_get_platform_widget_js_url() {
    $env = seminargo_get_environment();
    if ( $env === 'production' ) {
        return 'https://platform-widget.prod.seminargo.eu/widget.js';
    } else {
        return 'https://platform-widget.dev.seminargo.eu/widget.js';
    }
}

/**
 * Get Lister base URL based on environment
 *
 * @return string Lister base URL
 */
function seminargo_get_lister_url() {
    $env = seminargo_get_environment();
    if ( $env === 'production' ) {
        return 'https://lister.seminargo.com';
    } else {
        return 'https://lister-staging.seminargo.com';
    }
}

/**
 * Convert lister URLs based on current environment
 * Replaces lister.seminargo.com with staging or production URL
 * Also handles old staging URL variants that need conversion
 *
 * @param string $url The URL to convert
 * @return string The converted URL
 */
function seminargo_convert_lister_url( $url ) {
    if ( empty( $url ) || ! is_string( $url ) ) {
        return $url;
    }

    $env = seminargo_get_environment();

    if ( $env === 'staging' ) {
        // Convert production URLs to staging

        // Convert lister.seminargo.com to lister-staging.seminargo.com
        $url = str_replace(
            'https://lister.seminargo.com',
            'https://lister-staging.seminargo.com',
            $url
        );
        $url = str_replace(
            'http://lister.seminargo.com',
            'https://lister-staging.seminargo.com',
            $url
        );

        // Convert finder.dev.seminargo.eu to lister-staging.seminargo.com
        $url = str_replace(
            'https://finder.dev.seminargo.eu',
            'https://lister-staging.seminargo.com',
            $url
        );
        $url = str_replace(
            'http://finder.dev.seminargo.eu',
            'https://lister-staging.seminargo.com',
            $url
        );

        // Convert lister-dev.seminargo.com to lister-staging.seminargo.com
        $url = str_replace(
            'https://lister-dev.seminargo.com',
            'https://lister-staging.seminargo.com',
            $url
        );
        $url = str_replace(
            'http://lister-dev.seminargo.com',
            'https://lister-staging.seminargo.com',
            $url
        );

    } else {
        // Convert staging URLs to production

        // Convert lister-staging.seminargo.com to lister.seminargo.com
        $url = str_replace(
            'https://lister-staging.seminargo.com',
            'https://lister.seminargo.com',
            $url
        );
        $url = str_replace(
            'http://lister-staging.seminargo.com',
            'https://lister.seminargo.com',
            $url
        );

        // Convert finder.dev.seminargo.eu to lister.seminargo.com
        $url = str_replace(
            'https://finder.dev.seminargo.eu',
            'https://lister.seminargo.com',
            $url
        );
        $url = str_replace(
            'http://finder.dev.seminargo.eu',
            'https://lister.seminargo.com',
            $url
        );

        // Convert lister-dev.seminargo.com to lister.seminargo.com
        $url = str_replace(
            'https://lister-dev.seminargo.com',
            'https://lister.seminargo.com',
            $url
        );
        $url = str_replace(
            'http://lister-dev.seminargo.com',
            'https://lister.seminargo.com',
            $url
        );
    }

    return $url;
}

/**
 * Filter navigation menu URLs to convert lister links based on environment
 *
 * @param array $items Navigation menu items
 * @return array Modified menu items
 */
function seminargo_filter_nav_menu_lister_urls( $items ) {
    foreach ( $items as $item ) {
        if ( ! empty( $item->url ) ) {
            $item->url = seminargo_convert_lister_url( $item->url );
        }
    }
    return $items;
}
add_filter( 'wp_nav_menu_objects', 'seminargo_filter_nav_menu_lister_urls', 10, 1 );

/**
 * Filter post content to convert lister URLs based on environment
 *
 * @param string $content Post content
 * @return string Modified content
 */
function seminargo_filter_content_lister_urls( $content ) {
    if ( empty( $content ) ) {
        return $content;
    }

    return seminargo_convert_lister_url( $content );
}
add_filter( 'the_content', 'seminargo_filter_content_lister_urls', 20 );

/**
 * Add Environment Toggle to Admin Menu (removed - now on import page)
 */

/**
 * Render Environment Toggle Page
 */
function seminargo_render_environment_toggle_page() {
    // Handle AJAX toggle
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'seminargo_toggle_environment' && check_admin_referer( 'seminargo_environment_nonce' ) ) {
        $env = sanitize_text_field( $_POST['environment'] );
        if ( in_array( $env, [ 'staging', 'production' ] ) ) {
            update_option( 'seminargo_environment', $env );
            wp_send_json_success( [ 'message' => __( 'Environment updated successfully!', 'seminargo' ) ] );
        }
    }
    
    // Handle form submission (fallback)
    if ( isset( $_POST['seminargo_environment'] ) && check_admin_referer( 'seminargo_environment_nonce' ) ) {
        $env = sanitize_text_field( $_POST['seminargo_environment'] );
        if ( in_array( $env, [ 'staging', 'production' ] ) ) {
            update_option( 'seminargo_environment', $env );
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Environment updated successfully!', 'seminargo' ) . '</strong></p></div>';
        }
    }
    
    $current_env = seminargo_get_environment();
    $is_production = $current_env === 'production';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Environment Settings', 'seminargo' ); ?></h1>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2 style="margin-top: 0;">🔀 <?php esc_html_e( 'Switch Environment', 'seminargo' ); ?></h2>
            
            <form method="post" action="" id="seminargo-environment-form">
                <?php wp_nonce_field( 'seminargo_environment_nonce' ); ?>
                <input type="hidden" name="action" value="seminargo_toggle_environment">
                <input type="hidden" name="seminargo_environment" id="environment-value" value="<?php echo esc_attr( $current_env ); ?>">
                
                <div style="display: flex; align-items: center; gap: 30px; padding: 30px 0;">
                    <div style="flex: 1;">
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px; color: #f59e0b;">
                            🟡 <?php esc_html_e( 'Staging', 'seminargo' ); ?>
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            <?php esc_html_e( 'Development & Testing', 'seminargo' ); ?>
                        </div>
                    </div>
                    
                    <div style="position: relative;">
                        <label class="seminargo-toggle-switch" style="cursor: pointer;">
                            <input type="checkbox" id="environment-toggle" <?php checked( $is_production ); ?>>
                            <span class="seminargo-toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div style="flex: 1; text-align: right;">
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px; color: #10b981;">
                            🟢 <?php esc_html_e( 'Production', 'seminargo' ); ?>
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            <?php esc_html_e( 'Live Site', 'seminargo' ); ?>
                        </div>
                    </div>
                </div>
                
                <div id="toggle-status" style="padding: 15px; background: <?php echo $is_production ? '#d1fae5' : '#fef3c7'; ?>; border-radius: 6px; margin-top: 20px; text-align: center; font-weight: 600; color: <?php echo $is_production ? '#065f46' : '#92400e'; ?>;">
                    <?php if ( $is_production ) : ?>
                        🟢 <?php esc_html_e( 'Currently using PRODUCTION environment', 'seminargo' ); ?>
                    <?php else : ?>
                        🟡 <?php esc_html_e( 'Currently using STAGING environment', 'seminargo' ); ?>
                    <?php endif; ?>
                </div>
                
                <p class="submit" style="margin-top: 20px;">
                    <button type="submit" class="button button-primary button-large" id="save-environment-btn">
                        <?php esc_html_e( 'Save Changes', 'seminargo' ); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2 style="margin-top: 0;">📋 <?php esc_html_e( 'Current URLs', 'seminargo' ); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Service', 'seminargo' ); ?></th>
                        <th><?php esc_html_e( 'URL', 'seminargo' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e( 'API Endpoint', 'seminargo' ); ?></strong></td>
                        <td><code><?php echo esc_html( seminargo_get_api_url() ); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Finder Base URL', 'seminargo' ); ?></strong></td>
                        <td><code><?php echo esc_html( seminargo_get_finder_url() ); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Platform Widget URL', 'seminargo' ); ?></strong></td>
                        <td><code><?php echo esc_html( seminargo_get_platform_widget_url() ); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Platform Widget JS', 'seminargo' ); ?></strong></td>
                        <td><code><?php echo esc_html( seminargo_get_platform_widget_js_url() ); ?></code></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3 style="margin-top: 0;">⚠️ <?php esc_html_e( 'Important', 'seminargo' ); ?></h3>
            <p><?php esc_html_e( 'Changing the environment will immediately affect all API calls and URLs throughout the site. Make sure you are switching to the correct environment for your current needs.', 'seminargo' ); ?></p>
        </div>
    </div>
    
    <style>
        .wrap h1 {
            margin-bottom: 20px;
        }
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
        }
        .card h2 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        /* Toggle Switch Styles */
        .seminargo-toggle-switch {
            position: relative;
            display: inline-block;
            width: 80px;
            height: 40px;
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
            border-radius: 40px;
        }
        
        .seminargo-toggle-slider:before {
            position: absolute;
            content: "";
            height: 32px;
            width: 32px;
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
            transform: translateX(40px);
        }
        
        .seminargo-toggle-switch input:focus + .seminargo-toggle-slider {
            box-shadow: 0 0 1px #10b981;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('#environment-toggle').on('change', function() {
            var isProduction = $(this).is(':checked');
            $('#environment-value').val(isProduction ? 'production' : 'staging');
            
            // Update status display
            var statusDiv = $('#toggle-status');
            if (isProduction) {
                statusDiv.css({
                    'background': '#d1fae5',
                    'color': '#065f46'
                }).html('🟢 <?php esc_html_e( 'Currently using PRODUCTION environment', 'seminargo' ); ?>');
            } else {
                statusDiv.css({
                    'background': '#fef3c7',
                    'color': '#92400e'
                }).html('🟡 <?php esc_html_e( 'Currently using STAGING environment', 'seminargo' ); ?>');
            }
        });
        
        $('#seminargo-environment-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = $('#save-environment-btn');
            var originalText = submitBtn.text();
            
            submitBtn.prop('disabled', true).text('<?php esc_html_e( 'Saving...', 'seminargo' ); ?>');
            
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
                        submitBtn.text('<?php esc_html_e( 'Saved!', 'seminargo' ); ?>').css('background', '#10b981');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Error saving environment. Please try again.', 'seminargo' ); ?>');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    });
    </script>
    <?php
}

// AJAX handler for toggle
add_action( 'wp_ajax_seminargo_toggle_environment', 'seminargo_ajax_toggle_environment' );
function seminargo_ajax_toggle_environment() {
    check_ajax_referer( 'seminargo_environment_nonce', '_wpnonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'seminargo' ) ] );
    }
    
    $env = isset( $_POST['environment'] ) ? sanitize_text_field( $_POST['environment'] ) : '';
    
    if ( ! in_array( $env, [ 'staging', 'production' ] ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid environment', 'seminargo' ) ] );
    }
    
    update_option( 'seminargo_environment', $env );
    wp_send_json_success( [ 'message' => __( 'Environment updated successfully!', 'seminargo' ) ] );
}

/**
 * Brevo Newsletter Signup
 * Adds contact to Brevo with segmentation based on country
 */
add_action( 'wp_ajax_seminargo_newsletter_signup', 'seminargo_ajax_newsletter_signup' );
add_action( 'wp_ajax_nopriv_seminargo_newsletter_signup', 'seminargo_ajax_newsletter_signup' );

function seminargo_ajax_newsletter_signup() {
    // Validate input
    $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
    $anrede = isset( $_POST['anrede'] ) ? sanitize_text_field( $_POST['anrede'] ) : '';
    $vorname = isset( $_POST['vorname'] ) ? sanitize_text_field( $_POST['vorname'] ) : '';
    $nachname = isset( $_POST['nachname'] ) ? sanitize_text_field( $_POST['nachname'] ) : '';
    $country = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';

    // Validation
    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Ungültige E-Mail-Adresse', 'seminargo' ) ] );
    }

    if ( empty( $vorname ) || empty( $nachname ) ) {
        wp_send_json_error( [ 'message' => __( 'Bitte füllen Sie alle Pflichtfelder aus', 'seminargo' ) ] );
    }

    if ( ! in_array( $country, [ 'DE', 'AT' ] ) ) {
        wp_send_json_error( [ 'message' => __( 'Bitte wählen Sie ein Land', 'seminargo' ) ] );
    }

    // Determine list based on country
    // List ID 6 = Deutschland Newsletter
    // List ID 5 = Österreich Newsletter
    $list_id = ( $country === 'DE' ) ? 6 : 5;
    $list_name = ( $country === 'DE' ) ? 'Deutschland Newsletter' : 'Österreich Newsletter';

    // Brevo API Configuration
    $api_key = get_option( 'seminargo_brevo_api_key', '' );
    $api_url = 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation';

    // Format ANREDE for Brevo
    // Herr → "geehrter Herr"
    // Frau → "geehrte Frau"
    $anrede_formatted = ( $anrede === 'Herr' ) ? 'geehrter Herr' : 'geehrte Frau';

    // Prepare data for Brevo (Double Opt-In)
    $contact_data = [
        'email' => $email,
        'attributes' => [
            'VORNAME' => $vorname,
            'NACHNAME' => $nachname,
            'ANREDE_MIGRATE_MIGRATE_MIGRATE_MIGRATE_MIGRATE' => $anrede_formatted,
            'COUNTRY' => $country,
        ],
        'includeListIds' => [ $list_id ], // DOI uses includeListIds
        'templateId' => 32, // DOI confirmation email template
        'redirectionUrl' => home_url('/'), // Redirect to homepage after confirmation
    ];

    // Make API request to Brevo
    $response = wp_remote_post( $api_url, [
        'headers' => [
            'api-key' => $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode( $contact_data ),
        'timeout' => 15,
    ] );

    // Handle API response
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [
            'message' => __( 'Verbindungsfehler. Bitte versuchen Sie es später erneut.', 'seminargo' ),
            'error' => $response->get_error_message(),
        ] );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $response_data = json_decode( $response_body, true );

    // Success codes: 201 (created) or 204 (updated)
    if ( in_array( $response_code, [ 201, 204 ] ) ) {
        wp_send_json_success( [
            'message' => __( 'Fast geschafft! Bitte prüfen Sie Ihr E-Mail-Postfach und bestätigen Sie Ihre Anmeldung.', 'seminargo' ),
        ] );
    } else {
        // API error
        $error_message = isset( $response_data['message'] ) ? $response_data['message'] : __( 'Ein Fehler ist aufgetreten', 'seminargo' );

        wp_send_json_error( [
            'message' => $error_message,
        ] );
    }
}

/**
 * Hotel Newsletter Signup (for landing page)
 * Adds hotel contact to Brevo newsletter list
 */
add_action( 'wp_ajax_seminargo_hotel_newsletter_signup', 'seminargo_ajax_hotel_newsletter_signup' );
add_action( 'wp_ajax_nopriv_seminargo_hotel_newsletter_signup', 'seminargo_ajax_hotel_newsletter_signup' );

function seminargo_ajax_hotel_newsletter_signup() {
    // Validate input
    $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
    $vorname = isset( $_POST['vorname'] ) ? sanitize_text_field( $_POST['vorname'] ) : '';
    $nachname = isset( $_POST['nachname'] ) ? sanitize_text_field( $_POST['nachname'] ) : '';
    $hotelname = isset( $_POST['hotelname'] ) ? sanitize_text_field( $_POST['hotelname'] ) : '';

    // Validation
    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Ungültige E-Mail-Adresse', 'seminargo' ) ] );
    }

    if ( empty( $vorname ) || empty( $nachname ) || empty( $hotelname ) ) {
        wp_send_json_error( [ 'message' => __( 'Bitte füllen Sie alle Pflichtfelder aus', 'seminargo' ) ] );
    }

    // Brevo API Configuration
    $api_key = get_option( 'seminargo_brevo_api_key', '' );
    $api_url = 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation';

    // Prepare data for Brevo (Double Opt-In)
    $contact_data = [
        'email' => $email,
        'attributes' => [
            'VORNAME' => $vorname,
            'NACHNAME' => $nachname,
            'FIRMA' => $hotelname,
        ],
        'includeListIds' => [ 99 ], // Hotelnewsletter list
        'templateId' => 32, // DOI confirmation email template
        'redirectionUrl' => home_url('/'), // Redirect to homepage after confirmation
    ];

    // Make API request to Brevo
    $response = wp_remote_post( $api_url, [
        'headers' => [
            'api-key' => $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode( $contact_data ),
        'timeout' => 15,
    ] );

    // Handle API response
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [
            'message' => __( 'Verbindungsfehler. Bitte versuchen Sie es später erneut.', 'seminargo' ),
        ] );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $response_data = json_decode( $response_body, true );

    // Success codes: 201 (created) or 204 (updated)
    if ( in_array( $response_code, [ 201, 204 ] ) ) {
        wp_send_json_success( [
            'message' => __( 'Fast geschafft! Bitte prüfen Sie Ihr E-Mail-Postfach und bestätigen Sie Ihre Anmeldung.', 'seminargo' ),
        ] );
    } else {
        // API error
        $error_message = isset( $response_data['message'] ) ? $response_data['message'] : __( 'Ein Fehler ist aufgetreten', 'seminargo' );

        wp_send_json_error( [
            'message' => $error_message,
        ] );
    }
}

/**
 * Set content width
 */
if ( ! isset( $content_width ) ) {
    $content_width = 1200;
}

/**
 * Include Logo Slider Manager
 */
require_once SEMINARGO_INC_PATH . 'logo-slider-manager.php';

/**
 * Include Homepage Content Admin Interface
 */
require_once SEMINARGO_INC_PATH . 'homepage-content-admin.php';

/**
 * Theme setup
 */
if ( ! function_exists( 'seminargo_setup' ) ) {
    function seminargo_setup() {
        // Add language support
        load_theme_textdomain( 'seminargo', SEMINARGO_THEME_PATH . '/languages' );

        // Add default RSS feed links to head
        add_theme_support( 'automatic-feed-links' );

        // Let WordPress manage the document title
        add_theme_support( 'title-tag' );

        // Enable support for Post Thumbnails
        add_theme_support( 'post-thumbnails' );

        // Add custom image sizes
        add_image_size( 'seminargo-featured', 1920, 1080, true );
        add_image_size( 'seminargo-thumbnail', 600, 400, true );
        add_image_size( 'seminargo-square', 800, 800, true );

        // Register navigation menus
        register_nav_menus( array(
            'primary'         => esc_html__( 'Primary Menu', 'seminargo' ),
            'seminargo-side'  => esc_html__( 'Seminargo Sidemenu', 'seminargo' ),
            'footer'          => esc_html__( 'Footer Menu', 'seminargo' ),
            'social'          => esc_html__( 'Social Links Menu', 'seminargo' ),
        ) );

        // HTML5 support
        add_theme_support( 'html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'script',
            'style',
            'navigation-widgets',
        ) );

        // Add support for core custom logo
        add_theme_support( 'custom-logo', array(
            'height'      => 100,
            'width'       => 400,
            'flex-height' => true,
            'flex-width'  => true,
            'header-text' => array( 'site-title', 'site-description' ),
        ) );

        // Add theme support for selective refresh for widgets
        add_theme_support( 'customize-selective-refresh-widgets' );

        // Add support for Block Styles
        add_theme_support( 'wp-block-styles' );

        // Add support for full and wide align images
        add_theme_support( 'align-wide' );

        // Add support for editor styles
        add_theme_support( 'editor-styles' );
        add_editor_style( 'assets/css/editor-style.css' );

        // Add support for responsive embedded content
        add_theme_support( 'responsive-embeds' );

        // Add support for custom background
        add_theme_support( 'custom-background', array(
            'default-color' => 'ffffff',
            'default-image' => '',
        ) );

        // Add support for custom header
        add_theme_support( 'custom-header', array(
            'default-image'      => '',
            'default-text-color' => '000000',
            'width'              => 1920,
            'height'             => 500,
            'flex-height'        => true,
        ) );

        // Add support for WooCommerce
        add_theme_support( 'woocommerce' );
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );

        // Add excerpt support for pages
        add_post_type_support( 'page', 'excerpt' );
    }
}
add_action( 'after_setup_theme', 'seminargo_setup' );




/**
 * Include custom functions
 */
// Template functions
if ( file_exists( SEMINARGO_INC_PATH . 'template-functions.php' ) ) {
    require SEMINARGO_INC_PATH . 'template-functions.php';
}

// Template tags
if ( file_exists( SEMINARGO_INC_PATH . 'template-tags.php' ) ) {
    require SEMINARGO_INC_PATH . 'template-tags.php';
}

// Customizer
if ( file_exists( SEMINARGO_INC_PATH . 'customizer.php' ) ) {
    require SEMINARGO_INC_PATH . 'customizer.php';
}

// Custom post types
if ( file_exists( SEMINARGO_INC_PATH . 'custom-post-types.php' ) ) {
    require SEMINARGO_INC_PATH . 'custom-post-types.php';
}

// AJAX handlers
if ( file_exists( SEMINARGO_INC_PATH . 'ajax-handlers.php' ) ) {
    require SEMINARGO_INC_PATH . 'ajax-handlers.php';
}

// Hotel importer (API sync)
if ( file_exists( SEMINARGO_INC_PATH . 'hotel-importer.php' ) ) {
    require SEMINARGO_INC_PATH . 'hotel-importer.php';
}

// WP-CLI Hotel import command
if ( file_exists( SEMINARGO_INC_PATH . 'cli-hotel-import.php' ) ) {
    require SEMINARGO_INC_PATH . 'cli-hotel-import.php';
}

// Collection post type (SEO landing pages)
if ( file_exists( SEMINARGO_INC_PATH . 'post-type-collection.php' ) ) {
    require SEMINARGO_INC_PATH . 'post-type-collection.php';
}

// Contact form settings
if ( file_exists( SEMINARGO_INC_PATH . 'contact-settings.php' ) ) {
    require SEMINARGO_INC_PATH . 'contact-settings.php';
}

// Contact form database
if ( file_exists( SEMINARGO_INC_PATH . 'contact-form-db.php' ) ) {
    require SEMINARGO_INC_PATH . 'contact-form-db.php';
}

// Embedded mode support (iframe integration)
if ( file_exists( SEMINARGO_INC_PATH . 'embedded-mode.php' ) ) {
    require SEMINARGO_INC_PATH . 'embedded-mode.php';
}

// Menu icons functionality
if ( file_exists( SEMINARGO_INC_PATH . 'menu-icons.php' ) ) {
    require SEMINARGO_INC_PATH . 'menu-icons.php';
}

// AJAX handler for hotel search in admin
add_action( 'wp_ajax_search_hotels_for_selector', 'seminargo_ajax_search_hotels_for_selector' );
function seminargo_ajax_search_hotels_for_selector() {
    $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';
    $exclude_ids = isset( $_POST['exclude_ids'] ) ? array_map( 'intval', explode( ',', $_POST['exclude_ids'] ) ) : [];

    if ( strlen( $query ) < 2 ) {
        wp_send_json_success( [] );
    }

    // Optimized query for large datasets (5000+ hotels)
    $args = [
        'post_type'                => 'hotel',
        'post_status'              => 'publish',
        's'                        => $query,
        'posts_per_page'           => 50, // Limit results for performance
        'orderby'                  => 'title',
        'order'                    => 'ASC',
        'no_found_rows'            => true, // Skip counting total results (faster)
        'update_post_meta_cache'   => false, // Don't cache meta - we only need one field
        'update_post_term_cache'   => false, // Don't cache terms - not needed
        'suppress_filters'         => false, // Keep search filters active
    ];

    if ( ! empty( $exclude_ids ) ) {
        $args['post__not_in'] = $exclude_ids;
    }

    $hotels = new WP_Query( $args );

    $results = [];

    if ( $hotels->have_posts() ) {
        // Use get_post() instead of the_post() for better performance
        foreach ( $hotels->posts as $post ) {
            // Get location directly from meta without loading full post
            $location = get_post_meta( $post->ID, 'business_city', true );
            
            $results[] = [
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'location' => $location ?: '',
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success( $results );
}

// Widget areas
if ( file_exists( SEMINARGO_INC_PATH . 'widgets.php' ) ) {
    require SEMINARGO_INC_PATH . 'widgets.php';
}

// Assets enqueue (CSS/JS)
if ( file_exists( SEMINARGO_INC_PATH . 'assets-enqueue.php' ) ) {
    require SEMINARGO_INC_PATH . 'assets-enqueue.php';
}

// Elementor compatibility
if ( file_exists( SEMINARGO_INC_PATH . 'elementor-compatibility.php' ) ) {
    require SEMINARGO_INC_PATH . 'elementor-compatibility.php';
}

// Content filters (excerpt, mime types)
if ( file_exists( SEMINARGO_INC_PATH . 'content-filters.php' ) ) {
    require SEMINARGO_INC_PATH . 'content-filters.php';
}

// Admin tweaks
if ( file_exists( SEMINARGO_INC_PATH . 'admin-tweaks.php' ) ) {
    require SEMINARGO_INC_PATH . 'admin-tweaks.php';
}




/**
 * Output CTA Section with custom fields (Global - affects multiple pages)
 */
function seminargo_cta_section() {
    // Get homepage ID for custom fields
    $homepage_id = get_option( 'page_on_front' );

    // Get custom field values with fallbacks to original defaults
    $eyebrow = get_post_meta( $homepage_id, 'cta_eyebrow', true ) ?: 'Schnell · Persönlich · Kostenlos';
    $heading = get_post_meta( $homepage_id, 'cta_heading', true ) ?: 'Ihre Traum-Location in 24 Stunden';
    $description = get_post_meta( $homepage_id, 'cta_description', true ) ?: 'Unsere Experten kennen jede Location persönlich. Sie sagen uns, was Sie brauchen – wir liefern maßgeschneiderte Empfehlungen. Professionell, schnell und 100% kostenfrei.';

    $btn1_text = get_post_meta( $homepage_id, 'cta_btn1_text', true ) ?: 'Sofort Anrufen';
    $btn1_url = get_post_meta( $homepage_id, 'cta_btn1_url', true ) ?: 'tel:+43190858';

    $btn2_text = get_post_meta( $homepage_id, 'cta_btn2_text', true ) ?: 'Beratung anfragen';
    $btn2_url = get_post_meta( $homepage_id, 'cta_btn2_url', true ) ?: 'mailto:info@seminargo.com';

    get_template_part( 'template-parts/cta-section', null, [
        'eyebrow' => $eyebrow,
        'heading' => $heading,
        'description' => $description,
        'buttons' => [
            [
                'text' => $btn1_text,
                'url' => $btn1_url,
                'icon' => 'phone',
                'style' => 'white'
            ],
            [
                'text' => $btn2_text,
                'url' => $btn2_url,
                'icon' => 'email',
                'style' => 'outline-white'
            ]
        ],
        'style' => 'gradient'
    ] );
}

/**
 * Get hotel rooms label (Global - from homepage custom fields)
 *
 * @return string Rooms label
 */
function seminargo_get_rooms_label() {
    $homepage_id = get_option( 'page_on_front' );
    return get_post_meta( $homepage_id, 'hotel_rooms_label', true ) ?: 'Tagungsräume';
}

/**
 * Get hotel capacity label (Global - from homepage custom fields)
 *
 * @return string Capacity label
 */
function seminargo_get_capacity_label() {
    $homepage_id = get_option( 'page_on_front' );
    return get_post_meta( $homepage_id, 'hotel_capacity_label', true ) ?: 'max. Personen';
}

/**
 * ============================================
 * MEDIA LIBRARY ORGANIZATION
 * ============================================
 * Custom filter to organize media by type
 */

/**
 * Get image type based on parent post
 */
function seminargo_get_image_type( $attachment_id ) {
    $parent_id = wp_get_post_parent_id( $attachment_id );

    if ( ! $parent_id ) {
        return 'uncategorized';
    }

    $parent_type = get_post_type( $parent_id );

    switch ( $parent_type ) {
        case 'hotel':
            return 'hotel';
        case 'team':
            return 'team';
        case 'post':
            return 'blog';
        case 'page':
            return 'theme';
        case 'collection':
            return 'collection';
        case 'faq':
            return 'faq';
        default:
            return 'other';
    }
}

/**
 * Add dropdown filter to media library LIST view
 */
function seminargo_add_media_filter_dropdown( $post_type, $which ) {
    // Only show on media library in the correct position
    // 'bar' is the location identifier for media library filters
    if ( 'bar' !== $which ) {
        return;
    }

    $selected = isset( $_GET['seminargo_media_type'] ) ? $_GET['seminargo_media_type'] : '';
    ?>
    <select name="seminargo_media_type" class="attachment-filters">
        <option value="">All Media Types</option>
        <option value="hotel" <?php selected( $selected, 'hotel' ); ?>>🏨 Hotel Images</option>
        <option value="team" <?php selected( $selected, 'team' ); ?>>👥 Team Images</option>
        <option value="blog" <?php selected( $selected, 'blog' ); ?>>📝 Blog Images</option>
        <option value="theme" <?php selected( $selected, 'theme' ); ?>>🎨 Theme/Page Images</option>
        <option value="collection" <?php selected( $selected, 'collection' ); ?>>📚 Collection Images</option>
        <option value="uncategorized" <?php selected( $selected, 'uncategorized' ); ?>>➖ Uncategorized</option>
        <option value="other" <?php selected( $selected, 'other' ); ?>>📎 Other</option>
    </select>
    <?php
}
add_action( 'restrict_manage_posts', 'seminargo_add_media_filter_dropdown', 10, 2 );

/**
 * Filter media library query based on selection
 */
function seminargo_filter_media_by_type( $query ) {
    global $pagenow, $typenow;

    if ( $pagenow === 'upload.php' && isset( $_GET['seminargo_media_type'] ) && $_GET['seminargo_media_type'] !== '' ) {
        $media_type = $_GET['seminargo_media_type'];

        // Get all attachments
        $all_attachments = get_posts( array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ) );

        // Filter attachments by type
        $filtered_ids = array();
        foreach ( $all_attachments as $attachment_id ) {
            $type = seminargo_get_image_type( $attachment_id );
            if ( $type === $media_type ) {
                $filtered_ids[] = $attachment_id;
            }
        }

        // If no matches, set to impossible ID to show no results
        if ( empty( $filtered_ids ) ) {
            $filtered_ids = array( 0 );
        }

        $query['post__in'] = $filtered_ids;
    }

    return $query;
}
add_filter( 'request', 'seminargo_filter_media_by_type' );

/**
 * Add custom column to media library
 */
function seminargo_add_media_type_column( $columns ) {
    $columns['media_type'] = __( 'Image Type', 'seminargo' );
    return $columns;
}
add_filter( 'manage_media_columns', 'seminargo_add_media_type_column' );

/**
 * Display image type in custom column
 */
function seminargo_display_media_type_column( $column_name, $attachment_id ) {
    if ( $column_name === 'media_type' ) {
        $type = seminargo_get_image_type( $attachment_id );

        $labels = array(
            'hotel' => '<span style="color: #AC2A6E; font-weight: 600;">🏨 Hotel</span>',
            'team' => '<span style="color: #10b981; font-weight: 600;">👥 Team</span>',
            'blog' => '<span style="color: #3b82f6; font-weight: 600;">📝 Blog</span>',
            'theme' => '<span style="color: #8b5cf6; font-weight: 600;">🎨 Theme</span>',
            'collection' => '<span style="color: #f59e0b; font-weight: 600;">📚 Collection</span>',
            'faq' => '<span style="color: #6b7280; font-weight: 600;">❓ FAQ</span>',
            'uncategorized' => '<span style="color: #9ca3af;">➖ Uncategorized</span>',
            'other' => '<span style="color: #9ca3af;">📎 Other</span>',
        );

        echo isset( $labels[$type] ) ? $labels[$type] : $labels['other'];
    }
}
add_action( 'manage_media_custom_column', 'seminargo_display_media_type_column', 10, 2 );

/**
 * Make the image type column sortable
 */
function seminargo_make_media_type_sortable( $columns ) {
    $columns['media_type'] = 'media_type';
    return $columns;
}
add_filter( 'manage_upload_sortable_columns', 'seminargo_make_media_type_sortable' );

/**
 * Add filter dropdown to media modal (grid view)
 */
function seminargo_add_media_filter_to_modal() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        if (typeof wp !== 'undefined' && wp.media && wp.media.view) {
            // Extend the media toolbar
            wp.media.view.AttachmentFilters.Seminargo = wp.media.view.AttachmentFilters.extend({
                createFilters: function() {
                    var filters = {};

                    filters.all = {
                        text: 'All Media Types',
                        props: {
                            seminargo_media_type: ''
                        }
                    };

                    filters.hotel = {
                        text: '🏨 Hotel Images',
                        props: {
                            seminargo_media_type: 'hotel'
                        }
                    };

                    filters.team = {
                        text: '👥 Team Images',
                        props: {
                            seminargo_media_type: 'team'
                        }
                    };

                    filters.blog = {
                        text: '📝 Blog Images',
                        props: {
                            seminargo_media_type: 'blog'
                        }
                    };

                    filters.theme = {
                        text: '🎨 Theme/Page Images',
                        props: {
                            seminargo_media_type: 'theme'
                        }
                    };

                    filters.collection = {
                        text: '📚 Collection Images',
                        props: {
                            seminargo_media_type: 'collection'
                        }
                    };

                    filters.uncategorized = {
                        text: '➖ Uncategorized',
                        props: {
                            seminargo_media_type: 'uncategorized'
                        }
                    };

                    filters.other = {
                        text: '📎 Other',
                        props: {
                            seminargo_media_type: 'other'
                        }
                    };

                    this.filters = filters;
                }
            });

            // Add the filter to media library
            var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
            wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
                createToolbar: function() {
                    AttachmentsBrowser.prototype.createToolbar.apply(this, arguments);
                    this.toolbar.set('seminargo_filter', new wp.media.view.AttachmentFilters.Seminargo({
                        controller: this.controller,
                        model: this.collection.props,
                        priority: -80
                    }).render());
                }
            });
        }
    });
    </script>
    <?php
}
add_action( 'admin_footer-upload.php', 'seminargo_add_media_filter_to_modal' );

/**
 * Filter attachments by custom media type in AJAX requests
 */
function seminargo_filter_ajax_query( $query ) {
    if ( isset( $_REQUEST['query']['seminargo_media_type'] ) && $_REQUEST['query']['seminargo_media_type'] !== '' ) {
        $media_type = sanitize_text_field( $_REQUEST['query']['seminargo_media_type'] );

        // Get all attachments
        $all_attachments = get_posts( array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ) );

        // Filter attachments by type
        $filtered_ids = array();
        foreach ( $all_attachments as $attachment_id ) {
            $type = seminargo_get_image_type( $attachment_id );
            if ( $type === $media_type ) {
                $filtered_ids[] = $attachment_id;
            }
        }

        // If no matches, set to impossible ID to show no results
        if ( empty( $filtered_ids ) ) {
            $filtered_ids = array( 0 );
        }

        $query['post__in'] = $filtered_ids;
    }

    return $query;
}
add_filter( 'ajax_query_attachments_args', 'seminargo_filter_ajax_query' );

/**
 * ============================================
 *  COLLECTION ICON SELECTOR
 * ============================================
 */

/**
 * Get available icon options for collections
 */
function seminargo_get_collection_icons() {
    return array(
        'seminar' => array(
            'name' => 'Seminar / Schulung',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>'
        ),
        'spa' => array(
            'name' => 'Seminar & Spa / Wellness',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>'
        ),
        'tagung' => array(
            'name' => 'Tagung / Meeting',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'
        ),
        'veranstaltung' => array(
            'name' => 'Veranstaltung / Event',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>'
        ),
        'workshop' => array(
            'name' => 'Workshop / Training',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>'
        ),
        'konferenz' => array(
            'name' => 'Konferenz / Großveranstaltung',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>'
        ),
        'weihnachtsfeier' => array(
            'name' => 'Weihnachtsfeier',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle><path d="M12 14l-3-3m6 0l-3 3m0-3v6"></path></svg>'
        ),
        'firmenfeier' => array(
            'name' => 'Firmenfeier / Betriebsfeier',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>'
        ),
        'incentive' => array(
            'name' => 'Incentive / Teambuilding',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>'
        ),
        'messe' => array(
            'name' => 'Messe / Ausstellung',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>'
        ),
        'hochzeit' => array(
            'name' => 'Hochzeit / Feier',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>'
        ),
        'geburtstag' => array(
            'name' => 'Geburtstag / Jubiläum',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="8" width="18" height="14" rx="2"></rect><path d="M12 2v6M8 2v6M16 2v6"></path></svg>'
        ),
        'outdoor' => array(
            'name' => 'Outdoor Event',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 20h18M5.6 20l6.4-9 6.4 9M12 11V3"></path></svg>'
        ),
        'produktpraesentation' => array(
            'name' => 'Produktpräsentation',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>'
        ),
        'gala' => array(
            'name' => 'Gala / Abendveranstaltung',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'
        ),
    );
}

/**
 * Add meta box for collection icon selection
 */
function seminargo_add_collection_icon_meta_box() {
    add_meta_box(
        'collection_icon_meta_box',
        'Collection Icon',
        'seminargo_collection_icon_meta_box_callback',
        'collection',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'seminargo_add_collection_icon_meta_box' );

/**
 * Collection icon meta box callback
 */
function seminargo_collection_icon_meta_box_callback( $post ) {
    wp_nonce_field( 'seminargo_collection_icon_nonce', 'seminargo_collection_icon_nonce' );

    $selected_icon = get_post_meta( $post->ID, 'collection_icon', true );
    $icons = seminargo_get_collection_icons();

    ?>
    <style>
        .icon-selector-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .icon-option {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .icon-option:hover {
            border-color: #AC2A6E;
            background: #f9f9f9;
        }
        .icon-option.selected {
            border-color: #AC2A6E;
            background: rgba(172, 42, 110, 0.05);
        }
        .icon-option input[type="radio"] {
            margin-right: 10px;
        }
        .icon-preview {
            width: 32px;
            height: 32px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .icon-preview svg {
            width: 100%;
            height: 100%;
            stroke: #AC2A6E;
        }
        .icon-name {
            font-size: 13px;
            font-weight: 500;
        }
    </style>

    <div class="icon-selector-grid">
        <?php foreach ( $icons as $key => $icon ) : ?>
            <label class="icon-option <?php echo ( $selected_icon === $key ) ? 'selected' : ''; ?>">
                <input
                    type="radio"
                    name="collection_icon"
                    value="<?php echo esc_attr( $key ); ?>"
                    <?php checked( $selected_icon, $key ); ?>
                    onchange="document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected')); this.closest('.icon-option').classList.add('selected');"
                >
                <div class="icon-preview">
                    <?php echo $icon['svg']; ?>
                </div>
                <span class="icon-name"><?php echo esc_html( $icon['name'] ); ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <p style="margin-top: 15px; font-size: 12px; color: #666;">
        Wählen Sie ein Icon für diese Collection. Wird auf der Homepage in der "Veranstaltungsarten" Sektion angezeigt.
    </p>
    <?php
}

/**
 * Save collection icon meta
 */
function seminargo_save_collection_icon_meta( $post_id ) {
    if ( ! isset( $_POST['seminargo_collection_icon_nonce'] ) ||
         ! wp_verify_nonce( $_POST['seminargo_collection_icon_nonce'], 'seminargo_collection_icon_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['collection_icon'] ) ) {
        update_post_meta( $post_id, 'collection_icon', sanitize_text_field( $_POST['collection_icon'] ) );
    }
}
add_action( 'save_post_collection', 'seminargo_save_collection_icon_meta' );

/**
 * ============================================
 *  COLLECTION HOME EXCERPT META BOX
 * ============================================
 */

/**
 * Add Home Excerpt meta box to collection post type
 */
function seminargo_add_collection_home_excerpt_meta_box() {
    add_meta_box(
        'collection_home_excerpt',
        'Homepage Kurztext',
        'seminargo_collection_home_excerpt_callback',
        'collection',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'seminargo_add_collection_home_excerpt_meta_box' );

/**
 * Home Excerpt meta box callback
 */
function seminargo_collection_home_excerpt_callback( $post ) {
    wp_nonce_field( 'seminargo_home_excerpt_nonce', 'seminargo_home_excerpt_nonce' );

    $home_excerpt = get_post_meta( $post->ID, 'home_excerpt', true );
    ?>
    <div style="padding: 10px 0;">
        <p style="margin-bottom: 10px; color: #666;">
            <strong>Hinweis:</strong> Dieser Text wird auf der Homepage in der Kachel angezeigt (unterhalb des Titels).
        </p>
        <textarea
            name="home_excerpt"
            id="home_excerpt"
            rows="3"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
            placeholder="z.B. Perfekte Locations für Ihre Seminare und Schulungen"
        ><?php echo esc_textarea( $home_excerpt ); ?></textarea>
        <p style="margin-top: 8px; color: #666; font-size: 13px;">
            Empfohlene Länge: 50-80 Zeichen für optimale Darstellung
        </p>
    </div>
    <?php
}

/**
 * Save Home Excerpt meta
 */
function seminargo_save_collection_home_excerpt_meta( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['seminargo_home_excerpt_nonce'] ) ||
         ! wp_verify_nonce( $_POST['seminargo_home_excerpt_nonce'], 'seminargo_home_excerpt_nonce' ) ) {
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

    // Save home excerpt
    if ( isset( $_POST['home_excerpt'] ) ) {
        $home_excerpt = sanitize_textarea_field( $_POST['home_excerpt'] );
        update_post_meta( $post_id, 'home_excerpt', $home_excerpt );
    } else {
        delete_post_meta( $post_id, 'home_excerpt' );
    }
}
add_action( 'save_post_collection', 'seminargo_save_collection_home_excerpt_meta' );

/**
 * ============================================
 *  HOMEPAGE COLLECTIONS META BOX
 * ============================================
 */

/**
 * Add meta box for homepage collection selection
 */
function seminargo_add_homepage_meta_box() {
    add_meta_box(
        'homepage_collections_meta_box',
        'Homepage Collection Einstellungen',
        'seminargo_homepage_collections_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'seminargo_add_homepage_meta_box' );

/**
 * Meta box callback function
 */
function seminargo_homepage_collections_meta_box_callback( $post ) {
    // Only show on front page (check if this page is set as front page)
    $front_page_id = get_option( 'page_on_front' );
    if ( $front_page_id && $front_page_id != $post->ID ) {
        // If a front page is set and this isn't it, don't show the meta box
        echo '<p style="padding: 15px; color: #666;">Diese Einstellungen sind nur für die Startseite verfügbar. Diese Seite ist nicht als Startseite gesetzt.</p>';
        return;
    }

    // Add nonce for security
    wp_nonce_field( 'seminargo_homepage_collections_nonce', 'seminargo_homepage_collections_nonce' );

    // Get current values
    $event_type_collections = get_post_meta( $post->ID, 'event_type_collections', true );
    $popular_location_collections = get_post_meta( $post->ID, 'popular_location_collections', true );

    // Get visibility settings (default to true/shown)
    $show_top_hotels = get_post_meta( $post->ID, 'show_top_hotels_section', true ) !== '0';
    $show_event_types = get_post_meta( $post->ID, 'show_event_types_section', true ) !== '0';
    $show_locations = get_post_meta( $post->ID, 'show_locations_section', true ) !== '0';

    // Get filter tab visibility settings (default to true/shown)
    $show_top_tab = get_post_meta( $post->ID, 'show_top_hotels_tab', true ) !== '0';
    $show_theme_tab = get_post_meta( $post->ID, 'show_theme_filter_tab', true ) !== '0';
    $show_location_tab = get_post_meta( $post->ID, 'show_location_filter_tab', true ) !== '0';

    // Get hero CTA text settings
    $hero_cta_title = get_post_meta( $post->ID, 'hero_cta_title', true );
    $hero_cta_subtitle = get_post_meta( $post->ID, 'hero_cta_subtitle', true );

    // Convert to arrays
    $selected_event_types = ! empty( $event_type_collections ) ? array_map( 'intval', explode( ',', $event_type_collections ) ) : array();
    $selected_locations = ! empty( $popular_location_collections ) ? array_map( 'intval', explode( ',', $popular_location_collections ) ) : array();

    // Query all collections
    $all_collections = get_posts( array(
        'post_type'      => 'collection',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );
    
    // Get all hotel IDs for selectors (used in multiple places)
    $all_hotel_ids_for_selectors = get_posts( array(
        'post_type'      => 'hotel',
        'posts_per_page' => 500,
        'fields'         => 'ids',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );

    // Create lookup array for collection titles
    $collections_lookup = array();
    foreach ( $all_collections as $collection ) {
        $collections_lookup[ $collection->ID ] = $collection->post_title;
    }

    ?>
    <div class="homepage-collections-settings">
        <style>
            .homepage-collections-settings h3 {
                margin-top: 20px;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }
            .helper-text {
                color: #666;
                font-style: italic;
                margin-bottom: 15px;
            }
            .collections-manager {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 30px;
            }
            .selected-collections {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 6px;
                border: 2px solid #AC2A6E;
            }
            .available-collections {
                background: #fff;
                padding: 15px;
                border-radius: 6px;
                border: 1px solid #ddd;
            }
            .collections-manager h4 {
                margin-top: 0;
                font-size: 14px;
                font-weight: 600;
                color: #333;
            }
            .sortable-list {
                list-style: none;
                margin: 0;
                padding: 0;
                min-height: 50px;
            }
            #available-top-hotels {
                max-height: 300px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            #selected-top-hotels {
                max-height: 250px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .sortable-list li {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px;
                margin-bottom: 8px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: move;
                transition: all 0.2s;
            }
            .sortable-list li:hover {
                background: #f0f0f0;
                border-color: #AC2A6E;
            }
            .sortable-list li.ui-sortable-helper {
                opacity: 0.8;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            .collection-item-handle {
                display: flex;
                align-items: center;
                gap: 8px;
                flex: 1;
            }
            .drag-handle {
                color: #999;
                cursor: move;
            }
            .remove-btn {
                padding: 4px 8px;
                background: #dc3545;
                color: white;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            }
            .remove-btn:hover {
                background: #c82333;
            }
            .add-btn {
                padding: 4px 8px;
                background: #AC2A6E;
                color: white;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            }
            .add-btn:hover {
                background: #8A1F56;
            }
            .available-list li {
                cursor: pointer;
            }
            .collection-count {
                color: #AC2A6E;
                font-weight: 600;
                font-size: 12px;
            }
            .visibility-settings {
                background: #f0f6ff;
                border: 2px solid #2271b1;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 30px;
            }
            .visibility-settings h3 {
                margin-top: 0 !important;
                border-bottom: none !important;
                color: #2271b1;
                font-size: 16px;
            }
            .visibility-group {
                margin-bottom: 20px;
            }
            .visibility-group:last-child {
                margin-bottom: 0;
            }
            .visibility-group h4 {
                font-size: 14px;
                margin: 0 0 12px 0;
                color: #1d2327;
                font-weight: 600;
            }
            .checkbox-group {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 12px;
            }
            .checkbox-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px;
                background: white;
                border-radius: 4px;
                border: 1px solid #ddd;
                transition: all 0.2s;
            }
            .checkbox-item:hover {
                border-color: #AC2A6E;
                background: #fafafa;
            }
            .checkbox-item input[type="checkbox"] {
                width: 18px;
                height: 18px;
                margin: 0;
                cursor: pointer;
            }
            .checkbox-item label {
                margin: 0;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
                flex: 1;
            }
        </style>

        <!-- Hero Section Settings -->
        <div class="visibility-settings" style="background: #f0f6ff; border-color: #2271b1;">
            <h3>✏️ Hero Bereich (Oberer Bereich)</h3>
            <p style="font-size: 12px; color: #666; margin: 0 0 15px 0;">Bearbeiten Sie die Hauptüberschrift und Beschreibung im Hero-Bereich.</p>
            
            <?php
            $hero_h1 = get_post_meta( $post->ID, 'hero_h1', true );
            $hero_description = get_post_meta( $post->ID, 'hero_description', true );
            ?>
            
            <div style="margin-bottom: 15px;">
                <label for="hero_h1" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
                    H1 Überschrift:
                </label>
                <input type="text"
                       id="hero_h1"
                       name="hero_h1"
                       value="<?php echo esc_attr( $hero_h1 ?: 'Finden Sie Ihr perfektes Tagungshotel' ); ?>"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                       placeholder="z.B. Finden Sie Ihr perfektes Tagungshotel">
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">
                    Die Hauptüberschrift über dem Such-Widget.
                </p>
            </div>

            <div>
                <label for="hero_description" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
                    Beschreibung:
                </label>
                <input type="text"
                       id="hero_description"
                       name="hero_description"
                       value="<?php echo esc_attr( $hero_description ?: 'Über 24.000 Seminarhotels in Deutschland und Österreich' ); ?>"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                       placeholder="z.B. Über 24.000 Seminarhotels in Deutschland und Österreich">
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">
                    Die Beschreibung unter der H1 Überschrift.
                </p>
            </div>
        </div>

        <!-- Hero CTA Text Settings -->
        <div class="visibility-settings" style="background: #fff5f7; border-color: #AC2A6E;">
            <h3>✏️ Hero Bildabschnitt (Unter dem Widget)</h3>
            <p style="font-size: 12px; color: #666; margin: 0 0 15px 0;">Bearbeiten Sie den Bildabschnitt mit Hintergrundbild, Text und Button.</p>
            
            <?php
            $hero_background_image = get_post_meta( $post->ID, 'hero_background_image', true );
            $hero_button_text = get_post_meta( $post->ID, 'hero_button_text', true );
            $hero_button_link = get_post_meta( $post->ID, 'hero_button_link', true );
            $default_hero_image = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&h=600&fit=crop';
            $display_image = $hero_background_image ?: $default_hero_image;
            $is_custom_image = $hero_background_image && $hero_background_image !== $default_hero_image;
            ?>
            
            <div style="margin-bottom: 15px;">
                <label for="hero_background_image" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
                    Hintergrundbild:
                </label>
                <div class="hero-background-image-preview" style="margin-bottom: 10px;">
                    <img src="<?php echo esc_url( $display_image ); ?>" style="max-width: 400px; height: auto; border-radius: 8px; border: 1px solid #ddd;">
                </div>
                <input type="hidden" id="hero_background_image" name="hero_background_image" value="<?php echo esc_attr( $hero_background_image ?: $default_hero_image ); ?>">
                <button type="button" class="button button-secondary" id="upload_hero_background_image" style="margin-right: 8px;">
                    <?php esc_html_e( 'Bild auswählen', 'seminargo' ); ?>
                </button>
                <button type="button" class="button" id="remove_hero_background_image" <?php echo ! $is_custom_image ? 'style="display:none;"' : ''; ?>>
                    <?php esc_html_e( 'Entfernen', 'seminargo' ); ?>
                </button>
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">
                    Wählen Sie ein Hintergrundbild für den Hero-Bildabschnitt aus der Medienbibliothek aus.
                </p>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="hero_cta_title" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
                    H2 Überschrift:
                </label>
                <input type="text"
                       id="hero_cta_title"
                       name="hero_cta_title"
                       value="<?php echo esc_attr( $hero_cta_title ?: 'Kreativer Workshop im Grünen?' ); ?>"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                       placeholder="z.B. Kreativer Workshop im Grünen?">
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">
                    Die H2 Überschrift auf dem Hero-Bild.
                </p>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="hero_cta_subtitle" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
                    Beschreibung (Paragraph):
                </label>
                <input type="text"
                       id="hero_cta_subtitle"
                       name="hero_cta_subtitle"
                       value="<?php echo esc_attr( $hero_cta_subtitle ?: 'Finden Sie Ihre perfekte Veranstaltungsumgebung.' ); ?>"
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                       placeholder="z.B. Finden Sie Ihre perfekte Veranstaltungsumgebung.">
                <p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">
                    Der Beschreibungstext unter der H2 Überschrift.
                </p>
            </div>

            <div style="margin-bottom: 15px; display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label for="hero_button_text" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
                        Button Text:
                    </label>
                    <input type="text"
                           id="hero_button_text"
                           name="hero_button_text"
                           value="<?php echo esc_attr( $hero_button_text ?: 'Inspirier mich' ); ?>"
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                           placeholder="z.B. Inspirier mich">
                    <p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">
                        Der Text auf dem Button.
                    </p>
                </div>
                <div style="flex: 1;">
                    <label for="hero_button_link" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px;">
                        Button Link:
                    </label>
                    <input type="text"
                           id="hero_button_link"
                           name="hero_button_link"
                           value="<?php echo esc_attr( $hero_button_link ?: '#' ); ?>"
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                           placeholder="# oder /home oder https://example.com">
                    <p style="margin: 5px 0 0 0; font-size: 11px; color: #666;">
                        URL, Slug (z.B. /home) oder # für keinen Link.
                    </p>
                </div>
            </div>
        </div>

        <!-- Visibility Settings -->
        <div class="visibility-settings">
            <h3>👁️ Sichtbarkeit der Sektionen</h3>

            <div class="visibility-group">
                <h4>Haupt-Sektionen</h4>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox"
                               id="show_top_hotels_section"
                               name="show_top_hotels_section"
                               value="1"
                               <?php checked( $show_top_hotels, true ); ?>>
                        <label for="show_top_hotels_section">
                            ⭐ Zeige "Top-Veranstaltungsorte" Sektion
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox"
                               id="show_event_types_section"
                               name="show_event_types_section"
                               value="1"
                               <?php checked( $show_event_types, true ); ?>>
                        <label for="show_event_types_section">
                            🎯 Zeige "Veranstaltungsarten" Sektion
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox"
                               id="show_locations_section"
                               name="show_locations_section"
                               value="1"
                               <?php checked( $show_locations, true ); ?>>
                        <label for="show_locations_section">
                            📍 Zeige "Angesagte Locations" Sektion
                        </label>
                    </div>
                </div>
            </div>

            <div class="visibility-group" id="top-hotels-filter-tabs-group" style="<?php echo $show_top_hotels ? '' : 'display: none;'; ?>">
                <h4>Top Hotels Filter-Tabs</h4>
                <p style="font-size: 12px; color: #666; margin: 0 0 12px 0;">Steuere welche Filter-Tabs in der "Top-Veranstaltungsorte" Sektion angezeigt werden</p>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox"
                               id="show_top_hotels_tab"
                               name="show_top_hotels_tab"
                               value="1"
                               <?php checked( $show_top_tab, true ); ?>>
                        <label for="show_top_hotels_tab">
                            ⭐ Zeige "Top Hotels" Tab
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox"
                               id="show_theme_filter_tab"
                               name="show_theme_filter_tab"
                               value="1"
                               <?php checked( $show_theme_tab, true ); ?>>
                        <label for="show_theme_filter_tab">
                            🏷️ Zeige "Nach Thema" Tab
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox"
                               id="show_location_filter_tab"
                               name="show_location_filter_tab"
                               value="1"
                               <?php checked( $show_location_tab, true ); ?>>
                        <label for="show_location_filter_tab">
                            🌍 Zeige "Nach Region" Tab
                        </label>
                    </div>
                </div>
                
                <!-- Top Hotels Tab Hotel Selector -->
                <div id="top-hotels-tab-selector" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; <?php echo $show_top_tab ? '' : 'display: none;'; ?>">
                    <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;">Hotels für "Top Hotels" Tab auswählen:</h4>
                    <?php
                    $top_hotels_tab_hotels = get_post_meta( $post->ID, 'top_hotels_tab_hotels_ordered', true );
                    $selected_top_hotels_tab_hotels = ! empty( $top_hotels_tab_hotels ) ? array_map( 'intval', explode( ',', $top_hotels_tab_hotels ) ) : array();
                    ?>
                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <h5 style="font-size: 12px; margin: 0 0 8px 0;">✓ Ausgewählte Hotels</h5>
                            <ul class="sortable-list" id="selected-top-hotels-tab-hotels" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
                                <?php 
                                if ( ! empty( $selected_top_hotels_tab_hotels ) ) {
                                    $hotels = get_posts( array(
                                        'post_type' => 'hotel',
                                        'post__in' => $selected_top_hotels_tab_hotels,
                                        'posts_per_page' => -1,
                                        'orderby' => 'post__in',
                                    ) );
                                    foreach ( $hotels as $hotel ) : ?>
                                        <li data-id="<?php echo esc_attr( $hotel->ID ); ?>" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="cursor: move;">⋮⋮</span>
                                                <span style="flex: 1; margin: 0 10px;"><?php echo esc_html( $hotel->post_title ); ?></span>
                                                <button type="button" class="remove-btn" onclick="removeHotelFromList(this, 'top-hotels-tab')" style="padding: 2px 6px; font-size: 16px;">×</button>
                                            </div>
                                        </li>
                                    <?php endforeach;
                                }
                                ?>
                            </ul>
                            <input type="hidden" name="top_hotels_tab_hotels_ordered" id="top-hotels-tab-hotels-input" value="<?php echo esc_attr( implode( ',', $selected_top_hotels_tab_hotels ) ); ?>">
                        </div>
                        <div style="flex: 1;">
                            <h5 style="font-size: 12px; margin: 0 0 8px 0;">Verfügbare Hotels</h5>
                            <input type="text" 
                                   id="top-hotels-tab-hotel-search" 
                                   placeholder="Hotels suchen..." 
                                   style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; margin-bottom: 8px;"
                                   onkeyup="filterHotelList(this.value, 'top-hotels-tab')">
                            <ul class="sortable-list available-list" id="available-top-hotels-tab-hotels" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
                                <?php 
                                $available_hotel_ids_for_tab = array_diff( $all_hotel_ids_for_selectors, $selected_top_hotels_tab_hotels );
                                if ( ! empty( $available_hotel_ids_for_tab ) ) {
                                    $available_hotels = get_posts( array(
                                        'post_type' => 'hotel',
                                        'post__in' => array_slice( $available_hotel_ids_for_tab, 0, 100 ),
                                        'posts_per_page' => 100,
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                    ) );
                                    foreach ( $available_hotels as $hotel ) : ?>
                                        <li data-id="<?php echo esc_attr( $hotel->ID ); ?>" data-title="<?php echo esc_attr( strtolower( $hotel->post_title ) ); ?>" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="flex: 1;"><?php echo esc_html( $hotel->post_title ); ?></span>
                                                <button type="button" class="add-btn" onclick="addHotelToList(this, 'top-hotels-tab')" style="padding: 2px 8px; font-size: 14px;">+</button>
                                            </div>
                                        </li>
                                    <?php endforeach;
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Nach Thema Tab Collection Selector -->
                <div id="theme-tab-selector" style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; <?php echo $show_theme_tab ? '' : 'display: none;'; ?>">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #856404; flex-shrink: 0;">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                        </svg>
                        <div>
                            <h4 style="margin: 0 0 5px 0; font-size: 14px; color: #856404;">🏷️ "Nach Thema" Tab</h4>
                            <p style="margin: 0; font-size: 12px; color: #856404;">Die Auswahlfunktion für diesen Tab ist noch nicht verfügbar. Diese Funktion wird in einer zukünftigen Version hinzugefügt.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Nach Region Tab Selector -->
                <div id="location-tab-selector" style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; <?php echo $show_location_tab ? '' : 'display: none;'; ?>">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #856404; flex-shrink: 0;">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                        </svg>
                        <div>
                            <h4 style="margin: 0 0 5px 0; font-size: 14px; color: #856404;">🌍 "Nach Region" Tab</h4>
                            <p style="margin: 0; font-size: 12px; color: #856404;">Die Auswahlfunktion für diesen Tab ist noch nicht verfügbar. Diese Funktion wird in einer zukünftigen Version hinzugefügt.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Types Section -->
        <div id="event-types-selection-section" style="<?php echo $show_event_types ? '' : 'display: none;'; ?>">
        <h3>Veranstaltungsarten Sektion (Event Types)</h3>
        <p class="helper-text">Ziehen Sie Collections, um die Reihenfolge zu ändern. Max. 6 Collections. <span class="collection-count" id="event-types-count">(<?php echo count( $selected_event_types ); ?>/6)</span></p>

        <div class="collections-manager">
            <div class="selected-collections">
                <h4>✓ Ausgewählte Collections (Reihenfolge änderbar)</h4>
                <ul class="sortable-list" id="selected-event-types">
                    <?php foreach ( $selected_event_types as $collection_id ) : ?>
                        <?php if ( isset( $collections_lookup[ $collection_id ] ) ) : ?>
                            <li data-id="<?php echo esc_attr( $collection_id ); ?>">
                                <div class="collection-item-handle">
                                    <span class="drag-handle">⋮⋮</span>
                                    <span><?php echo esc_html( $collections_lookup[ $collection_id ] ); ?></span>
                                </div>
                                <button type="button" class="remove-btn" onclick="removeCollection(this, 'event')">×</button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <input type="hidden" name="event_type_collections_ordered" id="event-type-collections-input" value="<?php echo esc_attr( implode( ',', $selected_event_types ) ); ?>">
            </div>

            <div class="available-collections">
                <h4>Verfügbare Collections</h4>
                <ul class="sortable-list available-list" id="available-event-types">
                    <?php foreach ( $all_collections as $collection ) : ?>
                        <?php if ( ! in_array( $collection->ID, $selected_event_types ) ) : ?>
                            <li data-id="<?php echo esc_attr( $collection->ID ); ?>">
                                <div class="collection-item-handle">
                                    <span><?php echo esc_html( $collection->post_title ); ?></span>
                                </div>
                                <button type="button" class="add-btn" onclick="addCollection(this, 'event')">+</button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        </div> <!-- End event-types-selection-section -->

        <!-- Top Hotels Section -->
        <div id="top-hotels-selection-section" style="<?php echo ( $show_top_hotels && ! empty( $selected_top_hotels ) ) ? '' : 'display: none;'; ?>">
        <h3>⭐ Top Hotels Sektion (Top-Veranstaltungsorte)</h3>
        <p class="helper-text">Wählen Sie Hotels aus, die auf der Startseite angezeigt werden sollen. Ziehen Sie Hotels, um die Reihenfolge zu ändern. <span class="collection-count" id="top-hotels-count">(<?php 
            $top_hotels = get_post_meta( $post->ID, 'top_hotels_ordered', true );
            $selected_top_hotels = ! empty( $top_hotels ) ? array_map( 'intval', explode( ',', $top_hotels ) ) : array();
            echo count( $selected_top_hotels ); 
        ?>)</span></p>

        <?php
        // Optimized query: Only get IDs first to reduce memory usage
        $all_hotel_ids = get_posts( array(
            'post_type'      => 'hotel',
            'posts_per_page' => 500, // Limit to 500 to prevent memory issues
            'fields'         => 'ids', // Only get IDs to save memory
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ) );

        // Get full hotel objects only for selected hotels and a limited set for available
        $hotels_lookup = array();
        
        // First, get selected hotels (always include these)
        if ( ! empty( $selected_top_hotels ) ) {
            $selected_hotels = get_posts( array(
                'post_type'      => 'hotel',
                'post__in'       => $selected_top_hotels,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ) );
            foreach ( $selected_hotels as $hotel ) {
                $hotels_lookup[ $hotel->ID ] = $hotel->post_title;
            }
        }
        
        // Get available hotels (limited set for performance)
        $available_hotel_ids = array_diff( $all_hotel_ids, $selected_top_hotels );
        if ( ! empty( $available_hotel_ids ) ) {
            $available_hotels = get_posts( array(
                'post_type'      => 'hotel',
                'post__in'       => array_slice( $available_hotel_ids, 0, 200 ), // Limit to 200 available hotels
                'posts_per_page' => 200,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'post_status'    => 'publish',
            ) );
            foreach ( $available_hotels as $hotel ) {
                if ( ! isset( $hotels_lookup[ $hotel->ID ] ) ) {
                    $hotels_lookup[ $hotel->ID ] = $hotel->post_title;
                }
            }
        }
        
        // Create array of all hotels for display (selected + available)
        $all_hotels = array();
        foreach ( $hotels_lookup as $hotel_id => $hotel_title ) {
            $all_hotels[] = (object) array(
                'ID' => $hotel_id,
                'post_title' => $hotel_title,
            );
        }
        ?>
        
        <div style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px; font-size: 12px; color: #666;">
            <strong>💡 Tipp:</strong> Es werden bis zu 200 verfügbare Hotels angezeigt. Verwenden Sie die Suche, um spezifische Hotels zu finden, oder fügen Sie Hotels direkt über die Hotel-ID hinzu.
        </div>

        <div class="collections-manager">
            <div class="selected-collections">
                <h4>✓ Ausgewählte Hotels (Reihenfolge änderbar)</h4>
                <ul class="sortable-list" id="selected-top-hotels">
                    <?php foreach ( $selected_top_hotels as $hotel_id ) : ?>
                        <?php if ( isset( $hotels_lookup[ $hotel_id ] ) ) : ?>
                            <li data-id="<?php echo esc_attr( $hotel_id ); ?>">
                                <div class="collection-item-handle">
                                    <span class="drag-handle">⋮⋮</span>
                                    <span><?php echo esc_html( $hotels_lookup[ $hotel_id ] ); ?></span>
                                </div>
                                <button type="button" class="remove-btn" onclick="removeHotel(this)">×</button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <input type="hidden" name="top_hotels_ordered" id="top-hotels-input" value="<?php echo esc_attr( implode( ',', $selected_top_hotels ) ); ?>">
            </div>

            <div class="available-collections">
                <h4>Verfügbare Hotels</h4>
                <div style="margin-bottom: 10px;">
                    <input type="text" 
                           id="hotel-search-input" 
                           placeholder="Hotels suchen..." 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;"
                           onkeyup="filterHotels(this.value)">
                </div>
                <ul class="sortable-list available-list" id="available-top-hotels" style="max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                    <?php foreach ( $all_hotels as $hotel ) : ?>
                        <?php if ( ! in_array( $hotel->ID, $selected_top_hotels ) ) : ?>
                            <li data-id="<?php echo esc_attr( $hotel->ID ); ?>" data-title="<?php echo esc_attr( strtolower( $hotel->post_title ) ); ?>">
                                <div class="collection-item-handle">
                                    <span><?php echo esc_html( $hotel->post_title ); ?></span>
                                </div>
                                <button type="button" class="add-btn" onclick="addHotel(this)">+</button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <p style="margin-top: 10px; font-size: 11px; color: #666; font-style: italic;">
                    <?php 
                    $total_hotels = wp_count_posts( 'hotel' )->publish;
                    if ( $total_hotels > 200 ) {
                        echo sprintf( 'Zeige 200 von %d verfügbaren Hotels. Verwenden Sie die Suche, um spezifische Hotels zu finden.', $total_hotels );
                    }
                    ?>
                </p>
            </div>
        </div>
        </div> <!-- End top-hotels-selection-section -->

        <!-- Popular Locations Section -->
        <div id="locations-selection-section" style="<?php echo $show_locations ? '' : 'display: none;'; ?>">
        <h3>Beliebte Locations Sektion (Popular Locations)</h3>
        <p class="helper-text">Ziehen Sie Collections, um die Reihenfolge zu ändern. Max. 6 Collections. <span class="collection-count" id="locations-count">(<?php echo count( $selected_locations ); ?>/6)</span></p>

        <div class="collections-manager">
            <div class="selected-collections">
                <h4>✓ Ausgewählte Collections (Reihenfolge änderbar)</h4>
                <ul class="sortable-list" id="selected-locations">
                    <?php foreach ( $selected_locations as $collection_id ) : ?>
                        <?php if ( isset( $collections_lookup[ $collection_id ] ) ) : ?>
                            <li data-id="<?php echo esc_attr( $collection_id ); ?>">
                                <div class="collection-item-handle">
                                    <span class="drag-handle">⋮⋮</span>
                                    <span><?php echo esc_html( $collections_lookup[ $collection_id ] ); ?></span>
                                </div>
                                <button type="button" class="remove-btn" onclick="removeCollection(this, 'location')">×</button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <input type="hidden" name="popular_location_collections_ordered" id="location-collections-input" value="<?php echo esc_attr( implode( ',', $selected_locations ) ); ?>">
            </div>

            <div class="available-collections">
                <h4>Verfügbare Collections</h4>
                <ul class="sortable-list available-list" id="available-locations">
                    <?php foreach ( $all_collections as $collection ) : ?>
                        <?php if ( ! in_array( $collection->ID, $selected_locations ) ) : ?>
                            <li data-id="<?php echo esc_attr( $collection->ID ); ?>">
                                <div class="collection-item-handle">
                                    <span><?php echo esc_html( $collection->post_title ); ?></span>
                                </div>
                                <button type="button" class="add-btn" onclick="addCollection(this, 'location')">+</button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        </div> <!-- End locations-selection-section -->

        <script>
        jQuery(document).ready(function($) {
            // Make lists sortable
            $('#selected-event-types, #selected-locations, #selected-top-hotels').sortable({
                handle: '.drag-handle',
                opacity: 0.8,
                cursor: 'move',
                update: function(event, ui) {
                    if ($(this).attr('id') === 'selected-top-hotels') {
                        updateHotelsInput($(this));
                        toggleTopHotelsSection();
                    } else {
                        updateHiddenInput($(this));
                    }
                }
            });

            // Toggle sections based on checkboxes
            $('#show_top_hotels_section').on('change', function() {
                toggleTopHotelsSection();
                // Toggle filter tabs group visibility
                if ($(this).is(':checked')) {
                    $('#top-hotels-filter-tabs-group').slideDown(200);
                } else {
                    $('#top-hotels-filter-tabs-group').slideUp(200);
                }
            });

            $('#show_event_types_section').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#event-types-selection-section').slideDown(200);
                } else {
                    $('#event-types-selection-section').slideUp(200);
                }
            });

            $('#show_locations_section').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#locations-selection-section').slideDown(200);
                } else {
                    $('#locations-selection-section').slideUp(200);
                }
            });
            
            // Toggle filter tab selectors
            $('#show_top_hotels_tab').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#top-hotels-tab-selector').slideDown(200);
                } else {
                    $('#top-hotels-tab-selector').slideUp(200);
                }
            });
            
            $('#show_theme_filter_tab').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#theme-tab-selector').slideDown(200);
                } else {
                    $('#theme-tab-selector').slideUp(200);
                }
            });
            
            $('#show_location_filter_tab').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#location-tab-selector').slideDown(200);
                } else {
                    $('#location-tab-selector').slideUp(200);
                }
            });
            
            // Make new lists sortable
            $('#selected-top-hotels-tab-hotels, #selected-theme-tab-collections').sortable({
                handle: 'span',
                opacity: 0.8,
                cursor: 'move',
                update: function(event, ui) {
                    updateSelectorInput($(this));
                }
            });
        });

        function toggleTopHotelsSection() {
            var checkbox = jQuery('#show_top_hotels_section');
            var section = jQuery('#top-hotels-selection-section');
            var hasHotels = jQuery('#selected-top-hotels li').length > 0;
            
            if (checkbox.is(':checked') && hasHotels) {
                section.slideDown(200);
            } else {
                section.slideUp(200);
            }
        }

        function updateHiddenInput(list) {
            var ids = [];
            list.find('li').each(function() {
                ids.push(jQuery(this).data('id'));
            });

            var inputId = list.attr('id') === 'selected-event-types' ? 'event-type-collections-input' : 'location-collections-input';
            var countId = list.attr('id') === 'selected-event-types' ? 'event-types-count' : 'locations-count';

            jQuery('#' + inputId).val(ids.join(','));
            jQuery('#' + countId).text('(' + ids.length + '/6)');
        }

        function addCollection(button, type) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            var title = li.find('.collection-item-handle span').text();

            var selectedList = type === 'event' ? jQuery('#selected-event-types') : jQuery('#selected-locations');

            // Check if max 6
            if (selectedList.find('li').length >= 6) {
                alert('Maximal 6 Collections erlaubt!');
                return;
            }

            // Add to selected
            var newLi = '<li data-id="' + id + '">' +
                '<div class="collection-item-handle">' +
                '<span class="drag-handle">⋮⋮</span>' +
                '<span>' + title + '</span>' +
                '</div>' +
                '<button type="button" class="remove-btn" onclick="removeCollection(this, \'' + type + '\')">×</button>' +
                '</li>';

            selectedList.append(newLi);
            li.remove();
            updateHiddenInput(selectedList);
        }

        function removeCollection(button, type) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            var title = li.find('.collection-item-handle span:last').text();

            var availableList = type === 'event' ? jQuery('#available-event-types') : jQuery('#available-locations');
            var selectedList = type === 'event' ? jQuery('#selected-event-types') : jQuery('#selected-locations');

            // Add back to available
            var newLi = '<li data-id="' + id + '">' +
                '<div class="collection-item-handle">' +
                '<span>' + title + '</span>' +
                '</div>' +
                '<button type="button" class="add-btn" onclick="addCollection(this, \'' + type + '\')">+</button>' +
                '</li>';

            availableList.append(newLi);
            li.remove();
            updateHiddenInput(selectedList);
        }

        function updateHotelsInput(list) {
            var ids = [];
            list.find('li').each(function() {
                ids.push(jQuery(this).data('id'));
            });
            jQuery('#top-hotels-input').val(ids.join(','));
            jQuery('#top-hotels-count').text('(' + ids.length + ')');
        }

        function addHotel(button) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            var title = li.find('.collection-item-handle span').text();

            var selectedList = jQuery('#selected-top-hotels');

            // Add to selected
            var newLi = '<li data-id="' + id + '">' +
                '<div class="collection-item-handle">' +
                '<span class="drag-handle">⋮⋮</span>' +
                '<span>' + title + '</span>' +
                '</div>' +
                '<button type="button" class="remove-btn" onclick="removeHotel(this)">×</button>' +
                '</li>';

            selectedList.append(newLi);
            li.remove();
            updateHotelsInput(selectedList);
            toggleTopHotelsSection();
        }

        function removeHotel(button) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            var title = li.find('.collection-item-handle span:last').text();

            var availableList = jQuery('#available-top-hotels');
            var selectedList = jQuery('#selected-top-hotels');

            // Add back to available
            var newLi = '<li data-id="' + id + '" data-title="' + title.toLowerCase() + '">' +
                '<div class="collection-item-handle">' +
                '<span>' + title + '</span>' +
                '</div>' +
                '<button type="button" class="add-btn" onclick="addHotel(this)">+</button>' +
                '</li>';

            availableList.append(newLi);
            li.remove();
            updateHotelsInput(selectedList);
            toggleTopHotelsSection();
        }

        var searchTimeout;
        function filterHotels(searchTerm) {
            var searchLower = searchTerm.toLowerCase();
            var availableList = jQuery('#available-top-hotels');
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // If search is less than 2 characters, just filter the visible list
            if (searchTerm.length < 2) {
                availableList.find('li').each(function() {
                    var title = jQuery(this).data('title') || jQuery(this).find('.collection-item-handle span').text().toLowerCase();
                    if (title.indexOf(searchLower) !== -1) {
                        jQuery(this).show();
                    } else {
                        jQuery(this).hide();
                    }
                });
                return;
            }
            
            // For longer searches, use AJAX to search all hotels
            searchTimeout = setTimeout(function() {
                var selectedIds = [];
                jQuery('#selected-top-hotels li').each(function() {
                    selectedIds.push(jQuery(this).data('id'));
                });
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_hotels_for_selector',
                        query: searchTerm,
                        exclude_ids: selectedIds.join(',')
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            availableList.empty();
                            response.data.forEach(function(hotel) {
                                var location = hotel.location ? '<br><small style="color: #666;">' + hotel.location + '</small>' : '';
                                var li = '<li data-id="' + hotel.id + '" data-title="' + hotel.title.toLowerCase() + '">' +
                                    '<div class="collection-item-handle">' +
                                    '<span>' + hotel.title + '</span>' + location +
                                    '</div>' +
                                    '<button type="button" class="add-btn" onclick="addHotel(this)">+</button>' +
                                    '</li>';
                                availableList.append(li);
                            });
                        } else {
                            availableList.html('<li style="padding: 10px; color: #999; text-align: center;">Keine Hotels gefunden</li>');
                        }
                    },
                    error: function() {
                        // Fallback to local search on error
                        availableList.find('li').each(function() {
                            var title = jQuery(this).data('title') || jQuery(this).find('.collection-item-handle span').text().toLowerCase();
                            if (title.indexOf(searchLower) !== -1) {
                                jQuery(this).show();
                            } else {
                                jQuery(this).hide();
                            }
                        });
                    }
                });
            }, 300);
        }
        
        // New functions for new selectors
        function updateSelectorInput(list) {
            var ids = [];
            list.find('li').each(function() {
                ids.push(jQuery(this).data('id'));
            });
            
            var listId = list.attr('id');
            var inputId = '';
            if (listId === 'selected-top-hotels-tab-hotels') {
                inputId = 'top-hotels-tab-hotels-input';
            } else if (listId === 'selected-theme-tab-collections') {
                inputId = 'theme-tab-collections-input';
            }
            
            if (inputId) {
                jQuery('#' + inputId).val(ids.join(','));
            }
        }
        
        function addHotelToList(button, type) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            // Get title - it's the first text node in the span, before any <br> or <small>
            var titleSpan = li.find('span').first();
            var title = titleSpan.clone().children().remove().end().text().trim();
            // Get location if it exists
            var locationSmall = titleSpan.find('small').text();
            var location = locationSmall ? locationSmall.trim() : '';
            
            var selectedList = jQuery('#selected-top-hotels-tab-hotels');
            var availableList = jQuery('#available-top-hotels-tab-hotels');
            
            // Add to selected - store location in data attribute for later retrieval
            var newLi = jQuery('<li>', {
                'data-id': id,
                'data-location': location,
                'style': 'padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 4px;'
            }).html(
                '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="cursor: move;">⋮⋮</span>' +
                '<span style="flex: 1; margin: 0 10px;">' + title + '</span>' +
                '<button type="button" class="remove-btn" onclick="removeHotelFromList(this, \'' + type + '\')" style="padding: 2px 6px; font-size: 16px;">×</button>' +
                '</div>'
            );
            
            selectedList.append(newLi);
            li.remove();
            updateSelectorInput(selectedList);
        }
        
        function removeHotelFromList(button, type) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            // Get title - in selected list, it's the second span (after drag handle)
            var titleSpan = li.find('span').eq(1);
            var title = titleSpan.text().trim();
            // Get location from data attribute if stored
            var location = li.data('location') || '';
            var locationHtml = location ? '<br><small style="color: #666;">' + location + '</small>' : '';
            
            var selectedList = jQuery('#selected-top-hotels-tab-hotels');
            var availableList = jQuery('#available-top-hotels-tab-hotels');
            
            // Add back to available
            var newLi = '<li data-id="' + id + '" data-title="' + title.toLowerCase() + '" data-location="' + (location || '') + '" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">' +
                '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="flex: 1;">' + title + locationHtml + '</span>' +
                '<button type="button" class="add-btn" onclick="addHotelToList(this, \'' + type + '\')" style="padding: 2px 8px; font-size: 14px;">+</button>' +
                '</div>' +
                '</li>';
            
            availableList.append(newLi);
            li.remove();
            updateSelectorInput(selectedList);
        }
        
        function addCollectionToList(button, type) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            var title = li.find('span').first().text();
            
            var selectedList = jQuery('#selected-theme-tab-collections');
            var availableList = jQuery('#available-theme-tab-collections');
            
            // Add to selected
            var newLi = '<li data-id="' + id + '" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 4px;">' +
                '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="cursor: move;">⋮⋮</span>' +
                '<span style="flex: 1; margin: 0 10px;">' + title + '</span>' +
                '<button type="button" class="remove-btn" onclick="removeCollectionFromList(this, \'' + type + '\')" style="padding: 2px 6px; font-size: 16px;">×</button>' +
                '</div>' +
                '</li>';
            
            selectedList.append(newLi);
            li.remove();
            updateSelectorInput(selectedList);
        }
        
        function removeCollectionFromList(button, type) {
            var li = jQuery(button).closest('li');
            var id = li.data('id');
            var title = li.find('span').eq(1).text();
            
            var selectedList = jQuery('#selected-theme-tab-collections');
            var availableList = jQuery('#available-theme-tab-collections');
            
            // Add back to available
            var newLi = '<li data-id="' + id + '" data-title="' + title.toLowerCase() + '" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">' +
                '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="flex: 1;">' + title + '</span>' +
                '<button type="button" class="add-btn" onclick="addCollectionToList(this, \'' + type + '\')" style="padding: 2px 8px; font-size: 14px;">+</button>' +
                '</div>' +
                '</li>';
            
            availableList.append(newLi);
            li.remove();
            updateSelectorInput(selectedList);
        }
        
        var searchTimeoutTab;
        function filterHotelList(searchTerm, type) {
            var searchLower = searchTerm.toLowerCase();
            var availableList = jQuery('#available-top-hotels-tab-hotels');
            
            // Clear previous timeout
            clearTimeout(searchTimeoutTab);
            
            // If search is less than 2 characters, just filter the visible list
            if (searchTerm.length < 2) {
                availableList.find('li').each(function() {
                    var title = jQuery(this).data('title') || jQuery(this).find('span').first().text().toLowerCase();
                    if (title.indexOf(searchLower) !== -1) {
                        jQuery(this).show();
                    } else {
                        jQuery(this).hide();
                    }
                });
                return;
            }
            
            // For longer searches, use AJAX to search all hotels
            searchTimeoutTab = setTimeout(function() {
                var selectedIds = [];
                jQuery('#selected-top-hotels-tab-hotels li').each(function() {
                    selectedIds.push(jQuery(this).data('id'));
                });
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_hotels_for_selector',
                        query: searchTerm,
                        exclude_ids: selectedIds.join(',')
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            availableList.empty();
                            response.data.forEach(function(hotel) {
                                var location = hotel.location ? '<br><small style="color: #666;">' + hotel.location + '</small>' : '';
                                var li = '<li data-id="' + hotel.id + '" data-title="' + hotel.title.toLowerCase() + '" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">' +
                                    '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                                    '<span style="flex: 1;">' + hotel.title + location + '</span>' +
                                    '<button type="button" class="add-btn" onclick="addHotelToList(this, \'' + type + '\')" style="padding: 2px 8px; font-size: 14px;">+</button>' +
                                    '</div>' +
                                    '</li>';
                                availableList.append(li);
                            });
                        } else {
                            availableList.html('<li style="padding: 10px; color: #999; text-align: center;">Keine Hotels gefunden</li>');
                        }
                    },
                    error: function() {
                        // Fallback to local search on error
                        availableList.find('li').each(function() {
                            var title = jQuery(this).data('title') || jQuery(this).find('span').first().text().toLowerCase();
                            if (title.indexOf(searchLower) !== -1) {
                                jQuery(this).show();
                            } else {
                                jQuery(this).hide();
                            }
                        });
                    }
                });
            }, 300);
        }
        
        function filterCollectionList(searchTerm, type) {
            var searchLower = searchTerm.toLowerCase();
            jQuery('#available-theme-tab-collections li').each(function() {
                var title = jQuery(this).data('title') || jQuery(this).find('span').first().text().toLowerCase();
                if (title.indexOf(searchLower) !== -1) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }
            });
        }
        </script>
    </div>
    <?php
}

/**
 * Save meta box data
 */
function seminargo_save_homepage_collections_meta( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['seminargo_homepage_collections_nonce'] ) ||
         ! wp_verify_nonce( $_POST['seminargo_homepage_collections_nonce'], 'seminargo_homepage_collections_nonce' ) ) {
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

    // Save event type collections (ordered)
    if ( isset( $_POST['event_type_collections_ordered'] ) && ! empty( $_POST['event_type_collections_ordered'] ) ) {
        $event_types = sanitize_text_field( $_POST['event_type_collections_ordered'] );
        update_post_meta( $post_id, 'event_type_collections', $event_types );
    } else {
        delete_post_meta( $post_id, 'event_type_collections' );
    }

    // Save popular location collections (ordered)
    if ( isset( $_POST['popular_location_collections_ordered'] ) && ! empty( $_POST['popular_location_collections_ordered'] ) ) {
        $locations = sanitize_text_field( $_POST['popular_location_collections_ordered'] );
        update_post_meta( $post_id, 'popular_location_collections', $locations );
    } else {
        delete_post_meta( $post_id, 'popular_location_collections' );
    }

    // Save top hotels (ordered)
    if ( isset( $_POST['top_hotels_ordered'] ) && ! empty( $_POST['top_hotels_ordered'] ) ) {
        $top_hotels = sanitize_text_field( $_POST['top_hotels_ordered'] );
        update_post_meta( $post_id, 'top_hotels_ordered', $top_hotels );
    } else {
        delete_post_meta( $post_id, 'top_hotels_ordered' );
    }
    
    // Save top hotels tab hotels (ordered)
    if ( isset( $_POST['top_hotels_tab_hotels_ordered'] ) && ! empty( $_POST['top_hotels_tab_hotels_ordered'] ) ) {
        $top_hotels_tab_hotels = sanitize_text_field( $_POST['top_hotels_tab_hotels_ordered'] );
        update_post_meta( $post_id, 'top_hotels_tab_hotels_ordered', $top_hotels_tab_hotels );
    } else {
        delete_post_meta( $post_id, 'top_hotels_tab_hotels_ordered' );
    }
    
    // Save theme tab collections (ordered)
    if ( isset( $_POST['theme_tab_collections_ordered'] ) && ! empty( $_POST['theme_tab_collections_ordered'] ) ) {
        $theme_tab_collections = sanitize_text_field( $_POST['theme_tab_collections_ordered'] );
        update_post_meta( $post_id, 'theme_tab_collections_ordered', $theme_tab_collections );
    } else {
        delete_post_meta( $post_id, 'theme_tab_collections_ordered' );
    }

    // Save section visibility settings (save as '1' or '0')
    update_post_meta( $post_id, 'show_top_hotels_section', isset( $_POST['show_top_hotels_section'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_event_types_section', isset( $_POST['show_event_types_section'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_locations_section', isset( $_POST['show_locations_section'] ) ? '1' : '0' );

    // Save filter tab visibility settings (save as '1' or '0')
    update_post_meta( $post_id, 'show_top_hotels_tab', isset( $_POST['show_top_hotels_tab'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_theme_filter_tab', isset( $_POST['show_theme_filter_tab'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_location_filter_tab', isset( $_POST['show_location_filter_tab'] ) ? '1' : '0' );

    // Save hero section settings
    if ( isset( $_POST['hero_h1'] ) ) {
        update_post_meta( $post_id, 'hero_h1', sanitize_text_field( $_POST['hero_h1'] ) );
    }
    if ( isset( $_POST['hero_description'] ) ) {
        update_post_meta( $post_id, 'hero_description', sanitize_text_field( $_POST['hero_description'] ) );
    }

    // Save hero CTA text settings
    if ( isset( $_POST['hero_cta_title'] ) ) {
        update_post_meta( $post_id, 'hero_cta_title', sanitize_text_field( $_POST['hero_cta_title'] ) );
    }
    if ( isset( $_POST['hero_cta_subtitle'] ) ) {
        update_post_meta( $post_id, 'hero_cta_subtitle', sanitize_text_field( $_POST['hero_cta_subtitle'] ) );
    }
    if ( isset( $_POST['hero_background_image'] ) ) {
        update_post_meta( $post_id, 'hero_background_image', esc_url_raw( $_POST['hero_background_image'] ) );
    }
    if ( isset( $_POST['hero_button_text'] ) ) {
        update_post_meta( $post_id, 'hero_button_text', sanitize_text_field( $_POST['hero_button_text'] ) );
    }
    if ( isset( $_POST['hero_button_link'] ) ) {
        $button_link = sanitize_text_field( $_POST['hero_button_link'] );
        // Allow full URLs, internal paths, hash anchors, or empty
        // Use esc_url_raw for full URLs, but preserve internal paths and hash anchors
        if ( ! empty( $button_link ) && ( strpos( $button_link, 'http://' ) === 0 || strpos( $button_link, 'https://' ) === 0 ) ) {
            update_post_meta( $post_id, 'hero_button_link', esc_url_raw( $button_link ) );
        } else {
            // For internal paths, hash anchors, or empty - just sanitize as text
            update_post_meta( $post_id, 'hero_button_link', $button_link );
        }
    }
}
add_action( 'save_post', 'seminargo_save_homepage_collections_meta' );

/**
 * Enqueue admin scripts for homepage meta box
 */
function seminargo_enqueue_homepage_admin_scripts( $hook ) {
    global $post_type;

    // Only enqueue on page edit screens
    if ( ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post_type === 'page' ) {
        wp_enqueue_media();

        wp_add_inline_script( 'jquery', '
            jQuery(document).ready(function($) {
                // Media uploader for hero background image
                var heroBackgroundMediaUploader;
                $("#upload_hero_background_image").on("click", function(e) {
                    e.preventDefault();
                    if (heroBackgroundMediaUploader) {
                        heroBackgroundMediaUploader.open();
                        return;
                    }
                    heroBackgroundMediaUploader = wp.media({
                        title: "Hintergrundbild auswählen",
                        button: { text: "Bild verwenden" },
                        multiple: false
                    });
                    heroBackgroundMediaUploader.on("select", function() {
                        var attachment = heroBackgroundMediaUploader.state().get("selection").first().toJSON();
                        $("#hero_background_image").val(attachment.url);
                        $(".hero-background-image-preview").html("<img src=\"" + attachment.url + "\" style=\"max-width: 400px; height: auto; border-radius: 8px; border: 1px solid #ddd;\">");
                        $("#remove_hero_background_image").show();
                    });
                    heroBackgroundMediaUploader.open();
                });

                $("#remove_hero_background_image").on("click", function() {
                    var defaultImage = "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&h=600&fit=crop";
                    $("#hero_background_image").val(defaultImage);
                    $(".hero-background-image-preview").html("<img src=\"" + defaultImage + "\" style=\"max-width: 400px; height: auto; border-radius: 8px; border: 1px solid #ddd;\">");
                    $(this).hide();
                });
            });
        ' );
    }
}
add_action( 'admin_enqueue_scripts', 'seminargo_enqueue_homepage_admin_scripts' );

/**
 * Redirect old hotel slugs to new URLs (301 redirect)
 * 
 * Checks if the current URL matches an old slug (migSlug) and redirects to the new URL.
 * This handles migration from old URL structure to new WordPress permalink structure.
 */
function seminargo_redirect_old_hotel_slugs() {
    // Only run on frontend, not admin or AJAX
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return;
    }

    // Skip if we're already on a valid hotel post (to avoid redirect loops)
    if ( is_singular( 'hotel' ) ) {
        return;
    }

    // Get the current request URI
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    
    if ( empty( $request_uri ) ) {
        return;
    }
    
    // Remove query string and leading/trailing slashes
    $request_uri = strtok( $request_uri, '?' );
    $request_uri = trim( $request_uri, '/' );
    
    // Extract the slug from the URL (last segment)
    $uri_parts = explode( '/', $request_uri );
    $current_slug = end( $uri_parts );

    // Skip if empty or if it's not a potential hotel slug
    if ( empty( $current_slug ) ) {
        return;
    }

    // CRITICAL: Handle BOTH encoded and decoded versions of old URLs
    // Old URL: /hotel/Jugendg%C3%A4stehaus_Bad_Ischl (browser shows with %)
    // Stored: Jugendgästehaus_Bad_Ischl (actual ä character from API)
    //
    // Server may give us REQUEST_URI as:
    //   - Decoded: Jugendgästehaus_Bad_Ischl (actual characters)
    //   - Encoded: Jugendg%C3%A4stehaus_Bad_Ischl (percent-encoded)
    //
    // Solution: Try BOTH decoded and encoded versions

    $slug_decoded = rawurldecode( $current_slug );     // Convert %C3%A4 → ä
    $slug_encoded = rawurlencode( $slug_decoded );     // Convert back: ä → %C3%A4

    // Try both variations (one will match)
    $hotel_query = new WP_Query( [
        'post_type'      => 'hotel',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'mig_slug',
                'value'   => $current_slug,    // As-is from URL
                'compare' => '=',
            ],
            [
                'key'     => 'mig_slug',
                'value'   => $slug_decoded,    // Decoded version (with actual ä)
                'compare' => '=',
            ],
        ],
        'fields'         => 'ids',
    ] );
    
    // If we found a hotel with this old slug, redirect to the new URL
    if ( $hotel_query->have_posts() ) {
        $hotel_id = $hotel_query->posts[0];
        $new_url = get_permalink( $hotel_id );
        
        if ( $new_url ) {
            // Preserve query string if present
            $query_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
            if ( ! empty( $query_string ) ) {
                $new_url = add_query_arg( $query_string, '', $new_url );
            }
            
            // 301 Permanent Redirect
            wp_redirect( $new_url, 301 );
            exit;
        }
    }
}
add_action( 'template_redirect', 'seminargo_redirect_old_hotel_slugs', 1 );