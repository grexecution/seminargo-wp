<?php
/**
 * The template for displaying all single posts
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">
            <div class="content-wrapper">
                <div class="primary-content">

                    <?php
                    while ( have_posts() ) : the_post();

                        get_template_part( 'template-parts/content', 'single' );

                        // Post navigation
                        the_post_navigation( array(
                            'prev_text' => '<span class="nav-subtitle">' . __( 'Previous:', 'seminargo' ) . '</span> <span class="nav-title">%title</span>',
                            'next_text' => '<span class="nav-subtitle">' . __( 'Next:', 'seminargo' ) . '</span> <span class="nav-title">%title</span>',
                        ) );

                        // If comments are open or we have at least one comment, load up the comment template
                        if ( comments_open() || get_comments_number() ) :
                            comments_template();
                        endif;

                    endwhile;
                    ?>

                </div><!-- .primary-content -->

                <?php get_sidebar(); ?>

            </div><!-- .content-wrapper -->
        </div><!-- .container -->
    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();