<?php
/**
 * Template part for displaying single posts
 *
 * @package Seminargo
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

        <?php if ( 'post' === get_post_type() ) : ?>
            <div class="entry-meta">
                <span class="posted-on">
                    <time class="entry-date published updated" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
                        <?php echo esc_html( get_the_date() ); ?>
                    </time>
                </span>
                <span class="byline">
                    <?php
                    printf(
                        /* translators: %s: post author. */
                        esc_html_x( 'by %s', 'post author', 'seminargo' ),
                        '<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
                    );
                    ?>
                </span>
                <span class="comments-link">
                    <?php comments_popup_link(
                        esc_html__( 'No Comments', 'seminargo' ),
                        esc_html__( '1 Comment', 'seminargo' ),
                        esc_html__( '% Comments', 'seminargo' )
                    ); ?>
                </span>
            </div><!-- .entry-meta -->
        <?php endif; ?>
    </header><!-- .entry-header -->

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="post-thumbnail">
            <?php the_post_thumbnail( 'seminargo-featured' ); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        the_content();

        wp_link_pages( array(
            'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'seminargo' ),
            'after'  => '</div>',
        ) );
        ?>
    </div><!-- .entry-content -->

    <footer class="entry-footer">
        <?php if ( 'post' === get_post_type() ) : ?>
            <div class="entry-taxonomies">
                <?php if ( has_category() ) : ?>
                    <div class="cat-links">
                        <span class="taxonomy-label"><?php esc_html_e( 'Categories:', 'seminargo' ); ?></span>
                        <?php the_category( ', ' ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( has_tag() ) : ?>
                    <div class="tags-links">
                        <span class="taxonomy-label"><?php esc_html_e( 'Tags:', 'seminargo' ); ?></span>
                        <?php the_tags( '', ', ' ); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( get_the_author_meta( 'description' ) ) : ?>
            <div class="author-bio">
                <div class="author-avatar">
                    <?php echo get_avatar( get_the_author_meta( 'ID' ), 100 ); ?>
                </div>
                <div class="author-details">
                    <h3 class="author-name">
                        <?php
                        printf(
                            /* translators: %s: post author. */
                            esc_html__( 'About %s', 'seminargo' ),
                            get_the_author()
                        );
                        ?>
                    </h3>
                    <p class="author-description">
                        <?php the_author_meta( 'description' ); ?>
                    </p>
                    <a class="author-link" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
                        <?php
                        printf(
                            /* translators: %s: post author. */
                            esc_html__( 'View all posts by %s', 'seminargo' ),
                            get_the_author()
                        );
                        ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </footer><!-- .entry-footer -->
</article><!-- #post-<?php the_ID(); ?> -->