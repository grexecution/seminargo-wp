<?php
/**
 * The main template file
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">

            <?php if ( have_posts() ) : ?>

                <?php if ( is_home() && ! is_front_page() ) : ?>
                    <header class="page-header">
                        <h1 class="page-title"><?php single_post_title(); ?></h1>
                    </header>
                <?php endif; ?>

                <div class="posts-grid">
                    <?php
                    // Start the Loop
                    while ( have_posts() ) : the_post();

                        // Include the Post-Format-specific template
                        get_template_part( 'template-parts/content', get_post_format() );

                    endwhile;
                    ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => __( '&laquo; Previous', 'seminargo' ),
                    'next_text' => __( 'Next &raquo;', 'seminargo' ),
                ) );
                ?>

            <?php else : ?>

                <?php get_template_part( 'template-parts/content', 'none' ); ?>

            <?php endif; ?>

        </div><!-- .container -->
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();