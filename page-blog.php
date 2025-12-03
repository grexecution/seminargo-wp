<?php
/**
 * Template Name: Blog
 * Template for displaying blog posts archive
 *
 * @package Seminargo
 */

get_header();

// Get paged value for pagination
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

// Query blog posts
$blog_query = new WP_Query( array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 9,
    'paged'          => $paged,
) );

?>

<div id="primary" class="content-area blog-archive">
    <main id="main" class="site-main">

        <!-- Archive Hero -->
        <section class="archive-hero">
            <div class="container">
                <div class="archive-header">
                    <span class="archive-tagline"><?php esc_html_e( 'Aktuelles & Ratgeber', 'seminargo' ); ?></span>
                    <h1 class="archive-title"><?php echo esc_html( get_the_title() ); ?></h1>
                    <?php if ( get_the_content() ) : ?>
                        <div class="archive-description">
                            <?php the_content(); ?>
                        </div>
                    <?php else : ?>
                        <div class="archive-description">
                            <?php esc_html_e( 'Entdecken Sie hilfreiche Tipps, Trends und Neuigkeiten rund um Seminarhotels, Veranstaltungsplanung und erfolgreiche Events.', 'seminargo' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ( $blog_query->have_posts() ) : ?>

            <!-- Posts Grid -->
            <section class="posts-section">
                <div class="container">
                    <div class="posts-grid">
                        <?php
                        while ( $blog_query->have_posts() ) : $blog_query->the_post();
                            // Get post data
                            $post_data = array(
                                'id'           => get_the_ID(),
                                'title'        => get_the_title(),
                                'link'         => get_permalink(),
                                'excerpt'      => get_the_excerpt(),
                                'date'         => get_the_date(),
                                'author'       => get_the_author(),
                                'author_link'  => get_author_posts_url( get_the_author_meta( 'ID' ) ),
                                'categories'   => get_the_category(),
                                'comment_count'=> get_comments_number(),
                                'image'        => get_the_post_thumbnail_url( get_the_ID(), 'large' ) ?: 'https://placehold.co/600x400/e2e8f0/64748b?text=Blog+Post',
                            );
                        ?>
                            <article id="post-<?php echo $post_data['id']; ?>" class="blog-card">
                                <a href="<?php echo esc_url( $post_data['link'] ); ?>" class="blog-card-link">
                                    <div class="blog-card-image">
                                        <img src="<?php echo esc_url( $post_data['image'] ); ?>"
                                             alt="<?php echo esc_attr( $post_data['title'] ); ?>"
                                             loading="lazy">
                                        <?php if ( ! empty( $post_data['categories'] ) ) : ?>
                                            <div class="blog-card-category">
                                                <?php echo esc_html( $post_data['categories'][0]->name ); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="blog-card-content">
                                        <div class="blog-card-meta">
                                            <span class="blog-card-date">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                                <?php echo esc_html( $post_data['date'] ); ?>
                                            </span>
                                            <span class="blog-card-author">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                    <circle cx="12" cy="7" r="4"></circle>
                                                </svg>
                                                <?php echo esc_html( $post_data['author'] ); ?>
                                            </span>
                                        </div>
                                        <h2 class="blog-card-title"><?php echo esc_html( $post_data['title'] ); ?></h2>
                                        <p class="blog-card-excerpt"><?php echo esc_html( wp_trim_words( $post_data['excerpt'], 20 ) ); ?></p>
                                        <span class="blog-card-readmore">
                                            <?php esc_html_e( 'Weiterlesen', 'seminargo' ); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                <polyline points="12 5 19 12 12 19"></polyline>
                                            </svg>
                                        </span>
                                    </div>
                                </a>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php
                    echo paginate_links( array(
                        'total'              => $blog_query->max_num_pages,
                        'current'            => $paged,
                        'mid_size'           => 2,
                        'prev_text'          => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg> ' . __( 'Zurück', 'seminargo' ),
                        'next_text'          => __( 'Weiter', 'seminargo' ) . ' <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>',
                        'before_page_number' => '<span class="screen-reader-text">' . __( 'Seite', 'seminargo' ) . ' </span>',
                        'type'               => 'list',
                        'class'              => 'blog-pagination',
                    ) );
                    ?>
                </div>
            </section>

        <?php else : ?>

            <section class="no-results">
                <div class="container">
                    <div class="no-results-content">
                        <h1><?php esc_html_e( 'Noch keine Beiträge', 'seminargo' ); ?></h1>
                        <p><?php esc_html_e( 'Es sind noch keine Blog-Beiträge vorhanden. Schauen Sie bald wieder vorbei!', 'seminargo' ); ?></p>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button button-primary">
                            <?php esc_html_e( 'Zur Startseite', 'seminargo' ); ?>
                        </a>
                    </div>
                </div>
            </section>

        <?php endif; ?>
        <?php wp_reset_postdata(); ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
