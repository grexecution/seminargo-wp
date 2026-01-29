<?php
/**
 * Elementor Compatibility
 *
 * Elementor page builder compatibility functions
 *
 * @package Seminargo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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

    /**
     * Force Elementor to use External CSS files (not inline)
     * This ensures CSS is loaded for all users, not just logged-in
     */
    add_action( 'init', function() {
        // Only update if not already set to external file
        $css_print_method = get_option( 'elementor_css_print_method' );
        if ( $css_print_method !== 'external' ) {
            update_option( 'elementor_css_print_method', 'external' );
        }
    } );

    /**
     * Ensure Elementor frontend styles are always enqueued
     */
    add_action( 'wp_enqueue_scripts', function() {
        // Make sure Elementor frontend CSS is loaded
        if ( class_exists( '\Elementor\Plugin' ) ) {
            $elementor = \Elementor\Plugin::instance();

            // Enqueue frontend styles
            if ( method_exists( $elementor->frontend, 'enqueue_styles' ) ) {
                $elementor->frontend->enqueue_styles();
            }

            // Enqueue post CSS for current post
            if ( is_singular() ) {
                $post_id = get_the_ID();
                if ( $post_id && $elementor->documents->get( $post_id ) ) {
                    // Force load the post CSS
                    $css_file = \Elementor\Core\Files\CSS\Post::create( $post_id );
                    $css_file->enqueue();
                }
            }
        }
    }, 5 ); // Priority 5 to run before other scripts

    /**
     * Regenerate Elementor CSS on theme switch or update
     */
    add_action( 'after_switch_theme', function() {
        if ( class_exists( '\Elementor\Plugin' ) ) {
            \Elementor\Plugin::instance()->files_manager->clear_cache();
        }
    } );

    /**
     * Fix: Ensure CSS files exist and are accessible
     */
    add_filter( 'elementor/frontend/print_google_fonts', '__return_true' );

    /**
     * Disable Elementor's CSS optimization that can cause issues
     */
    add_filter( 'elementor/frontend/allow_late_render', '__return_false' );

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
