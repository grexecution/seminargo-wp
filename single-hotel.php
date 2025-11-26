<?php
/**
 * Template for displaying single hotel posts
 *
 * @package Seminargo
 */

get_header();

// Get hotel data
while ( have_posts() ) : the_post();

    // Get custom fields with fallbacks
    $hotel_data = array(
        'location'      => get_post_meta( get_the_ID(), 'location', true ) ?: 'Deutschland',
        'rating'        => floatval( get_post_meta( get_the_ID(), 'rating', true ) ) ?: 4.5,
        'reviews'       => intval( get_post_meta( get_the_ID(), 'reviews', true ) ) ?: 150,
        'stars'         => intval( get_post_meta( get_the_ID(), 'stars', true ) ) ?: 4,
        'rooms'         => intval( get_post_meta( get_the_ID(), 'rooms', true ) ) ?: 15,
        'capacity'      => intval( get_post_meta( get_the_ID(), 'capacity', true ) ) ?: 200,
        'bedrooms'      => intval( get_post_meta( get_the_ID(), 'bedrooms', true ) ) ?: 80,
        'price'         => floatval( get_post_meta( get_the_ID(), 'price', true ) ) ?: 0,
        'amenities'     => get_post_meta( get_the_ID(), 'amenities', true ) ?: array( 'WLAN', 'Parkplatz', 'Restaurant' ),
        'features'      => get_post_meta( get_the_ID(), 'features', true ) ?: array( 'Tagungsräume', 'Catering-Service' ),
        'address'       => get_post_meta( get_the_ID(), 'business_address_1', true ) ?: get_post_meta( get_the_ID(), 'address', true ) ?: '',
        'phone'         => get_post_meta( get_the_ID(), 'phone', true ) ?: '',
        'email'         => get_post_meta( get_the_ID(), 'email', true ) ?: '',
        'website'       => get_post_meta( get_the_ID(), 'website', true ) ?: '',
        'description'   => get_post_meta( get_the_ID(), 'description', true ) ?: get_the_content(),
        'featured'      => (bool) get_post_meta( get_the_ID(), 'featured', true ),
        'check_in'      => get_post_meta( get_the_ID(), 'check_in', true ) ?: '14:00',
        'check_out'     => get_post_meta( get_the_ID(), 'check_out', true ) ?: '11:00',
        'parking'       => get_post_meta( get_the_ID(), 'parking', true ) ?: 'Kostenlose Parkplätze verfügbar',
        'cancellation'  => get_post_meta( get_the_ID(), 'cancellation', true ) ?: 'Kostenlose Stornierung bis 48h vor Anreise',
    );

    // Get featured image
    $featured_image = get_the_post_thumbnail_url( get_the_ID(), 'full' );
    if ( !$featured_image ) {
        $featured_image = 'https://images.seminargo.pro/hotel-83421-4-400x300-FIT_AND_TRIM-f09c5c96e1bc6e5e8f88c37c951bbaa2.webp';
    }

    // Get gallery images
    $gallery_images = get_post_meta( get_the_ID(), 'gallery', true );
    if ( !is_array( $gallery_images ) || empty( $gallery_images ) ) {
        $gallery_images = array(
            'https://images.seminargo.pro/hotel-83421-4-400x300-FIT_AND_TRIM-f09c5c96e1bc6e5e8f88c37c951bbaa2.webp',
            'https://images.seminargo.pro/hotel-3111-2-400x300-FIT_AND_TRIM-fea59c5c951cbc623e866c7a87b23e81.webp',
            'https://images.seminargo.pro/hotel-9227-2-400x300-FIT_AND_TRIM-f74f04e91f4e5b0aef5f436b93d09f07.webp',
            'https://images.seminargo.pro/hotel-10037-8-400x300-FIT_AND_TRIM-b7e7fd05a15fa17d0ad2dc5d85502f6d.webp',
        );
    }

    // FAQs
    $faqs = get_post_meta( get_the_ID(), 'faqs', true );
    if ( !is_array( $faqs ) || empty( $faqs ) ) {
        $faqs = array(
            array(
                'question' => 'Wie kann ich das Seminarhotel buchen?',
                'answer' => 'Sie können eine unverbindliche Anfrage über unser Kontaktformular senden. Wir melden uns innerhalb von 24 Stunden mit einem maßgeschneiderten Angebot bei Ihnen.'
            ),
            array(
                'question' => 'Welche Tagungstechnik ist verfügbar?',
                'answer' => 'Alle Tagungsräume sind mit moderner Konferenztechnik ausgestattet: Beamer, Leinwand, Flipcharts, Moderationskoffer, WLAN und auf Wunsch Videokonferenzsysteme.'
            ),
            array(
                'question' => 'Gibt es Verpflegungsmöglichkeiten?',
                'answer' => 'Ja, wir bieten verschiedene Catering-Pakete an - von Kaffee & Snacks über Business-Lunch bis hin zu mehrgängigen Menüs. Vegetarische und vegane Optionen sind verfügbar.'
            ),
            array(
                'question' => 'Ist das Hotel barrierefrei?',
                'answer' => 'Ja, unser Hotel verfügt über barrierefreie Zimmer und Tagungsräume sowie einen Aufzug. Bitte teilen Sie uns besondere Bedürfnisse bei der Buchung mit.'
            ),
            array(
                'question' => 'Welche Stornierungsbedingungen gelten?',
                'answer' => $hotel_data['cancellation']
            ),
        );
    }
?>

<div id="primary" class="content-area hotel-single">
    <main id="main" class="site-main">

        <!-- Main Content Container -->
        <div class="container hotel-content-wrapper">

            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'seminargo' ); ?>">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Startseite', 'seminargo' ); ?></a>
                <span class="separator">/</span>
                <a href="<?php echo esc_url( home_url( '/seminarhotels' ) ); ?>"><?php esc_html_e( 'Seminarhotels', 'seminargo' ); ?></a>
                <span class="separator">/</span>
                <span class="current"><?php echo esc_html( get_the_title() ); ?></span>
            </nav>

            <!-- Hotel Layout: Left Column (Images) + Right Column (Content + Sidebar) -->
            <div class="hotel-layout">

                <!-- Left Column: Images -->
                <div class="hotel-images-column">

                    <!-- Main Image -->
                    <div class="hotel-main-image">
                        <img src="<?php echo esc_url( $featured_image ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">

                        <!-- Featured Badge -->
                        <?php if ( $hotel_data['featured'] ) : ?>
                            <div class="hotel-featured-badge-large">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                <?php esc_html_e( 'Top', 'seminargo' ); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Wishlist Button -->
                        <button class="btn-wishlist" aria-label="<?php esc_attr_e( 'Zur Merkliste hinzufügen', 'seminargo' ); ?>" data-hotel-id="<?php echo esc_attr( get_the_ID() ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Gallery Thumbnails -->
                    <?php if ( !empty( $gallery_images ) && count( $gallery_images ) > 1 ) : ?>
                        <div class="hotel-gallery-grid">
                            <?php foreach ( array_slice( $gallery_images, 0, 4 ) as $index => $image_url ) : ?>
                                <div class="gallery-thumb">
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() . ' - Bild ' . ( $index + 1 ) ); ?>">
                                    <?php if ( $index === 3 && count( $gallery_images ) > 4 ) : ?>
                                        <div class="gallery-more-overlay">
                                            <span>+<?php echo count( $gallery_images ) - 4; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Booking Card (Mobile) -->
                    <div class="booking-card booking-card-mobile">
                        <div class="booking-card-header">
                            <div class="price-info-large">
                                <span class="price-label"><?php esc_html_e( 'Preis', 'seminargo' ); ?></span>
                                <span class="price-amount"><?php esc_html_e( 'auf Anfrage', 'seminargo' ); ?></span>
                            </div>
                        </div>
                        <div class="booking-card-body">
                            <a href="#booking-form-section" class="btn-booking">
                                <?php esc_html_e( 'Unverbindliche Anfrage', 'seminargo' ); ?>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Content + Sidebar -->
                <div class="hotel-content-column">

                    <!-- Hotel Header -->
                    <header class="hotel-header">
                        <div class="hotel-header-top">
                            <div class="hotel-title-area">
                                <h1 class="hotel-title"><?php the_title(); ?></h1>
                                <div class="hotel-address-header">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span><?php echo esc_html( $hotel_data['address'] ? $hotel_data['address'] : $hotel_data['location'] ); ?></span>
                                </div>
                            </div>

                            <!-- Stars Badge with Rating -->
                            <?php if ( !empty( $hotel_data['stars'] ) || !empty( $hotel_data['rating'] ) ) : ?>
                                <div class="hotel-stars-badge-large">
                                    <?php if ( !empty( $hotel_data['stars'] ) ) : ?>
                                        <span class="stars-display"><?php echo str_repeat( '★', $hotel_data['stars'] ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( !empty( $hotel_data['rating'] ) ) : ?>
                                        <span class="rating-display"><?php echo number_format( $hotel_data['rating'], 1 ); ?>/10</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </header>

                    <!-- Two Column Layout: Main Content + Sidebar -->
                    <div class="hotel-inner-layout">

                        <!-- Main Content -->
                        <div class="hotel-main-content">

                            <!-- Key Features Grid -->
                            <section class="hotel-key-features">
                                <div class="key-features-grid">
                                    <div class="key-feature-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                        </svg>
                                        <div class="feature-content">
                                            <span class="feature-value"><?php echo esc_html( $hotel_data['rooms'] ); ?></span>
                                            <span class="feature-label"><?php esc_html_e( 'Tagungsräume', 'seminargo' ); ?></span>
                                        </div>
                                    </div>

                                    <div class="key-feature-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                        <div class="feature-content">
                                            <span class="feature-value"><?php echo esc_html( $hotel_data['capacity'] ); ?></span>
                                            <span class="feature-label"><?php esc_html_e( 'max. Personen', 'seminargo' ); ?></span>
                                        </div>
                                    </div>

                                    <div class="key-feature-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M2 4v16"></path>
                                            <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                                            <path d="M2 17h20"></path>
                                            <path d="M6 8v9"></path>
                                        </svg>
                                        <div class="feature-content">
                                            <span class="feature-value"><?php echo esc_html( $hotel_data['bedrooms'] ); ?></span>
                                            <span class="feature-label"><?php esc_html_e( 'Zimmer', 'seminargo' ); ?></span>
                                        </div>
                                    </div>

                                    <div class="key-feature-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <div class="feature-content">
                                            <span class="feature-value"><?php echo esc_html( $hotel_data['check_in'] ); ?></span>
                                            <span class="feature-label"><?php esc_html_e( 'Check-in', 'seminargo' ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Description -->
                            <section class="hotel-description">
                                <h2><?php esc_html_e( 'Über das Hotel', 'seminargo' ); ?></h2>
                                <div class="description-content">
                                    <?php
                                    if ( $hotel_data['description'] ) {
                                        echo wpautop( wp_kses_post( $hotel_data['description'] ) );
                                    } else {
                                        the_content();
                                    }
                                    ?>
                                </div>
                            </section>

                            <!-- Highlights from Attributes -->
                            <?php
                            // Get attributes from custom field
                            $attributes_json = get_post_meta( get_the_ID(), 'attributes', true );
                            $attributes = array();

                            if ( !empty( $attributes_json ) ) {
                                $decoded = json_decode( $attributes_json, true );
                                if ( is_array( $decoded ) ) {
                                    foreach ( $decoded as $attr ) {
                                        if ( isset( $attr['attribute'] ) ) {
                                            $attributes[] = $attr['attribute'];
                                        }
                                    }
                                }
                            }

                            // Attribute labels mapping
                            $attribute_labels = array(
                                'CATEGORY_FIVE_STARS' => '5 Sterne Hotel',
                                'CATEGORY_FOUR_STARS' => '4 Sterne Hotel',
                                'CATEGORY_THREE_STARS' => '3 Sterne Hotel',
                                'DESIGN_BUSINESS' => 'Business Hotel',
                                'DESIGN_WELLNESS' => 'Wellness Hotel',
                                'DESIGN_CONFERENCE' => 'Tagungshotel',
                                'ROOM_SAFE' => 'Safe im Zimmer',
                                'ROOM_AIRCONDITIONER' => 'Klimaanlage',
                                'ROOM_MINIBAR' => 'Minibar',
                                'ROOM_BALCONY' => 'Balkon',
                                'ACTIVITY_FITNESS' => 'Fitnessraum',
                                'ACTIVITY_POOL' => 'Pool',
                                'ACTIVITY_SPA' => 'Spa & Wellness',
                                'ACTIVITY_SAUNA' => 'Sauna',
                                'FACILITY_RESTAURANT' => 'Restaurant',
                                'FACILITY_BAR' => 'Bar',
                                'FACILITY_PARKING' => 'Parkplatz',
                                'FACILITY_WIFI' => 'WLAN',
                                'FACILITY_ELEVATOR' => 'Aufzug',
                                'FACILITY_ACCESSIBLE' => 'Barrierefrei',
                            );

                            if ( !empty( $attributes ) ) :
                            ?>
                            <section class="hotel-amenities-features">
                                <div class="amenities-features-grid">
                                    <div class="features-section">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                            </svg>
                                            <?php esc_html_e( 'Highlights', 'seminargo' ); ?>
                                        </h3>
                                        <ul class="features-list">
                                            <?php foreach ( $attributes as $attr ) : ?>
                                                <?php if ( isset( $attribute_labels[ $attr ] ) ) : ?>
                                                    <li>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="20 6 9 17 4 12"></polyline>
                                                        </svg>
                                                        <?php echo esc_html( $attribute_labels[ $attr ] ); ?>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </section>
                            <?php endif; ?>

                            <!-- FAQs -->
                            <?php if ( !empty( $faqs ) ) : ?>
                                <section class="hotel-faqs">
                                    <h2><?php esc_html_e( 'Häufig gestellte Fragen', 'seminargo' ); ?></h2>
                                    <div class="faqs-list">
                                        <?php foreach ( $faqs as $index => $faq ) : ?>
                                            <div class="faq-item">
                                                <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-<?php echo $index; ?>">
                                                    <span><?php echo esc_html( $faq['question'] ); ?></span>
                                                    <svg class="faq-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="6 9 12 15 18 9"></polyline>
                                                    </svg>
                                                </button>
                                                <div class="faq-answer" id="faq-answer-<?php echo $index; ?>">
                                                    <p><?php echo esc_html( $faq['answer'] ); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>

                        </div>

                        <!-- Sidebar -->
                        <aside class="hotel-sidebar">

                            <!-- Booking Card -->
                            <div class="booking-card sticky" id="booking-form-section">
                                <div class="booking-card-header">
                                    <div class="price-info-large">
                                        <span class="price-label"><?php esc_html_e( 'Preis', 'seminargo' ); ?></span>
                                        <span class="price-amount"><?php esc_html_e( 'auf Anfrage', 'seminargo' ); ?></span>
                                    </div>
                                </div>

                                <div class="booking-card-body">
                                    <form class="booking-form" id="hotel-booking-form">
                                        <div class="form-group">
                                            <label for="booking-name"><?php esc_html_e( 'Ihr Name', 'seminargo' ); ?> *</label>
                                            <input type="text" id="booking-name" name="name" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="booking-email"><?php esc_html_e( 'E-Mail', 'seminargo' ); ?> *</label>
                                            <input type="email" id="booking-email" name="email" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="booking-phone"><?php esc_html_e( 'Telefon', 'seminargo' ); ?></label>
                                            <input type="tel" id="booking-phone" name="phone">
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="booking-checkin"><?php esc_html_e( 'Anreise', 'seminargo' ); ?> *</label>
                                                <input type="date" id="booking-checkin" name="checkin" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="booking-checkout"><?php esc_html_e( 'Abreise', 'seminargo' ); ?> *</label>
                                                <input type="date" id="booking-checkout" name="checkout" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="booking-guests"><?php esc_html_e( 'Anzahl Personen', 'seminargo' ); ?> *</label>
                                            <input type="number" id="booking-guests" name="guests" min="1" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="booking-message"><?php esc_html_e( 'Ihre Nachricht', 'seminargo' ); ?></label>
                                            <textarea id="booking-message" name="message" rows="3"></textarea>
                                        </div>

                                        <button type="submit" class="btn-booking">
                                            <?php esc_html_e( 'Unverbindliche Anfrage senden', 'seminargo' ); ?>
                                        </button>

                                        <p class="booking-note"><?php esc_html_e( 'Wir melden uns innerhalb von 24 Stunden', 'seminargo' ); ?></p>
                                    </form>
                                </div>
                            </div>

                            <!-- Map -->
                            <?php
                            $latitude = get_post_meta( get_the_ID(), 'latitude', true );
                            $longitude = get_post_meta( get_the_ID(), 'longitude', true );

                            // Fallback coordinates (Germany center) if not available
                            if ( empty( $latitude ) || empty( $longitude ) ) {
                                $latitude = 51.1657;
                                $longitude = 10.4515;
                            }
                            ?>
                            <div class="hotel-map-card">
                                <h3><?php esc_html_e( 'Standort', 'seminargo' ); ?></h3>
                                <div id="hotel-map"
                                     data-lat="<?php echo esc_attr( $latitude ); ?>"
                                     data-lng="<?php echo esc_attr( $longitude ); ?>"
                                     data-name="<?php echo esc_attr( get_the_title() ); ?>"></div>
                            </div>


                        </aside>

                    </div>

                </div>

            </div>

        </div>

        <!-- Call to Action Section -->
        <section class="hotel-cta">
            <div class="container">
                <div class="cta-content">
                    <div class="cta-text">
                        <h2><?php esc_html_e( 'Noch nicht das Richtige gefunden?', 'seminargo' ); ?></h2>
                        <p><?php esc_html_e( 'Entdecken Sie über 800 weitere Seminarhotels in Deutschland und Österreich', 'seminargo' ); ?></p>
                    </div>
                    <div class="cta-actions">
                        <a href="<?php echo esc_url( home_url( '/seminarhotels' ) ); ?>" class="btn-cta-primary">
                            <?php esc_html_e( 'Alle Hotels durchsuchen', 'seminargo' ); ?>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/kontakt' ) ); ?>" class="btn-cta-secondary">
                            <?php esc_html_e( 'Persönliche Beratung', 'seminargo' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<?php
endwhile;
get_footer();
?>
