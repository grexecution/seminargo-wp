<?php
/**
 * The template for displaying all pages
 *
 * @package Seminargo
 */

// Check if Elementor Canvas template is active
$elementor_page_layout = '';
if ( class_exists( '\Elementor\Plugin' ) ) {
    $elementor_page_layout = get_post_meta( get_the_ID(), '_elementor_page_layout', true );
}

// If Elementor Canvas, don't show header/footer
if ( 'elementor_canvas' === $elementor_page_layout ) {
    while ( have_posts() ) : the_post();
        the_content();
    endwhile;
} else {
    // Normal page or Elementor Full Width
    get_header();

    // Check if this is an Elementor page
    $is_elementor_page = ( 'elementor_header_footer' === $elementor_page_layout || \Elementor\Plugin::$instance->db->is_built_with_elementor( get_the_ID() ) );

    // Check if we should use container
    $use_container = ! $is_elementor_page;
    ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php if ( $use_container ) : ?>
                <div class="container">
            <?php endif; ?>

                <?php
                while ( have_posts() ) : the_post();

                    // For Elementor pages, just output the content
                    if ( $is_elementor_page ) {
                        the_content();
                    } else {
                        // For normal pages, use the template part
                        get_template_part( 'template-parts/content', 'page' );

                        // If comments are open or we have at least one comment, load up the comment template
                        if ( comments_open() || get_comments_number() ) :
                            comments_template();
                        endif;
                    }

                endwhile;
                ?>

            <?php if ( $use_container ) : ?>
                </div><!-- .container -->
            <?php endif; ?>
        </main><!-- #main -->
    </div><!-- #primary -->

    <?php get_footer();
}