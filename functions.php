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
 * Output CTA Section with static content
 */
function seminargo_cta_section() {
    get_template_part( 'template-parts/cta-section', null, [
        'eyebrow' => 'Schnell · Persönlich · Kostenlos',
        'heading' => 'Ihre Traum-Location in 24 Stunden',
        'description' => 'Unsere Experten kennen jede Location persönlich. Sie sagen uns, was Sie brauchen – wir liefern maßgeschneiderte Empfehlungen. Professionell, schnell und 100% kostenfrei.',
        'buttons' => [
            [
                'text' => 'Sofort Anrufen',
                'url' => 'tel:+43190858',
                'icon' => 'phone',
                'style' => 'white'
            ],
            [
                'text' => 'Beratung anfragen',
                'url' => 'mailto:info@seminargo.com',
                'icon' => 'email',
                'style' => 'outline-white'
            ]
        ],
        'style' => 'gradient'
    ] );
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
    // Only show on front page
    if ( get_option( 'page_on_front' ) != $post->ID && ! is_front_page() ) {
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

            <div class="visibility-group">
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
            </div>
        </div>

        <!-- Event Types Section -->
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

        <!-- Popular Locations Section -->
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

        <script>
        jQuery(document).ready(function($) {
            // Make lists sortable
            $('#selected-event-types, #selected-locations').sortable({
                handle: '.drag-handle',
                opacity: 0.8,
                cursor: 'move',
                update: function(event, ui) {
                    updateHiddenInput($(this));
                }
            });
        });

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

    // Save section visibility settings (save as '1' or '0')
    update_post_meta( $post_id, 'show_top_hotels_section', isset( $_POST['show_top_hotels_section'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_event_types_section', isset( $_POST['show_event_types_section'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_locations_section', isset( $_POST['show_locations_section'] ) ? '1' : '0' );

    // Save filter tab visibility settings (save as '1' or '0')
    update_post_meta( $post_id, 'show_top_hotels_tab', isset( $_POST['show_top_hotels_tab'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_theme_filter_tab', isset( $_POST['show_theme_filter_tab'] ) ? '1' : '0' );
    update_post_meta( $post_id, 'show_location_filter_tab', isset( $_POST['show_location_filter_tab'] ) ? '1' : '0' );
}
add_action( 'save_post', 'seminargo_save_homepage_collections_meta' );