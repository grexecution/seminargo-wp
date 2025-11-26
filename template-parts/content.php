<?php
/**
 * Template part for displaying posts
 *
 * @package Seminargo
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail( 'seminargo-thumbnail' ); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="post-content">
        <header class="entry-header">
            <?php
            if ( is_singular() ) :
                the_title( '<h1 class="entry-title">', '</h1>' );
            else :
                the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
            endif;
            ?>

            <?php if ( 'post' === get_post_type() ) : ?>
                <div class="entry-meta">
                    <span class="posted-on">
                        <time class="entry-date published" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
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
                </div><!-- .entry-meta -->
            <?php endif; ?>
        </header><!-- .entry-header -->

        <div class="entry-summary">
            <?php the_excerpt(); ?>
        </div><!-- .entry-summary -->

        <footer class="entry-footer">
            <a href="<?php the_permalink(); ?>" class="read-more">
                <?php esc_html_e( 'Read More', 'seminargo' ); ?>
                <span class="screen-reader-text"><?php the_title(); ?></span>
            </a>

            <?php if ( 'post' === get_post_type() ) : ?>
                <div class="entry-categories">
                    <?php the_category( ', ' ); ?>
                </div>
            <?php endif; ?>
        </footer><!-- .entry-footer -->
    </div><!-- .post-content -->
</article><!-- #post-<?php the_ID(); ?> -->