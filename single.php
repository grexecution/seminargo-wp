<?php
/**
 * The template for displaying all single posts
 *
 * @package Seminargo
 */

get_header();

while ( have_posts() ) : the_post();

    $post_id = get_the_ID();
    $categories = get_the_category();
    $tags = get_the_tags();
    $author_id = get_the_author_meta( 'ID' );
    $author_name = get_the_author();
    $author_avatar = get_avatar_url( $author_id, array( 'size' => 80 ) );
    $author_bio = get_the_author_meta( 'description' );
    $author_posts_url = get_author_posts_url( $author_id );
    $featured_image = get_the_post_thumbnail_url( $post_id, 'full' );
    $reading_time = ceil( str_word_count( strip_tags( get_the_content() ) ) / 200 ); // ~200 words per minute

?>

<div id="primary" class="content-area blog-single">
    <main id="main" class="site-main">

        <!-- Article Header -->
        <article id="post-<?php echo $post_id; ?>" <?php post_class( 'blog-post' ); ?>>

            <div class="container blog-post-container">

                <!-- Breadcrumbs -->
                <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'seminargo' ); ?>">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'seminargo' ); ?></a>
                    <span class="separator">/</span>
                    <?php
                    // Find the blog page (page with Blog template)
                    $blog_page = get_pages( array(
                        'meta_key'   => '_wp_page_template',
                        'meta_value' => 'page-blog.php',
                        'number'     => 1
                    ) );
                    $blog_url = ! empty( $blog_page ) ? get_permalink( $blog_page[0]->ID ) : home_url( '/blog' );
                    ?>
                    <a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog', 'seminargo' ); ?></a>
                    <?php if ( ! empty( $categories ) ) : ?>
                        <span class="separator">/</span>
                        <a href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>"><?php echo esc_html( $categories[0]->name ); ?></a>
                    <?php endif; ?>
                    <span class="separator">/</span>
                    <span class="current"><?php the_title(); ?></span>
                </nav>

                <!-- Post Header -->
                <header class="blog-post-header">
                    <?php if ( ! empty( $categories ) ) : ?>
                        <div class="post-category-badge">
                            <a href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>">
                                <?php echo esc_html( $categories[0]->name ); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <h1 class="post-title"><?php the_title(); ?></h1>

                    <div class="post-meta">
                        <div class="post-meta-item">
                            <img src="<?php echo esc_url( $author_avatar ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" class="author-avatar">
                            <div class="author-info">
                                <a href="<?php echo esc_url( $author_posts_url ); ?>" class="author-name"><?php echo esc_html( $author_name ); ?></a>
                                <div class="post-meta-details">
                                    <span class="post-date"><?php echo get_the_date(); ?></span>
                                    <span class="meta-separator">•</span>
                                    <span class="reading-time"><?php printf( esc_html__( '%d Min. Lesezeit', 'seminargo' ), $reading_time ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                <?php if ( $featured_image ) : ?>
                    <div class="post-featured-image">
                        <img src="<?php echo esc_url( $featured_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
                    </div>
                <?php endif; ?>

                <!-- Post Content -->
                <div class="post-content-wrapper">
                    <div class="post-content">
                        <?php the_content(); ?>
                    </div>
                </div>

                <!-- Tags -->
                <?php if ( $tags ) : ?>
                    <div class="post-tags">
                        <div class="tags-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                <line x1="7" y1="7" x2="7.01" y2="7"></line>
                            </svg>
                            <?php esc_html_e( 'Tags:', 'seminargo' ); ?>
                        </div>
                        <div class="tags-list">
                            <?php foreach ( $tags as $tag ) : ?>
                                <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="tag-item">
                                    <?php echo esc_html( $tag->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Author Bio -->
                <?php if ( $author_bio ) : ?>
                    <div class="author-bio-card">
                        <div class="author-bio-content">
                            <img src="<?php echo esc_url( $author_avatar ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" class="author-bio-avatar">
                            <div class="author-bio-text">
                                <h3 class="author-bio-title"><?php esc_html_e( 'Über den Autor', 'seminargo' ); ?></h3>
                                <h4 class="author-bio-name">
                                    <a href="<?php echo esc_url( $author_posts_url ); ?>"><?php echo esc_html( $author_name ); ?></a>
                                </h4>
                                <p class="author-bio-description"><?php echo wp_kses_post( $author_bio ); ?></p>
                                <a href="<?php echo esc_url( $author_posts_url ); ?>" class="author-posts-link">
                                    <?php esc_html_e( 'Weitere Beiträge anzeigen', 'seminargo' ); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Post Navigation -->
                <div class="post-navigation-wrapper">
                    <?php
                    $prev_post = get_previous_post();
                    $next_post = get_next_post();
                    ?>
                    <?php if ( $prev_post || $next_post ) : ?>
                        <nav class="post-navigation">
                            <?php if ( $prev_post ) : ?>
                                <a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>" class="nav-post nav-post-prev">
                                    <span class="nav-direction">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="15 18 9 12 15 6"></polyline>
                                        </svg>
                                        <?php esc_html_e( 'Vorheriger Beitrag', 'seminargo' ); ?>
                                    </span>
                                    <span class="nav-title"><?php echo esc_html( get_the_title( $prev_post ) ); ?></span>
                                </a>
                            <?php endif; ?>
                            <?php if ( $next_post ) : ?>
                                <a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>" class="nav-post nav-post-next">
                                    <span class="nav-direction">
                                        <?php esc_html_e( 'Nächster Beitrag', 'seminargo' ); ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                    </span>
                                    <span class="nav-title"><?php echo esc_html( get_the_title( $next_post ) ); ?></span>
                                </a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>

            </div>

        </article>

        <!-- Related Posts -->
        <?php
        if ( ! empty( $categories ) ) {
            $related_args = array(
                'category__in'   => array( $categories[0]->term_id ),
                'post__not_in'   => array( $post_id ),
                'posts_per_page' => 3,
                'orderby'        => 'rand',
            );
            $related_posts = new WP_Query( $related_args );

            if ( $related_posts->have_posts() ) :
        ?>
            <section class="related-posts-section">
                <div class="container">
                    <div class="section-header">
                        <span class="section-tagline"><?php esc_html_e( 'Weiterlesen', 'seminargo' ); ?></span>
                        <h2 class="section-title"><?php esc_html_e( 'Ähnliche Beiträge', 'seminargo' ); ?></h2>
                    </div>
                    <div class="related-posts-grid">
                        <?php
                        while ( $related_posts->have_posts() ) : $related_posts->the_post();
                            $related_id = get_the_ID();
                            $related_image = get_the_post_thumbnail_url( $related_id, 'large' ) ?: 'https://placehold.co/600x400/e2e8f0/64748b?text=Blog+Post';
                            $related_cats = get_the_category( $related_id );
                        ?>
                            <article class="related-post-card">
                                <a href="<?php echo esc_url( get_permalink() ); ?>" class="related-post-link">
                                    <div class="related-post-image">
                                        <img src="<?php echo esc_url( $related_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                                        <?php if ( ! empty( $related_cats ) ) : ?>
                                            <div class="related-post-category"><?php echo esc_html( $related_cats[0]->name ); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="related-post-content">
                                        <span class="related-post-date"><?php echo get_the_date(); ?></span>
                                        <h3 class="related-post-title"><?php the_title(); ?></h3>
                                        <span class="related-post-readmore">
                                            <?php esc_html_e( 'Weiterlesen', 'seminargo' ); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                <polyline points="12 5 19 12 12 19"></polyline>
                                            </svg>
                                        </span>
                                    </div>
                                </a>
                            </article>
                        <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            </section>
        <?php
            endif;
        }
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php
endwhile;
get_footer();
