<?php
/**
 * Content Filters
 *
 * Filters for excerpt, upload mime types, and other content modifications
 *
 * @package Seminargo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
