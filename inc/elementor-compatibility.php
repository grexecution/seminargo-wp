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
