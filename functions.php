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

// Collection post type (SEO landing pages)
if ( file_exists( SEMINARGO_INC_PATH . 'post-type-collection.php' ) ) {
    require SEMINARGO_INC_PATH . 'post-type-collection.php';
}

// Contact form settings
if ( file_exists( SEMINARGO_INC_PATH . 'contact-settings.php' ) ) {
    require SEMINARGO_INC_PATH . 'contact-settings.php';
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