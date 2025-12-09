<?php
/**
 * Seminargo Theme Functions
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
define( 'SEMINARGO_VERSION', '1.2.0' );
define( 'SEMINARGO_THEME_PATH', get_template_directory() );
define( 'SEMINARGO_THEME_URL', get_template_directory_uri() );
define( 'SEMINARGO_ASSETS_PATH', SEMINARGO_THEME_PATH . '/assets/' );
define( 'SEMINARGO_ASSETS_URL', SEMINARGO_THEME_URL . '/assets/' );
define( 'SEMINARGO_INC_PATH', SEMINARGO_THEME_PATH . '/inc/' );

/**
 * Set content width
 */
if ( ! isset( $content_width ) ) {
    $content_width = 1200;
}

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

/**
 * Register widget areas
 */
function seminargo_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Primary Sidebar', 'seminargo' ),
        'id'            => 'sidebar-primary',
        'description'   => esc_html__( 'Add widgets here to appear in your sidebar.', 'seminargo' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget Area 1', 'seminargo' ),
        'id'            => 'footer-1',
        'description'   => esc_html__( 'Add widgets here to appear in footer column 1.', 'seminargo' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget Area 2', 'seminargo' ),
        'id'            => 'footer-2',
        'description'   => esc_html__( 'Add widgets here to appear in footer column 2.', 'seminargo' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget Area 3', 'seminargo' ),
        'id'            => 'footer-3',
        'description'   => esc_html__( 'Add widgets here to appear in footer column 3.', 'seminargo' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Widget Area 4', 'seminargo' ),
        'id'            => 'footer-4',
        'description'   => esc_html__( 'Add widgets here to appear in footer column 4.', 'seminargo' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'seminargo_widgets_init' );

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

// Collection post type (SEO landing pages)
if ( file_exists( SEMINARGO_INC_PATH . 'post-type-collection.php' ) ) {
    require SEMINARGO_INC_PATH . 'post-type-collection.php';
}

// Contact form settings
if ( file_exists( SEMINARGO_INC_PATH . 'contact-settings.php' ) ) {
    require SEMINARGO_INC_PATH . 'contact-settings.php';
}

/**
 * Compatibility functions for Elementor
 */
if ( defined( 'ELEMENTOR_VERSION' ) ) {
    // Register Elementor locations
    add_action( 'elementor/theme/register_locations', function( $elementor_theme_manager ) {
        $elementor_theme_manager->register_all_core_location();
    } );

    // Add Elementor theme support
    add_theme_support( 'elementor' );

    // Fix Elementor Pro query control taxonomy warning - multiple approaches

    // 1. Filter get_object_terms to catch WP_Error
    add_filter( 'get_object_terms', function( $terms, $object_ids, $taxonomies, $args ) {
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        return $terms;
    }, 10, 4 );

    // 2. Filter get_terms to catch WP_Error
    add_filter( 'get_terms', function( $terms, $taxonomies, $args, $term_query ) {
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        return $terms;
    }, 10, 4 );

    // 3. Suppress WP_Error for taxonomy queries in Elementor context
    add_filter( 'get_the_terms', function( $terms, $post_id, $taxonomy ) {
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        return $terms;
    }, 10, 3 );

    // 4. Add error handling to wp_get_object_terms
    add_filter( 'wp_get_object_terms', function( $terms, $object_ids, $taxonomies, $args ) {
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        return $terms;
    }, 10, 4 );
}

/**
 * Custom excerpt length
 */
function seminargo_excerpt_length( $length ) {
    if ( is_admin() ) {
        return $length;
    }
    return 30;
}
add_filter( 'excerpt_length', 'seminargo_excerpt_length', 999 );

/**
 * Custom excerpt more
 */
function seminargo_excerpt_more( $more ) {
    if ( is_admin() ) {
        return $more;
    }
    return '...';
}
add_filter( 'excerpt_more', 'seminargo_excerpt_more' );

/**
 * Allow additional MIME types for hotel images
 * Fixes issues with uppercase extensions (.JPG vs .jpg)
 */
function seminargo_upload_mimes( $mimes ) {
    // Ensure all common image formats are allowed
    $mimes['jpg|jpeg|jpe'] = 'image/jpeg';
    $mimes['gif'] = 'image/gif';
    $mimes['png'] = 'image/png';
    $mimes['webp'] = 'image/webp';
    $mimes['svg'] = 'image/svg+xml';

    return $mimes;
}
add_filter( 'upload_mimes', 'seminargo_upload_mimes' );