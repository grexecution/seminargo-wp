<?php
/**
 * Admin Tweaks
 *
 * WordPress admin interface customizations and improvements
 *
 * @package Seminargo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Remove "Mine" filter from Hotels admin list
 * All hotels are API-imported, not user-authored
 */
function seminargo_remove_mine_filter( $views ) {
    unset( $views['mine'] );
    return $views;
}
add_filter( 'views_edit-hotel', 'seminargo_remove_mine_filter' );
