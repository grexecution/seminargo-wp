<?php
/**
 * seminargo Front Page - 1:1 Match with Elementor Design
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <!-- Hero Section with Background extending through all elements -->
        <section class="hero-section-wrapper">
            <div class="hero-search-area">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">Finden Sie Ihr perfektes Tagungshotel</h1>
                        <p class="hero-subtitle">Über 24.000 Seminarhotels in Deutschland und Österreich</p>
                    </div>

                    <!-- seminargo Search Widget -->
                    <div class="search-widget-wrapper">
                        <div class="search-widget-wrapper-inner">
                            <div id="seminargo-widget"
                                 data-platform-url="https://lister-staging.seminargo.com/"></div>
                            <script src="https://platform-widget.dev.seminargo.eu/widget.js"></script>
                        </div>
                    </div>
                    <div class="search-widget-placeholder"></div>
                </div>
            </div>

            <!-- Hero Background Image with CTA -->
            <a href="#" class="hero-image-section" style="background-image: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&h=600&fit=crop');">
                <div class="hero-image-wrapper">
                    <div class="hero-overlay"></div>
                    <div class="hero-cta-content">
                        <h2 class="hero-cta-title">Kreativer Workshop im Grünen?</h2>
                        <p class="hero-cta-subtitle">Finden Sie Ihre perfekte Veranstaltungsumgebung.</p>
                        <span class="btn-inspirier">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                            <span>Inspirier mich</span>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Features Section (inside hero wrapper) -->
            <div class="features-section">
                <div class="features-grid">
                <a href="#hotels-section" class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h3 class="feature-title">Über 24.000 Seminar-Locations</h3>
                        <p class="feature-description">Erstklassige Veranstaltungsorte in Österreich und Deutschland</p>
                        <span class="feature-link"><span class="feature-link-text">Mehr erfahren</span> →</span>
                    </div>
                </a>

                <a href="#event-types-section" class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <path d="M9 11l3 3L22 4"></path>
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h3 class="feature-title">Einfaches Buchungssystem</h3>
                        <p class="feature-description">Planen und buchen Sie Ihre Events mit wenigen Klicks</p>
                        <span class="feature-link"><span class="feature-link-text">Mehr erfahren</span> →</span>
                    </div>
                </a>

                <a href="#popular-locations-section" class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h3 class="feature-title">Exklusive Angebote</h3>
                        <p class="feature-description">Profitieren Sie von maßgeschneiderten Paketen für Ihre Veranstaltung</p>
                        <span class="feature-link"><span class="feature-link-text">Mehr erfahren</span> →</span>
                    </div>
                </a>
            </div>
        </section> <!-- End of hero-section-wrapper -->

        <!-- Logo Slider Section -->
        <section class="logo-slider-section">
            <div class="container">
                <div class="logo-slider-wrapper">
                    <div class="logo-slider">
                        <?php
                        // Real client logos - Vector SVG format
                        $client_logos = array(
                            array('name' => 'REWE', 'image' => 'rewe.svg'),
                            array('name' => 'dm', 'image' => 'dm.svg'),
                            array('name' => 'Allianz', 'image' => 'allianz.svg'),
                            array('name' => 'Austrian Airlines', 'image' => 'austrian.svg'),
                            array('name' => 'Henkel', 'image' => 'henkel.svg'),
                            array('name' => 'DB Schenker', 'image' => 'dbschenker.svg'),
                            array('name' => 'STRABAG', 'image' => 'strabag.svg'),
                            array('name' => 'Mondi', 'image' => 'mondi.svg'),
                            array('name' => 'Agrana', 'image' => 'agrana.svg'),
                            array('name' => 'Doka', 'image' => 'doka.svg'),
                        );

                        // Display logos twice for seamless scrolling
                        for ($i = 0; $i < 2; $i++) :
                            foreach ($client_logos as $logo) :
                                $logo_path = get_template_directory_uri() . '/assets/images/client-vector/dark/' . $logo['image'];
                                ?>
                                <div class="logo-slide">
                                    <div class="logo-item" title="<?php echo esc_attr($logo['name']); ?>">
                                        <img src="<?php echo esc_url($logo_path); ?>" alt="<?php echo esc_attr($logo['name']); ?>" loading="lazy">
                                    </div>
                                </div>
                            <?php endforeach;
                        endfor; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hotels Section -->
        <?php
        // Get homepage visibility settings (default to shown if not set)
        $page_id = get_the_ID();
        $show_top_hotels_section = get_post_meta( $page_id, 'show_top_hotels_section', true ) !== '0';
        $show_event_types_section = get_post_meta( $page_id, 'show_event_types_section', true ) !== '0';
        $show_locations_section = get_post_meta( $page_id, 'show_locations_section', true ) !== '0';

        if ( $show_top_hotels_section ) :
        ?>
        <section id="hotels-section" class="hotels-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Unsere Empfehlungen</span>
                    <h2 class="section-title">Entdecken Sie unsere Top-Veranstaltungsorte</h2>
                </div>

                <!-- Filter Tabs -->
                <?php
                // Get filter tab visibility settings (default to shown if not set)
                $show_top_tab      = get_post_meta( $page_id, 'show_top_hotels_tab', true ) !== '0';
                $show_theme_tab    = get_post_meta( $page_id, 'show_theme_filter_tab', true ) !== '0';
                $show_location_tab = get_post_meta( $page_id, 'show_location_filter_tab', true ) !== '0';

                // Only show filter tabs if at least one is enabled
                if ( $show_top_tab || $show_theme_tab || $show_location_tab ) :
                ?>
                <div class="filter-tabs">
                    <?php if ( $show_top_tab ) : ?>
                    <button class="filter-tab active" data-filter="top">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        Top Hotels
                    </button>
                    <?php endif; ?>
                    <?php if ( $show_theme_tab ) : ?>
                    <button class="filter-tab<?php echo ! $show_top_tab ? ' active' : ''; ?>" data-filter="theme">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="9" y1="9" x2="15" y2="9"></line>
                            <line x1="9" y1="15" x2="15" y2="15"></line>
                        </svg>
                        Nach Thema
                    </button>
                    <?php endif; ?>
                    <?php if ( $show_location_tab ) : ?>
                    <button class="filter-tab<?php echo ! $show_top_tab && ! $show_theme_tab ? ' active' : ''; ?>" data-filter="location">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        Nach Region
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Filter Options -->
                <div class="filter-options">
                    <?php if ( $show_top_tab ) : ?>
                    <!-- Top Hotels Filter (Hidden by default) -->
                    <div class="filter-option-group" data-filter-group="top">
                        <p class="filter-description">Unsere handverlesenen Top-Veranstaltungsorte</p>
                    </div>
                    <?php endif; ?>

                    <?php if ( $show_theme_tab ) : ?>
                    <!-- Theme Filter -->
                    <div class="filter-option-group" data-filter-group="theme">
                        <div class="filter-buttons">
                            <button class="filter-button active" data-theme="all">Alle Themen</button>
                            <button class="filter-button" data-theme="seminar">Seminar</button>
                            <button class="filter-button" data-theme="tagung">Tagung</button>
                            <button class="filter-button" data-theme="spa">Spa & Wellness</button>
                            <button class="filter-button" data-theme="konferenz">Konferenz</button>
                            <button class="filter-button" data-theme="incentive">Incentive</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ( $show_location_tab ) : ?>
                    <!-- Location Filter -->
                    <div class="filter-option-group" data-filter-group="location">
                        <div class="filter-buttons">
                            <button class="filter-button active" data-location="all">Alle Regionen</button>
                            <button class="filter-button" data-location="wien">Wien</button>
                            <button class="filter-button" data-location="salzburg">Salzburg</button>
                            <button class="filter-button" data-location="tirol">Tirol</button>
                            <button class="filter-button" data-location="steiermark">Steiermark</button>
                            <button class="filter-button" data-location="kärnten">Kärnten</button>
                            <button class="filter-button" data-location="oberösterreich">Oberösterreich</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; // End filter tabs check ?>

                <div class="hotels-grid">
                    <?php
                    // Query featured hotels from WordPress (only those marked for homepage)
                    $featured_hotels = new WP_Query( array(
                        'post_type'      => 'hotel',
                        'posts_per_page' => 9,
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

                    if ( $featured_hotels->have_posts() ) :
                        while ( $featured_hotels->have_posts() ) : $featured_hotels->the_post();
                            // Get hotel image - same fallback pattern as single/archive pages
                            $hotel_image = get_the_post_thumbnail_url( get_the_ID(), 'large' );
                            if ( ! $hotel_image ) {
                                // First try WordPress gallery (downloaded images)
                                $gallery = get_post_meta( get_the_ID(), 'gallery', true );
                                if ( is_array( $gallery ) && ! empty( $gallery ) ) {
                                    $hotel_image = $gallery[0];
                                } else {
                                    // Fallback to external API image URLs
                                    $medias = json_decode( get_post_meta( get_the_ID(), 'medias_json', true ), true );
                                    if ( ! empty( $medias[0]['previewUrl'] ) ) {
                                        $hotel_image = $medias[0]['previewUrl'];
                                    } else {
                                        $hotel_image = 'https://images.seminargo.pro/hotel-83421-4-400x300-FIT_AND_TRIM-f09c5c96e1bc6e5e8f88c37c951bbaa2.webp';
                                    }
                                }
                            }

                            // Get hotel meta
                            $location = get_post_meta( get_the_ID(), 'location', true ) ?: get_post_meta( get_the_ID(), 'business_address_1', true ) ?: '';
                            $rooms = intval( get_post_meta( get_the_ID(), 'rooms', true ) );
                            $capacity = intval( get_post_meta( get_the_ID(), 'capacity', true ) );
                            $bedrooms = intval( get_post_meta( get_the_ID(), 'bedrooms', true ) );
                            $stars = floatval( get_post_meta( get_the_ID(), 'stars', true ) );
                            $rating = floatval( get_post_meta( get_the_ID(), 'rating', true ) );

                    ?>
                        <div class="hotel-card featured-hotel-card" data-location="<?php echo esc_attr( strtolower( $location ) ); ?>">
                            <a href="<?php echo esc_url( get_permalink() ); ?>">
                                <div class="hotel-image">
                                    <img src="<?php echo esc_url( $hotel_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                                    <?php if ( $stars > 0 ) : ?>
                                        <span class="hotel-rating-badge"><?php echo esc_html( $stars ); ?>★</span>
                                    <?php endif; ?>
                                    <?php if ( $rating > 0 ) : ?>
                                        <span class="hotel-review-badge">
                                            <svg viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                            <?php echo number_format( $rating, 1 ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="hotel-content">
                                    <h3 class="hotel-title">
                                        <?php the_title(); ?>
                                    </h3>
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
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        </section>
        <?php endif; // End show_top_hotels_section check ?>

        <!-- Event Types Section -->
        <?php if ( $show_event_types_section ) : ?>
        <section id="event-types-section" class="event-types-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Für jeden Anlass</span>
                    <h2 class="section-title">Finden Sie Ihre perfekte Veranstaltungsart</h2>
                </div>
                <div class="event-types-grid">
                    <?php
                    // Get selected collection IDs from homepage custom field
                    $event_type_collections = get_post_meta( get_the_ID(), 'event_type_collections', true );

                    // Query collections
                    if ( ! empty( $event_type_collections ) ) {
                        $event_types_query = new WP_Query( array(
                            'post_type'      => 'collection',
                            'posts_per_page' => 6,
                            'post__in'       => explode( ',', $event_type_collections ),
                            'orderby'        => 'post__in',
                            'post_status'    => 'publish',
                        ) );
                    } else {
                        // Fallback: show all collections
                        $event_types_query = new WP_Query( array(
                            'post_type'      => 'collection',
                            'posts_per_page' => 6,
                            'orderby'        => 'menu_order',
                            'order'          => 'ASC',
                            'post_status'    => 'publish',
                        ) );
                    }

                    if ( $event_types_query->have_posts() ) :
                        while ( $event_types_query->have_posts() ) : $event_types_query->the_post();
                            $collection_link = get_permalink();
                            // Use custom home excerpt if available, otherwise fall back to regular excerpt
                            $collection_excerpt = get_post_meta( get_the_ID(), 'home_excerpt', true );
                            if ( empty( $collection_excerpt ) ) {
                                $collection_excerpt = get_the_excerpt();
                            }

                            // Get icon from selected icon key
                            $icon_key = get_post_meta( get_the_ID(), 'collection_icon', true );
                            $all_icons = function_exists( 'seminargo_get_collection_icons' ) ? seminargo_get_collection_icons() : array();

                            // Debug: Show what we're getting
                            // echo '<!-- Collection ID: ' . get_the_ID() . ' | Icon Key: ' . $icon_key . ' -->';

                            // Get the SVG for the selected icon
                            if ( ! empty( $icon_key ) && isset( $all_icons[ $icon_key ] ) ) {
                                $collection_icon = $all_icons[ $icon_key ]['svg'];
                            } else {
                                // Default icon if none selected
                                $collection_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>';
                            }
                    ?>
                        <a href="<?php echo esc_url( $collection_link ); ?>" class="event-type-card">
                            <div class="event-type-icon">
                                <?php
                                // Output SVG directly (safe since it comes from our own function)
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo $collection_icon;
                                ?>
                            </div>
                            <h3 class="event-type-title"><?php the_title(); ?></h3>
                            <p class="event-type-description"><?php echo esc_html( $collection_excerpt ?: 'Entdecken Sie passende Locations' ); ?></p>
                            <span class="event-type-arrow">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                        </a>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        </section>
        <?php endif; // End show_event_types_section check ?>

        <!-- Popular Locations Section -->
        <?php if ( $show_locations_section ) : ?>
        <section id="popular-locations-section" class="popular-locations-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Beliebte Regionen</span>
                    <h2 class="section-title">Angesagte Locations</h2>
                </div>
                <div class="locations-grid">
                    <?php
                    // Get selected collection IDs from homepage custom field
                    $popular_location_collections = get_post_meta( get_the_ID(), 'popular_location_collections', true );

                    // Query collections
                    if ( ! empty( $popular_location_collections ) ) {
                        $locations_query = new WP_Query( array(
                            'post_type'      => 'collection',
                            'posts_per_page' => 6,
                            'post__in'       => explode( ',', $popular_location_collections ),
                            'orderby'        => 'post__in',
                            'post_status'    => 'publish',
                        ) );
                    } else {
                        // Fallback: show random collections
                        $locations_query = new WP_Query( array(
                            'post_type'      => 'collection',
                            'posts_per_page' => 6,
                            'orderby'        => 'rand',
                            'post_status'    => 'publish',
                        ) );
                    }

                    if ( $locations_query->have_posts() ) :
                        while ( $locations_query->have_posts() ) : $locations_query->the_post();
                            $collection_link = get_permalink();
                            $collection_image = get_the_post_thumbnail_url( get_the_ID(), 'medium' );

                            // Fallback image
                            if ( empty( $collection_image ) ) {
                                $collection_image = 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=400&h=300&fit=crop';
                            }
                    ?>
                        <div class="location-card">
                            <a href="<?php echo esc_url( $collection_link ); ?>">
                                <div class="location-image">
                                    <img src="<?php echo esc_url( $collection_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                                    <div class="location-overlay">
                                        <h3 class="location-title"><?php the_title(); ?></h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        </section>
        <?php endif; // End show_locations_section check ?>

        <!-- Location Finder CTA Section -->
        <?php seminargo_cta_section(); ?>

        <!-- SEO Content Section -->
        <section class="seo-content-section">
            <div class="container">
                <div class="seo-content-wrapper">
                    <h2>Ihre digitale Buchungsplattform für Seminarhotels in Österreich und Deutschland</h2>

                    <div class="seo-content-grid">
                        <div class="seo-content-block">
                            <h3>Effiziente Veranstaltungsplanung</h3>
                            <p>seminargo ist die führende digitale Buchungsplattform, die Veranstaltungsplaner mit über 24.000 Seminarhotels in Österreich und Deutschland verbindet. Unsere innovative Technologie spart Ihnen wertvolle Zeit bei der Suche nach dem perfekten Veranstaltungsort für Konferenzen, Meetings und Firmenevents.</p>
                            <a href="/preise" class="seo-link">
                                <span class="feature-link"><span class="feature-link-text">Mehr erfahren</span> →</span>
                            </a>
                        </div>

                        <div class="seo-content-block">
                            <h3>Expertise & persönliche Beratung</h3>
                            <p>Unser Expertenteam sorgt dafür, dass Sie präzise Angebote erhalten und das ideale Seminarhotel für Ihre Veranstaltung finden. Von Wien über Graz bis München – wir kennen die besten Locations in Österreich und Deutschland und beraten Sie kostenlos bei Ihrer Auswahl.</p>
                            <a href="/kontakt" class="seo-link">
                                <span class="feature-link"><span class="feature-link-text">Mehr erfahren</span> →</span>
                            </a>
                        </div>

                        <div class="seo-content-block">
                            <h3>Umfassende Ressourcen für Ihre Planung</h3>
                            <p>Nutzen Sie unsere praktischen Tools wie Checklisten, E-Books und den interaktiven Quick-Check, um systematisch Ihre perfekte Location zu identifizieren. Mit unseren Ressourcen wird die Eventplanung zum Kinderspiel.</p>
                            <a href="/downloads" class="seo-link">
                                <span class="feature-link"><span class="feature-link-text">Mehr erfahren</span> →</span>
                            </a>
                        </div>
                    </div>

                    <div class="trust-signals">
                        <p class="trust-text">Support-Team verfügbar Mo-Do 8-18 Uhr, Fr 8-14 Uhr in unseren Büros in München und Wien.</p>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();