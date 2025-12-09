<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <!-- 404 Hero Section -->
        <section class="error-404-hero">
            <div class="container">
                <div class="error-404-content">
                    <div class="error-404-number">404</div>
                    <h1 class="error-404-title">Seite nicht gefunden</h1>
                    <p class="error-404-subtitle">Die Seite, die Sie suchen, existiert leider nicht oder wurde verschoben.</p>

                    <!-- Action Buttons -->
                    <div class="error-404-actions">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button button-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Zur Startseite
                        </a>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>#search" class="button button-outline">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            Hotels suchen
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Helpful Links Section -->
        <section class="error-404-links-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Hilfreiche Links</span>
                    <h2 class="section-title">Vielleicht finden Sie hier was Sie suchen</h2>
                </div>

                <div class="error-404-links-grid">
                    <!-- Popular Pages -->
                    <div class="error-404-link-card">
                        <div class="link-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <h3 class="link-card-title">Beliebte Hotels</h3>
                        <p class="link-card-description">Entdecken Sie unsere empfohlenen Seminarhotels</p>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="link-card-link">
                            Hotels ansehen
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>

                    <!-- Search -->
                    <div class="error-404-link-card">
                        <div class="link-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                        <h3 class="link-card-title">Hotel finden</h3>
                        <p class="link-card-description">Suchen Sie nach dem perfekten Veranstaltungsort</p>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>#search" class="link-card-link">
                            Jetzt suchen
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>

                    <!-- Contact -->
                    <div class="error-404-link-card">
                        <div class="link-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <h3 class="link-card-title">Kontakt</h3>
                        <p class="link-card-description">Unser Team hilft Ihnen gerne weiter</p>
                        <a href="mailto:info@seminargo.com" class="link-card-link">
                            Kontaktieren Sie uns
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>

                    <!-- Blog -->
                    <div class="error-404-link-card">
                        <div class="link-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        </div>
                        <h3 class="link-card-title">Blog & Ratgeber</h3>
                        <p class="link-card-description">Tipps für erfolgreiche Veranstaltungen</p>
                        <a href="<?php echo esc_url( home_url( '/blog' ) ); ?>" class="link-card-link">
                            Zum Blog
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <?php
        // Get featured hotels for suggestions
        $featured_hotels = new WP_Query( array(
            'post_type'      => 'hotel',
            'posts_per_page' => 3,
            'orderby'        => 'rand',
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => 'featured_on_landing',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        ) );

        if ( $featured_hotels->have_posts() ) : ?>
        <!-- Suggested Hotels Section -->
        <section class="error-404-hotels-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Unsere Empfehlungen</span>
                    <h2 class="section-title">Vielleicht interessiert Sie das</h2>
                </div>
                <div class="hotels-grid">
                    <?php while ( $featured_hotels->have_posts() ) : $featured_hotels->the_post();
                        // Get hotel image
                        $hotel_image = get_the_post_thumbnail_url( get_the_ID(), 'large' );
                        if ( ! $hotel_image ) {
                            $gallery = get_post_meta( get_the_ID(), 'gallery', true );
                            if ( is_array( $gallery ) && ! empty( $gallery ) ) {
                                $hotel_image = $gallery[0];
                            } else {
                                $hotel_image = 'https://images.seminargo.pro/hotel-83421-4-400x300-FIT_AND_TRIM-f09c5c96e1bc6e5e8f88c37c951bbaa2.webp';
                            }
                        }

                        // Get hotel meta
                        $location = get_post_meta( get_the_ID(), 'location', true ) ?: get_post_meta( get_the_ID(), 'business_address_1', true ) ?: '';
                        $rooms = intval( get_post_meta( get_the_ID(), 'rooms', true ) );
                        $capacity = intval( get_post_meta( get_the_ID(), 'capacity', true ) );
                    ?>
                        <div class="hotel-card featured-hotel-card">
                            <a href="<?php echo esc_url( get_permalink() ); ?>">
                                <div class="hotel-image">
                                    <img src="<?php echo esc_url( $hotel_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                                </div>
                                <div class="hotel-content">
                                    <h3 class="hotel-title"><?php the_title(); ?></h3>
                                    <?php if ( $location ) : ?>
                                        <p class="hotel-location">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                            <?php echo esc_html( $location ); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ( $rooms > 0 || $capacity > 0 ) : ?>
                                    <div class="hotel-info-features">
                                        <?php if ( $rooms > 0 ) : ?>
                                        <div class="info-feature">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                            </svg>
                                            <div class="info-feature-content">
                                                <span class="info-feature-value"><?php echo esc_html( $rooms ); ?></span>
                                                <span class="info-feature-label"><?php esc_html_e( 'Tagungsräume', 'seminargo' ); ?></span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ( $capacity > 0 ) : ?>
                                        <div class="info-feature">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                            <div class="info-feature-content">
                                                <span class="info-feature-value"><?php echo esc_html( $capacity ); ?></span>
                                                <span class="info-feature-label"><?php esc_html_e( 'max. Personen', 'seminargo' ); ?></span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
