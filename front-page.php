<?php
/**
 * Seminargo Front Page - 1:1 Match with Elementor Design
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
                        <h1 class="hero-title">Finden Sie Ihr perfektes Seminarhotel</h1>
                        <p class="hero-subtitle">Über 24.000 Seminarhotels in Deutschland und Österreich</p>
                    </div>

                    <!-- Custom Search from Archive Page -->
                    <div class="search-filters-wrapper">
                        <form id="hotel-search-form" class="hotel-search-form" method="GET" action="<?php echo esc_url( home_url( '/seminarhotels' ) ); ?>">

                            <!-- Main Search Bar -->
                            <div class="search-bar-main">
                                <div class="search-field search-location">
                                    <label for="search-location">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                    </label>
                                    <input type="text" id="search-location" name="location" placeholder="Wo? (Ort, Region oder PLZ)">
                                </div>

                                <div class="search-field search-capacity">
                                    <label for="search-capacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </label>
                                    <input type="number" id="search-capacity" name="capacity" placeholder="Personen" min="1">
                                </div>

                                <div class="search-field search-date">
                                    <label for="search-date">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                    </label>
                                    <input type="date" id="search-date" name="date">
                                </div>

                                <button type="submit" class="btn-search-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <path d="m21 21-4.35-4.35"></path>
                                    </svg>
                                    <span><?php esc_html_e( 'Suchen', 'seminargo' ); ?></span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Hero Background Image with CTA -->
            <a href="#" class="hero-image-section" style="background-image: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&h=600&fit=crop');">
                <div class="hero-image-wrapper">
                    <div class="hero-overlay"></div>
                    <div class="hero-cta-content">
                        <h2 class="hero-cta-title">Kreativer Workshop im Grünen?</h2>
                        <p class="hero-cta-subtitle">Finde deine perfekte Veranstaltungsumgebung.</p>
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
                <div class="feature-item">
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
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <path d="M9 11l3 3L22 4"></path>
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h3 class="feature-title">Einfaches Buchungssystem</h3>
                        <p class="feature-description">Planen und buchen Sie Ihre Events mit wenigen Klicks</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                        </svg>
                    </div>
                    <div class="feature-content">
                        <h3 class="feature-title">Exklusive Angebote</h3>
                        <p class="feature-description">Profitieren Sie von maßgeschneiderten Paketen für Ihre Veranstaltung</p>
                    </div>
                </div>
            </div>
        </section> <!-- End of hero-section-wrapper -->

        <!-- Logo Slider Section -->
        <section class="logo-slider-section">
            <div class="container">
                <div class="logo-slider-wrapper">
                    <div class="logo-slider">
                        <?php
                        // Demo logos - in production these would be actual client logos
                        $demo_logos = array(
                            array('name' => 'Microsoft', 'svg' => '<svg viewBox="0 0 100 100"><rect x="10" y="10" width="35" height="35" fill="#f25022"/><rect x="55" y="10" width="35" height="35" fill="#7fba00"/><rect x="10" y="55" width="35" height="35" fill="#00a4ef"/><rect x="55" y="55" width="35" height="35" fill="#ffb900"/></svg>'),
                            array('name' => 'Google', 'svg' => '<svg viewBox="0 0 100 50"><text x="50" y="35" text-anchor="middle" font-family="Arial" font-size="24" font-weight="500"><tspan fill="#4285f4">G</tspan><tspan fill="#ea4335">o</tspan><tspan fill="#fbbc04">o</tspan><tspan fill="#4285f4">g</tspan><tspan fill="#34a853">l</tspan><tspan fill="#ea4335">e</tspan></text></svg>'),
                            array('name' => 'Amazon', 'svg' => '<svg viewBox="0 0 100 50"><text x="50" y="30" text-anchor="middle" font-family="Arial" font-size="20" font-weight="bold" fill="#232f3e">amazon</text><path d="M20 32 Q50 42 80 32" stroke="#ff9900" stroke-width="3" fill="none"/></svg>'),
                            array('name' => 'Apple', 'svg' => '<svg viewBox="0 0 100 100"><path d="M50 20 C30 20, 20 35, 20 50 C20 70, 35 80, 50 80 C65 80, 80 70, 80 50 C80 35, 70 20, 50 20 M55 10 C55 5, 60 5, 60 10" fill="#555"/></svg>'),
                            array('name' => 'IBM', 'svg' => '<svg viewBox="0 0 100 50"><text x="50" y="35" text-anchor="middle" font-family="Arial" font-size="28" font-weight="bold" fill="#006699">IBM</text></svg>'),
                            array('name' => 'Oracle', 'svg' => '<svg viewBox="0 0 100 50"><text x="50" y="35" text-anchor="middle" font-family="Arial" font-size="20" font-weight="bold" fill="#f80000">ORACLE</text></svg>'),
                            array('name' => 'SAP', 'svg' => '<svg viewBox="0 0 100 50"><rect x="10" y="15" width="80" height="25" rx="12" fill="#0088cc"/><text x="50" y="32" text-anchor="middle" font-family="Arial" font-size="18" font-weight="bold" fill="white">SAP</text></svg>'),
                            array('name' => 'Salesforce', 'svg' => '<svg viewBox="0 0 100 50"><circle cx="50" cy="25" r="20" fill="#00a1e0"/><text x="50" y="32" text-anchor="middle" font-family="Arial" font-size="14" font-weight="bold" fill="white">SF</text></svg>'),
                        );

                        // Display logos twice for seamless scrolling
                        for ($i = 0; $i < 2; $i++) :
                            foreach ($demo_logos as $logo) : ?>
                                <div class="logo-slide">
                                    <div class="logo-item" title="<?php echo esc_attr($logo['name']); ?>">
                                        <?php echo $logo['svg']; ?>
                                    </div>
                                </div>
                            <?php endforeach;
                        endfor; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hotels Section -->
        <section class="hotels-section">
            <div class="container">
                <h2 class="section-title">Entdecken Sie unsere Top-Veranstaltungsorte</h2>
                <div class="hotels-grid">
                    <?php
                    // Hotel data - in production this would come from database or API
                    $hotels = array(
                        array(
                            'image' => 'https://images.seminargo.pro/hotel-83421-4-400x300-FIT_AND_TRIM-f09c5c96e1bc6e5e8f88c37c951bbaa2.webp',
                            'title' => 'Dorint Hotel & Sportresort',
                            'location' => 'Winterberg/Sauerland',
                            'rooms' => '32 Tagungsräume für max. 500',
                            'bedrooms' => '135 Zimmer ab 83,00 EUR',
                            'link' => 'https://finder.dev.seminargo.eu/hotel-83421'
                        ),
                        array(
                            'image' => 'https://images.seminargo.pro/hotel-3111-2-400x300-FIT_AND_TRIM-fea59c5c951cbc623e866c7a87b23e81.webp',
                            'title' => 'Hotel Residence',
                            'location' => 'Bad Griesbach',
                            'rooms' => '6 Tagungsräume für max. 220',
                            'bedrooms' => '100 Zimmer ab 79,00 EUR',
                            'link' => 'https://finder.dev.seminargo.eu/hotel-3111'
                        ),
                        array(
                            'image' => 'https://images.seminargo.pro/hotel-9227-2-400x300-FIT_AND_TRIM-f74f04e91f4e5b0aef5f436b93d09f07.webp',
                            'title' => 'Hotel Esperanto',
                            'location' => 'Fulda',
                            'rooms' => '21 Tagungsräume für max. 1700',
                            'bedrooms' => '265 Zimmer ab 89,00 EUR',
                            'link' => 'https://finder.dev.seminargo.eu/hotel-9227'
                        ),
                        array(
                            'image' => 'https://images.seminargo.pro/hotel-77341-13-400x300-FIT_AND_TRIM-a017c8c1f2e50bc83e82e7b5c438bdf4.webp',
                            'title' => 'Kloster Maria Hilf',
                            'location' => 'Bühl',
                            'rooms' => '8 Tagungsräume für max. 80',
                            'bedrooms' => '37 Zimmer',
                            'link' => 'https://finder.dev.seminargo.eu/hotel-77341'
                        ),
                        array(
                            'image' => 'https://images.seminargo.pro/hotel-70161-20-400x300-FIT_AND_TRIM-3e5c5e5bc951c2c623e86847193d09b4.webp',
                            'title' => 'Kloster Schöntal',
                            'location' => 'Schöntal',
                            'rooms' => '25 Tagungsräume für max. 250',
                            'bedrooms' => '140 Zimmer',
                            'link' => 'https://finder.dev.seminargo.eu/hotel-70161'
                        ),
                        array(
                            'image' => 'https://images.seminargo.pro/hotel-88251-8-400x300-FIT_AND_TRIM-e40c4761df5e8ce93e85f437b93d0961.webp',
                            'title' => 'Hotel Villa Toskana',
                            'location' => 'Leimen',
                            'rooms' => '8 Tagungsräume für max. 100',
                            'bedrooms' => '146 Zimmer ab 99,00 EUR',
                            'link' => 'https://finder.dev.seminargo.eu/hotel-88251'
                        )
                    );

                    foreach ($hotels as $hotel) : ?>
                        <div class="hotel-card">
                            <a href="<?php echo esc_url($hotel['link']); ?>" target="_blank">
                                <div class="hotel-image">
                                    <img src="<?php echo esc_url($hotel['image']); ?>" alt="<?php echo esc_attr($hotel['title']); ?>">
                                </div>
                                <div class="hotel-content">
                                    <h3 class="hotel-title"><?php echo esc_html($hotel['title']); ?></h3>
                                    <p class="hotel-location"><?php echo esc_html($hotel['location']); ?></p>
                                    <div class="hotel-info">
                                        <span class="hotel-rooms"><?php echo esc_html($hotel['rooms']); ?></span>
                                        <?php if (!empty($hotel['bedrooms'])) : ?>
                                            <span class="hotel-bedrooms"><?php echo esc_html($hotel['bedrooms']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Event Types Section -->
        <section class="event-types-section">
            <div class="container">
                <h2 class="section-title">Finden Sie Ihre perfekte Veranstaltungsart</h2>
                <div class="event-types-grid">
                    <?php
                    $event_types = array(
                        array(
                            'title' => 'Seminar',
                            'description' => 'Schulungen und Weiterbildungen',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Seminar & Spa',
                            'description' => 'Lernen mit Wellness kombiniert',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Tagung',
                            'description' => 'Professionelle Meetings',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Veranstaltung',
                            'description' => 'Events und Feiern',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Workshop',
                            'description' => 'Interaktive Workshops',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Konferenz',
                            'description' => 'Große Konferenzen',
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
                            'link' => '#'
                        )
                    );

                    foreach ($event_types as $event_type) : ?>
                        <a href="<?php echo esc_url($event_type['link']); ?>" class="event-type-card">
                            <div class="event-type-icon">
                                <?php echo $event_type['icon']; ?>
                            </div>
                            <h3 class="event-type-title"><?php echo esc_html($event_type['title']); ?></h3>
                            <p class="event-type-description"><?php echo esc_html($event_type['description']); ?></p>
                            <span class="event-type-arrow">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Popular Locations Section -->
        <section class="popular-locations-section">
            <div class="container">
                <h2 class="section-title">Angesagte Locations</h2>
                <div class="locations-grid">
                    <?php
                    $locations = array(
                        array(
                            'title' => 'Design Hotels',
                            'image' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=400&h=300&fit=crop',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Tagungshotels Berlin',
                            'image' => 'https://images.unsplash.com/photo-1560969184-10fe8719e047?w=400&h=300&fit=crop',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Tagungshotels Hamburg',
                            'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400&h=300&fit=crop',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Tagungshotels Frankfurt',
                            'image' => 'https://images.unsplash.com/photo-1467377791767-c929b5dc9a23?w=400&h=300&fit=crop',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Tagungshotels Köln',
                            'image' => 'https://images.unsplash.com/photo-1546412414-e1885259563a?w=400&h=300&fit=crop',
                            'link' => '#'
                        ),
                        array(
                            'title' => 'Tagungshotels München',
                            'image' => 'https://images.unsplash.com/photo-1595521624992-48a59aef95e3?w=400&h=300&fit=crop',
                            'link' => '#'
                        )
                    );

                    foreach ($locations as $location) : ?>
                        <div class="location-card">
                            <a href="<?php echo esc_url($location['link']); ?>">
                                <div class="location-image">
                                    <img src="<?php echo esc_url($location['image']); ?>" alt="<?php echo esc_attr($location['title']); ?>">
                                    <div class="location-overlay">
                                        <h3 class="location-title"><?php echo esc_html($location['title']); ?></h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Location Finder CTA Section -->
        <section class="location-finder-cta">
            <div class="container">
                <div class="cta-content">
                    <div class="cta-text">
                        <h2>Kostenlose Beratung für Ihre perfekte Location</h2>
                        <p>Sparen Sie Zeit und Nerven – unser Expertenteam findet für Sie die ideale Location. Persönliche Beratung, maßgeschneiderte Vorschläge, 100% kostenfrei.</p>
                    </div>
                    <div class="cta-actions">
                        <a href="tel:+4312345678" class="button button-white">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            Jetzt anrufen
                        </a>
                        <a href="mailto:info@seminargo.com" class="button button-outline-white">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            E-Mail senden
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEO Content Section -->
        <section class="seo-content-section">
            <div class="container">
                <div class="seo-content-wrapper">
                    <h2>Ihre digitale Buchungsplattform für Seminarhotels in Österreich und Deutschland</h2>

                    <div class="seo-content-grid">
                        <div class="seo-content-block">
                            <h3>Effiziente Veranstaltungsplanung</h3>
                            <p>Seminargo ist die führende digitale Buchungsplattform, die Veranstaltungsplaner mit über 24.000 Seminarhotels in Österreich und Deutschland verbindet. Unsere innovative Technologie spart Ihnen wertvolle Zeit bei der Suche nach dem perfekten Veranstaltungsort für Konferenzen, Meetings und Firmenevents.</p>
                        </div>

                        <div class="seo-content-block">
                            <h3>Expertise & persönliche Beratung</h3>
                            <p>Unser Expertenteam sorgt dafür, dass Sie präzise Angebote erhalten und das ideale Seminarhotel für Ihre Veranstaltung finden. Von Wien über Graz bis München – wir kennen die besten Locations in Österreich und Deutschland und beraten Sie kostenlos bei Ihrer Auswahl.</p>
                        </div>

                        <div class="seo-content-block">
                            <h3>Umfassende Ressourcen für Ihre Planung</h3>
                            <p>Nutzen Sie unsere praktischen Tools wie Checklisten, E-Books und den interaktiven Quick-Check, um systematisch Ihre perfekte Location zu identifizieren. Mit unseren Ressourcen wird die Eventplanung zum Kinderspiel.</p>
                        </div>
                    </div>

                    <div class="trust-signals">
                        <p class="trust-text">Vertraut von führenden Unternehmen wie Accor Hotels, Austria Trend, Allianz, dm und T-Mobile. Support-Team verfügbar Mo-Do 8-18 Uhr, Fr 8-14 Uhr in unseren Büros in München und Wien.</p>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();