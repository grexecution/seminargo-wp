<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="container">

            <section class="error-404 not-found">
                <header class="page-header">
                    <h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'seminargo' ); ?></h1>
                </header><!-- .page-header -->

                <div class="page-content">
                    <p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'seminargo' ); ?></p>

                    <?php get_search_form(); ?>

                    <div class="error-404-widgets">
                        <div class="widget">
                            <h2 class="widget-title"><?php esc_html_e( 'Recent Posts', 'seminargo' ); ?></h2>
                            <ul>
                                <?php
                                $recent_posts = wp_get_recent_posts( array(
                                    'numberposts' => 5,
                                    'post_status' => 'publish',
                                ) );
                                foreach ( $recent_posts as $post ) :
                                    ?>
                                    <li>
                                        <a href="<?php echo get_permalink( $post['ID'] ); ?>">
                                            <?php echo $post['post_title']; ?>
                                        </a>
                                    </li>
                                <?php endforeach; wp_reset_query(); ?>
                            </ul>
                        </div>

                        <div class="widget">
                            <h2 class="widget-title"><?php esc_html_e( 'Categories', 'seminargo' ); ?></h2>
                            <ul>
                                <?php wp_list_categories( array(
                                    'orderby'    => 'count',
                                    'order'      => 'DESC',
                                    'show_count' => 1,
                                    'title_li'   => '',
                                    'number'     => 10,
                                ) ); ?>
                            </ul>
                        </div>

                        <div class="widget">
                            <h2 class="widget-title"><?php esc_html_e( 'Archives', 'seminargo' ); ?></h2>
                            <ul>
                                <?php wp_get_archives( array(
                                    'type'  => 'monthly',
                                    'limit' => 12,
                                ) ); ?>
                            </ul>
                        </div>
                    </div><!-- .error-404-widgets -->

                </div><!-- .page-content -->
            </section><!-- .error-404 -->

        </div><!-- .container -->
    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();