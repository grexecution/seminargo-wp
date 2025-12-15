<?php
/**
 * Template for displaying single collection posts (SEO landing pages)
 *
 * @package Seminargo
 */

get_header();

while ( have_posts() ) : the_post();

    $post_id = get_the_ID();

    // Get hero data
    $hero_subtitle = get_post_meta( $post_id, 'hero_subtitle', true );
    $hero_image = get_post_meta( $post_id, 'hero_image', true );
    $hero_overlay_opacity = get_post_meta( $post_id, 'hero_overlay_opacity', true ) ?: '50';

    // Fallback to featured image
    if ( ! $hero_image ) {
        $hero_image = get_the_post_thumbnail_url( $post_id, 'full' );
    }

    // Default hero image if nothing set
    if ( ! $hero_image ) {
        $hero_image = SEMINARGO_ASSETS_URL . 'images/default-hero.jpg';
    }

    // Get CTA data
    $cta_enabled = get_post_meta( $post_id, 'cta_enabled', true );
    $cta_title = get_post_meta( $post_id, 'cta_title', true ) ?: __( 'Kostenlose Beratung', 'seminargo' );
    $cta_description = get_post_meta( $post_id, 'cta_description', true ) ?: __( 'Lassen Sie sich von unseren Experten beraten und finden Sie das perfekte Seminarhotel.', 'seminargo' );
    $cta_button_text = get_post_meta( $post_id, 'cta_button_text', true ) ?: __( 'Jetzt anfragen', 'seminargo' );
    $cta_button_url = get_post_meta( $post_id, 'cta_button_url', true ) ?: home_url( '/kontakt' );
    $cta_phone = get_post_meta( $post_id, 'cta_phone', true );

    // Get hotels section data
    $linked_hotels = get_post_meta( $post_id, 'linked_hotels', true ) ?: [];
    $hotels_title = get_post_meta( $post_id, 'hotels_section_title', true ) ?: __( 'Empfohlene Hotels', 'seminargo' );
    $hotels_subtitle = get_post_meta( $post_id, 'hotels_section_subtitle', true );

?>

<div id="primary" class="content-area collection-single">
    <main id="main" class="site-main">

        <!-- Hero Section (same style as home page) -->
        <section class="collection-hero-wrapper">
            <div class="container">
                <!-- Breadcrumbs -->
                <nav class="collection-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'seminargo' ); ?>">
                    <a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'seminargo' ); ?></a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo esc_html( get_the_title() ); ?></span>
                </nav>

                <!-- Hero Image (like home page) -->
                <div class="collection-hero-image" style="background-image: url('<?php echo esc_url( $hero_image ); ?>');">
                    <div class="hero-image-wrapper">
                        <div class="hero-overlay"></div>
                        <div class="hero-cta-content">
                            <h1 class="hero-cta-title"><?php the_title(); ?></h1>
                            <?php if ( $hero_subtitle ) : ?>
                                <p class="hero-cta-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content Section -->
        <section class="collection-content-section">
            <div class="container">
                <div class="collection-layout <?php echo $cta_enabled ? 'has-sidebar' : 'no-sidebar'; ?>">

                    <!-- Main Content -->
                    <div class="collection-main-content">
                        <article class="collection-article">
                            <div class="entry-content">
                                <?php the_content(); ?>
                            </div>
                        </article>
                    </div>

                    <!-- Sidebar CTA (Sticky) -->
                    <?php if ( $cta_enabled ) : ?>
                    <aside class="collection-sidebar">
                        <div class="cta-card sticky">
                            <div class="cta-card-header">
                                <div class="cta-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                </div>
                                <h3 class="cta-title"><?php echo esc_html( $cta_title ); ?></h3>
                            </div>
                            <div class="cta-card-body">
                                <p class="cta-description"><?php echo esc_html( $cta_description ); ?></p>

                                <?php if ( $cta_phone ) : ?>
                                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $cta_phone ) ); ?>" class="cta-phone-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <?php echo esc_html( $cta_phone ); ?>
                                </a>
                                <?php endif; ?>

                                <a href="<?php echo esc_url( $cta_button_url ); ?>" class="btn-cta-primary">
                                    <?php echo esc_html( $cta_button_text ); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>

                            <!-- Trust Indicators -->
                            <div class="cta-card-footer">
                                <div class="trust-indicators">
                                    <div class="trust-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span><?php esc_html_e( 'Kostenlos & unverbindlich', 'seminargo' ); ?></span>
                                    </div>
                                    <div class="trust-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span><?php esc_html_e( 'Persönliche Beratung', 'seminargo' ); ?></span>
                                    </div>
                                    <div class="trust-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span><?php esc_html_e( 'Schnelle Rückmeldung', 'seminargo' ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                    <?php endif; ?>

                </div>
            </div>
        </section>

        <!-- Hotels Grid Section -->
        <?php if ( ! empty( $linked_hotels ) ) :
            // Get hotel posts
            $hotels_query = new WP_Query( [
                'post_type'      => 'hotel',
                'post_status'    => 'publish',
                'post__in'       => $linked_hotels,
                'orderby'        => 'post__in',
                'posts_per_page' => -1,
            ] );

            if ( $hotels_query->have_posts() ) :
        ?>
        <section class="collection-hotels-section">
            <div class="container">
                <div class="section-header">
                    <?php if ( $hotels_subtitle ) : ?>
                        <span class="section-tagline"><?php echo esc_html( $hotels_subtitle ); ?></span>
                    <?php endif; ?>
                    <h2 class="section-title"><?php echo esc_html( $hotels_title ); ?></h2>
                </div>

                <div class="hotels-grid">
                    <?php
                    while ( $hotels_query->have_posts() ) : $hotels_query->the_post();
                        $hotel_id = get_the_ID();

                        // Get featured image
                        $hotel_image = get_the_post_thumbnail_url( $hotel_id, 'large' );
                        if ( ! $hotel_image ) {
                            $medias = json_decode( get_post_meta( $hotel_id, 'medias_json', true ), true );
                            if ( ! empty( $medias[0]['previewUrl'] ) ) {
                                $hotel_image = $medias[0]['previewUrl'];
                            } else {
                                $hotel_image = 'https://placehold.co/400x300/e2e8f0/64748b?text=Hotel';
                            }
                        }

                        // Get hotel data
                        $hotel_city = get_post_meta( $hotel_id, 'business_city', true );

                        $hotel_data = [
                            'id'       => $hotel_id,
                            'title'    => get_the_title(),
                            'link'     => get_permalink(),
                            'image'    => $hotel_image,
                            'location' => $hotel_city,
                            'stars'    => floatval( get_post_meta( $hotel_id, 'stars', true ) ),
                            'rating'   => floatval( get_post_meta( $hotel_id, 'rating', true ) ),
                            'rooms'    => intval( get_post_meta( $hotel_id, 'rooms', true ) ),
                            'capacity' => intval( get_post_meta( $hotel_id, 'capacity', true ) ),
                        ];

                        get_template_part( 'template-parts/hotel-card', null, $hotel_data );
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>

                <div class="section-footer">
                    <a href="<?php echo esc_url( home_url( 'https://lister-dev.seminargo.com/' ) ); ?>" class="btn-view-all">
                        <?php esc_html_e( 'Alle Hotels ansehen', 'seminargo' ); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
        <?php
            endif;
        endif;
        ?>

        <!-- Call to Action Section -->
        <?php seminargo_cta_section(); ?>

    </main>
</div>

<?php
endwhile;
get_footer();
?>
