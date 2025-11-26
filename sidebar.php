<?php
/**
 * The sidebar containing the main widget area
 *
 * @package Seminargo
 */

if ( ! is_active_sidebar( 'sidebar-primary' ) ) {
    return;
}
?>

<aside id="secondary" class="widget-area sidebar">
    <?php dynamic_sidebar( 'sidebar-primary' ); ?>
</aside><!-- #secondary -->