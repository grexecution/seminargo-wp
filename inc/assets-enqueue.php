<?php
/**
 * Assets Enqueue
 *
 * Handles all CSS and JavaScript file loading
 *
 * @package Seminargo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue scripts and styles
 */
function seminargo_scripts() {
    // Theme stylesheet
    wp_enqueue_style(
        'seminargo-style',
        get_stylesheet_uri(),
        array(),
        SEMINARGO_VERSION
    );

    // Main stylesheet
    wp_enqueue_style(
        'seminargo-main',
        SEMINARGO_ASSETS_URL . 'css/main.css',
        array(),
        SEMINARGO_VERSION
    );

    // Main JavaScript file
    wp_enqueue_script(
        'seminargo-main',
        SEMINARGO_ASSETS_URL . 'js/main.js',
        array('jquery'),
        SEMINARGO_VERSION,
        true
    );

    // Navigation script
    wp_enqueue_script(
        'seminargo-navigation',
        SEMINARGO_ASSETS_URL . 'js/navigation.js',
        array(),
        SEMINARGO_VERSION,
        true
    );

    // Seminarhotels archive script (only on seminarhotels page)
    if ( is_page_template( 'page-seminarhotels.php' ) ) {
        wp_enqueue_script(
            'seminargo-seminarhotels',
            SEMINARGO_ASSETS_URL . 'js/seminarhotels.js',
            array(),
            SEMINARGO_VERSION,
            true
        );
    }

    // Hotel single script (only on single hotel pages)
    if ( is_singular( 'hotel' ) ) {
        // Leaflet.js for map
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        wp_enqueue_script(
            'seminargo-hotel-single',
            SEMINARGO_ASSETS_URL . 'js/hotel-single.js',
            array( 'leaflet' ),
            SEMINARGO_VERSION,
            true
        );
    }

    // FAQ page script (only on FAQ page)
    if ( is_page_template( 'page-faq.php' ) ) {
        wp_enqueue_script(
            'seminargo-faq',
            SEMINARGO_ASSETS_URL . 'js/faq.js',
            array(),
            SEMINARGO_VERSION,
            true
        );
    }

    // Hotel filters script (only on front page)
    if ( is_front_page() ) {
        wp_enqueue_script(
            'seminargo-hotel-filters',
            SEMINARGO_ASSETS_URL . 'js/hotel-filters.js',
            array(),
            SEMINARGO_VERSION,
            true
        );
    }

    // Localize script for AJAX
    wp_localize_script( 'seminargo-main', 'seminargo_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'seminargo-nonce' ),
    ) );

    // Comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'seminargo_scripts' );
