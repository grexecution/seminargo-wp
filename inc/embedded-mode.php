<?php
/**
 * Embedded Mode Support
 *
 * Handles iframe embedding functionality when ?embedded=1 parameter is present.
 * Hides header, footer, and other UI elements for clean iframe integration.
 *
 * @package Seminargo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add body class when embedded=1 parameter is present
 *
 * @param array $classes Existing body classes
 * @return array Modified body classes
 */
function seminargo_embedded_mode_body_class( $classes ) {
    if ( isset( $_GET['embedded'] ) && $_GET['embedded'] == '1' ) {
        $classes[] = 'embedded-mode';
    }
    return $classes;
}
add_filter( 'body_class', 'seminargo_embedded_mode_body_class' );

/**
 * Add inline CSS for embedded mode
 * Hides header, footer, and other UI elements when embedded=1 parameter is present
 */
function seminargo_embedded_mode_styles() {
    if ( isset( $_GET['embedded'] ) && $_GET['embedded'] == '1' ) {
        ?>
        <style>
            /* ========================================
               EMBEDDED MODE STYLES
               ========================================
               Hides navigation, footer, and UI elements
               for clean iframe embedding
            ======================================== */

            /* Hide header/navbar */
            .embedded-mode .site-header,
            .embedded-mode #masthead,
            .embedded-mode .slide-menu {
                display: none !important;
            }

            /* Hide footer */
            .embedded-mode .site-footer,
            .embedded-mode #colophon {
                display: none !important;
            }

            /* Hide back to top button */
            .embedded-mode .back-to-top,
            .embedded-mode #back-to-top {
                display: none !important;
            }

            /* Hide chat widget (Smartsupp) */
            .embedded-mode #chat-application,
            .embedded-mode #smartsupp-widget-container {
                display: none !important;
            }

            /* Adjust spacing - remove top padding since header is hidden */
            .embedded-mode .site-content {
                padding-top: 0;
            }

            /* Adjust page container */
            .embedded-mode .site {
                padding-top: 0;
            }

            /* Optional: Remove breadcrumbs in embedded mode */
            .embedded-mode .breadcrumbs {
                display: none;
            }
        </style>
        <?php
    }
}
add_action( 'wp_head', 'seminargo_embedded_mode_styles' );

