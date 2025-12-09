<?php
/**
 * Template for displaying single hotel posts
 *
 * @package Seminargo
 */

get_header();

// Get hotel data
while ( have_posts() ) : the_post();

    $post_id = get_the_ID();

    // Get all custom fields from API
    $hotel_data = array(
        // Basic info
        'hotel_id'      => get_post_meta( $post_id, 'hotel_id', true ),
        'ref_code'      => get_post_meta( $post_id, 'ref_code', true ),
        'api_slug'      => get_post_meta( $post_id, 'api_slug', true ),
        'shop_url'      => get_post_meta( $post_id, 'shop_url', true ),

        // Location
        'address'       => get_post_meta( $post_id, 'business_address_1', true ),
        'address_2'     => get_post_meta( $post_id, 'business_address_2', true ),
        'zip'           => get_post_meta( $post_id, 'business_zip', true ),
        'city'          => get_post_meta( $post_id, 'business_city', true ),
        'country'       => get_post_meta( $post_id, 'business_country', true ),
        'full_address'  => get_post_meta( $post_id, 'full_address', true ),
        'latitude'      => get_post_meta( $post_id, 'location_latitude', true ),
        'longitude'     => get_post_meta( $post_id, 'location_longitude', true ),
        'distance_airport' => get_post_meta( $post_id, 'distance_to_nearest_airport', true ),
        'distance_train'   => get_post_meta( $post_id, 'distance_to_nearest_railroad_station', true ),

        // Rating & Stars
        'rating'        => floatval( get_post_meta( $post_id, 'rating', true ) ),
        'stars'         => floatval( get_post_meta( $post_id, 'stars', true ) ),

        // Capacity
        'rooms'         => intval( get_post_meta( $post_id, 'rooms', true ) ),
        'capacity'      => intval( get_post_meta( $post_id, 'capacity', true ) ),
        'hotel_rooms'   => intval( get_post_meta( $post_id, 'max_capacity_rooms', true ) ),

        // Texts
        'description'   => get_post_meta( $post_id, 'description', true ) ?: get_the_content(),
        'arrival_car'   => get_post_meta( $post_id, 'arrival_car', true ),
        'arrival_flight'=> get_post_meta( $post_id, 'arrival_flight', true ),
        'arrival_train' => get_post_meta( $post_id, 'arrival_train', true ),

        // Contact
        'email'         => get_post_meta( $post_id, 'business_email', true ),

        // Partner info
        'space_name'    => get_post_meta( $post_id, 'space_name', true ),
        'direct_booking'=> get_post_meta( $post_id, 'direct_booking', true ),
    );

    // Build display location
    $location_parts = array_filter([$hotel_data['city'], $hotel_data['country']]);
    $hotel_data['location'] = !empty($location_parts) ? implode(', ', $location_parts) : $hotel_data['address'];

    // Display address - just use full_address field
    $hotel_data['display_address'] = $hotel_data['full_address'];

    // Get amenities list (categorized)
    $amenities_json = get_post_meta( $post_id, 'amenities_list', true );
    $amenities_list = json_decode( $amenities_json, true ) ?: [];

    // Get meeting rooms
    $meeting_rooms_json = get_post_meta( $post_id, 'meeting_rooms', true );
    $meeting_rooms = json_decode( $meeting_rooms_json, true ) ?: [];

    // Get media JSON for gallery
    $medias_json = get_post_meta( $post_id, 'medias_json', true );
    $medias = json_decode( $medias_json, true ) ?: [];

    // Build gallery images array
    $gallery_images = [];

    // First try to get WordPress attachment gallery
    $wp_gallery = get_post_meta( $post_id, 'gallery', true );
    if ( is_array( $wp_gallery ) && !empty( $wp_gallery ) ) {
        foreach ( $wp_gallery as $attachment_id ) {
            $url = wp_get_attachment_url( $attachment_id );
            if ( $url ) {
                $gallery_images[] = $url;
            }
        }
    }

    // If no WP gallery, use API media URLs
    if ( empty( $gallery_images ) && !empty( $medias ) ) {
        foreach ( $medias as $media ) {
            if ( !empty( $media['previewUrl'] ) ) {
                $gallery_images[] = $media['previewUrl'];
            }
        }
    }

    // Add featured image if exists and not already in gallery
    $featured_image = get_the_post_thumbnail_url( $post_id, 'full' );
    if ( $featured_image && !in_array( $featured_image, $gallery_images ) ) {
        array_unshift( $gallery_images, $featured_image );
    }

    // Fallback image
    if ( empty( $gallery_images ) ) {
        $gallery_images = ['https://placehold.co/800x600/e2e8f0/64748b?text=Hotel'];
    }

    // Amenity translations for display
    $amenity_translations = [
        // Room
        'ROOM_SAFE' => 'Safe im Zimmer',
        'ROOM_AIRCONDITIONER' => 'Klimaanlage',
        'ROOM_BARRIER_FREE' => 'Barrierefreie Zimmer',
        'ROOM_MINIBAR' => 'Minibar',
        'ROOM_BALCONY' => 'Balkon',
        // Design
        'DESIGN_BUSINESS' => 'Business-Hotel',
        'DESIGN_RURAL' => 'Ländlich',
        'DESIGN_MODERN' => 'Modern',
        'DESIGN_TRADITIONAL' => 'Traditionell',
        // Activity
        'ACTIVITY_GOLF' => 'Golf',
        'ACTIVITY_BIKE_RENTAL' => 'Fahrradverleih',
        'ACTIVITY_FITNESS' => 'Fitnessraum',
        'ACTIVITY_TENNIS' => 'Tennis',
        'ACTIVITY_HIGH_ROPES_COURSE' => 'Hochseilgarten',
        'ACTIVITY_TEAM_COOKING' => 'Team-Kochen',
        'ACTIVITY_RAFT_BUILDING' => 'Floßbau',
        'ACTIVITY_RAFTING' => 'Rafting',
        'ACTIVITY_SKI_SLOPE' => 'Skipiste',
        'ACTIVITY_TOBOGGAN_RUN' => 'Rodelbahn',
        // Wellness
        'WELLNESS_OUTDOOR_POOL' => 'Außenpool',
        'WELLNESS_INDOOR_POOL' => 'Innenpool',
        'WELLNESS_MASSAGE' => 'Massage',
        'WELLNESS_THERMAL' => 'Thermalbereich',
        'WELLNESS_WHIRLPOOL' => 'Whirlpool',
        'WELLNESS_INFRARED_CABIN' => 'Infrarotkabine',
        'WELLNESS_SAUNA' => 'Sauna',
        'WELLNESS_STEAM_BATH' => 'Dampfbad',
        // Facility
        'HOTELFACILITY_BARRIER_FREE' => 'Barrierefrei',
        'HOTELFACILITY_ELECTRIC_CHARGING_STATION' => 'E-Ladestation',
        'HOTELFACILITY_GREEN_AREA' => 'Grünanlage',
        // Eco
        'ECOLABEL_AUSTRIAN_ECOLABEL' => 'Österreichisches Umweltzeichen',
        'ECOLABEL_EU_ECOLABEL' => 'EU Ecolabel',
        'ECOLABEL_GREEN_KEY' => 'Green Key',
        'ECOLABEL_GREEN_MEETINGS_AND_EVENTS' => 'Green Meetings',
    ];

?>

<div id="primary" class="content-area hotel-single">
    <main id="main" class="site-main">

        <!-- Main Content Container -->
        <div class="container hotel-content-wrapper">

            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'seminargo' ); ?>">
                <a href="<?php echo esc_url( home_url( '/seminarhotels' ) ); ?>"><?php esc_html_e( 'Seminarhotels', 'seminargo' ); ?></a>
                <span class="separator">/</span>
                <span class="current"><?php echo esc_html( get_the_title() ); ?></span>
            </nav>

            <!-- Two Column Layout: Gallery Left + Content Right -->
            <div class="hotel-layout hotel-layout-two-col">

                <!-- Left Column: Gallery -->
                <div class="hotel-gallery-column">
                    <?php if ( !empty( $gallery_images ) ) : ?>
                        <div class="gallery-static" id="hotel-gallery">
                            <!-- Main Image (Static) -->
                            <div class="gallery-main-image" id="gallery-main-image">
                                <img src="<?php echo esc_url( $gallery_images[0] ); ?>"
                                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                                     id="gallery-current-image"
                                     data-images='<?php echo esc_attr( json_encode( $gallery_images ) ); ?>'>

                                <!-- Image Counter -->
                                <div class="gallery-counter">
                                    <span class="current-slide">1</span> / <span class="total-slides"><?php echo count( $gallery_images ); ?></span>
                                </div>

                                <?php if ( count( $gallery_images ) > 1 ) : ?>
                                <!-- Navigation Arrows -->
                                <button class="gallery-nav gallery-nav-prev" id="gallery-nav-prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', 'seminargo' ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                </button>
                                <button class="gallery-nav gallery-nav-next" id="gallery-nav-next" aria-label="<?php esc_attr_e( 'Nächstes Bild', 'seminargo' ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                                <?php endif; ?>

                                <!-- View All Button -->
                                <button class="gallery-view-all" id="open-lightbox">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="14" width="7" height="7"></rect>
                                        <rect x="3" y="14" width="7" height="7"></rect>
                                    </svg>
                                    <?php esc_html_e( 'Galerie öffnen', 'seminargo' ); ?>
                                </button>
                            </div>

                            <!-- Thumbnail Grid -->
                            <div class="gallery-thumbnails">
                                <?php foreach ( $gallery_images as $index => $image_url ) : ?>
                                    <button class="gallery-thumb-btn <?php echo $index === 0 ? 'active' : ''; ?>"
                                            data-index="<?php echo esc_attr( $index ); ?>"
                                            data-src="<?php echo esc_url( $image_url ); ?>"
                                            aria-label="<?php echo esc_attr( 'Bild ' . ( $index + 1 ) ); ?>">
                                        <img src="<?php echo esc_url( $image_url ); ?>"
                                             alt=""
                                             loading="lazy">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Content -->
                <div class="hotel-content-column">

                    <!-- Hotel Header -->
                    <header class="hotel-header">
                        <div class="hotel-header-top">
                            <div class="hotel-title-area">
                                <h1 class="hotel-title">
                                    <?php the_title(); ?>
                                    <?php if ( $hotel_data['stars'] > 0 ) : ?>
                                        <span class="hotel-stars-text"><?php echo esc_html( $hotel_data['stars'] ); ?>S</span>
                                    <?php endif; ?>
                                </h1>
                                <div class="hotel-address-header">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span><?php echo esc_html( $hotel_data['display_address'] ?: $hotel_data['location'] ); ?></span>
                                </div>
                            </div>

                            <!-- Rating Badge with Stars -->
                            <?php if ( $hotel_data['rating'] > 0 ) : ?>
                                <div class="hotel-stars-badge-large">
                                    <?php
                                    $rating_out_of_10 = $hotel_data['rating'];
                                    $rating_out_of_5 = $rating_out_of_10 / 2;
                                    $full_rating_stars = floor($rating_out_of_5);
                                    $half_rating_star = ($rating_out_of_5 - $full_rating_stars) >= 0.5;
                                    ?>
                                    <span class="stars-display">
                                        <?php
                                        echo str_repeat('★', $full_rating_stars);
                                        if ($half_rating_star) echo '<span class="half-star">★</span>';
                                        ?>
                                    </span>
                                    <span class="rating-display"><?php echo number_format( $rating_out_of_10, 1 ); ?>/10</span>
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
                                    <?php if ( $hotel_data['rooms'] > 0 ) : ?>
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
                                    <?php endif; ?>

                                    <?php if ( $hotel_data['capacity'] > 0 ) : ?>
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
                                    <?php endif; ?>

                                    <?php if ( $hotel_data['hotel_rooms'] > 0 ) : ?>
                                    <div class="key-feature-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M2 4v16"></path>
                                            <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                                            <path d="M2 17h20"></path>
                                            <path d="M6 8v9"></path>
                                        </svg>
                                        <div class="feature-content">
                                            <span class="feature-value"><?php echo esc_html( $hotel_data['hotel_rooms'] ); ?></span>
                                            <span class="feature-label"><?php esc_html_e( 'Zimmer', 'seminargo' ); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </section>

                            <!-- Über das Hotel Section -->
                            <section class="hotel-description">
                                <h2><?php esc_html_e( 'Über das Hotel', 'seminargo' ); ?></h2>
                                <div class="description-content-wrapper">
                                    <div class="description-content" id="hotel-description-content">
                                        <?php
                                        if ( $hotel_data['description'] ) {
                                            echo wpautop( wp_kses_post( $hotel_data['description'] ) );
                                        } else {
                                            the_content();
                                        }
                                        ?>
                                    </div>
                                    <div class="description-fade"></div>
                                    <button type="button" class="btn-read-more" id="btn-read-more-description" aria-expanded="false">
                                        <span class="read-more-text"><?php esc_html_e( 'Mehr lesen', 'seminargo' ); ?></span>
                                        <span class="read-less-text"><?php esc_html_e( 'Weniger anzeigen', 'seminargo' ); ?></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Amenities by Category -->
                                <?php if ( !empty( $amenities_list ) && array_filter( $amenities_list ) ) : ?>
                                <div class="hotel-features-categories">

                                    <?php if ( !empty( $amenities_list['room'] ) ) : ?>
                                    <div class="feature-category">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M2 4v16"></path>
                                                <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                                                <path d="M2 17h20"></path>
                                                <path d="M6 8v9"></path>
                                            </svg>
                                            <?php esc_html_e( 'Zimmerausstattung', 'seminargo' ); ?>
                                        </h3>
                                        <ul class="feature-list">
                                            <?php foreach ( $amenities_list['room'] as $attr ) : ?>
                                                <li>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    <?php echo esc_html( $amenity_translations[$attr] ?? $attr ); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ( !empty( $amenities_list['wellness'] ) ) : ?>
                                    <div class="feature-category">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"></path>
                                            </svg>
                                            <?php esc_html_e( 'Wellness & Spa', 'seminargo' ); ?>
                                        </h3>
                                        <ul class="feature-list">
                                            <?php foreach ( $amenities_list['wellness'] as $attr ) : ?>
                                                <li>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    <?php echo esc_html( $amenity_translations[$attr] ?? $attr ); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ( !empty( $amenities_list['activity'] ) ) : ?>
                                    <div class="feature-category">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
                                            </svg>
                                            <?php esc_html_e( 'Aktivitäten', 'seminargo' ); ?>
                                        </h3>
                                        <ul class="feature-list">
                                            <?php foreach ( $amenities_list['activity'] as $attr ) : ?>
                                                <li>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    <?php echo esc_html( $amenity_translations[$attr] ?? $attr ); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ( !empty( $amenities_list['facility'] ) ) : ?>
                                    <div class="feature-category">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                            </svg>
                                            <?php esc_html_e( 'Hotelausstattung', 'seminargo' ); ?>
                                        </h3>
                                        <ul class="feature-list">
                                            <?php foreach ( $amenities_list['facility'] as $attr ) : ?>
                                                <li>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    <?php echo esc_html( $amenity_translations[$attr] ?? $attr ); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ( !empty( $amenities_list['ecolabel'] ) ) : ?>
                                    <div class="feature-category">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                            </svg>
                                            <?php esc_html_e( 'Umweltzertifikate', 'seminargo' ); ?>
                                        </h3>
                                        <ul class="feature-list">
                                            <?php foreach ( $amenities_list['ecolabel'] as $attr ) : ?>
                                                <li>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="20 6 9 17 4 12"></polyline>
                                                    </svg>
                                                    <?php echo esc_html( $amenity_translations[$attr] ?? $attr ); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                </div>
                                <?php endif; ?>
                            </section>

                            <!-- Meeting Rooms Section - Only show if we have actual capacity data -->
                            <?php
                            // Check if meeting rooms have actual capacity/area data (not just names)
                            $has_room_details = false;
                            if ( !empty( $meeting_rooms ) ) {
                                foreach ( $meeting_rooms as $room ) {
                                    // Require at least area OR one capacity value - not just a name
                                    if ( !empty( $room['area'] ) ||
                                         !empty( $room['capacityTheater'] ) ||
                                         !empty( $room['capacityParlament'] ) ||
                                         !empty( $room['capacityBankett'] ) ||
                                         !empty( $room['capacityUForm'] ) ||
                                         !empty( $room['capacityBlock'] ) ) {
                                        $has_room_details = true;
                                        break;
                                    }
                                }
                            }

                            if ( $has_room_details ) :
                            ?>
                            <section class="hotel-meeting-rooms">
                                <div class="meeting-rooms-card">
                                    <div class="meeting-rooms-header">
                                        <div class="meeting-rooms-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                            </svg>
                                        </div>
                                        <div class="meeting-rooms-title">
                                            <h3><?php esc_html_e( 'Tagungsräume', 'seminargo' ); ?></h3>
                                            <span class="rooms-count"><?php printf( esc_html__( '%d Räume verfügbar', 'seminargo' ), count( $meeting_rooms ) ); ?></span>
                                        </div>
                                    </div>

                                    <?php
                                    $rooms_count = count($meeting_rooms);
                                    $max_visible_rows = 5;
                                    $has_hidden_rows = $rooms_count > $max_visible_rows;
                                    ?>
                                    <div class="meeting-rooms-table-wrapper<?php echo $has_hidden_rows ? ' has-hidden-rows' : ''; ?>" data-max-visible="<?php echo $max_visible_rows; ?>">
                                        <table class="meeting-rooms-table meeting-rooms-table-simple">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e( 'Raum', 'seminargo' ); ?></th>
                                                    <th><?php esc_html_e( 'Fläche', 'seminargo' ); ?></th>
                                                    <th><?php esc_html_e( 'Personen', 'seminargo' ); ?></th>
                                                    <th class="th-details"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ( $meeting_rooms as $index => $room ) :
                                                    $is_hidden = $has_hidden_rows && $index >= $max_visible_rows;
                                                    // Calculate min/max capacity from all seating types
                                                    $capacities = array_filter([
                                                        $room['capacityTheater'] ?? 0,
                                                        $room['capacityParlament'] ?? 0,
                                                        $room['capacityBankett'] ?? 0,
                                                        $room['capacityUForm'] ?? 0,
                                                        $room['capacityBlock'] ?? 0,
                                                    ]);
                                                    $min_cap = !empty($capacities) ? min($capacities) : 0;
                                                    $max_cap = !empty($capacities) ? max($capacities) : 0;
                                                    $has_seating_details = !empty($capacities);
                                                ?>
                                                <tr class="room-row<?php echo $is_hidden ? ' room-row-hidden' : ''; ?>" data-room-index="<?php echo $index; ?>">
                                                    <td class="room-name"><?php echo esc_html( $room['name'] ?? '' ); ?></td>
                                                    <td class="room-area"><?php echo $room['area'] ? esc_html( $room['area'] ) . ' m²' : '-'; ?></td>
                                                    <td class="room-capacity">
                                                        <?php if ( $min_cap && $max_cap ) : ?>
                                                            <?php if ( $min_cap === $max_cap ) : ?>
                                                                <?php echo esc_html( $max_cap ); ?> Pers.
                                                            <?php else : ?>
                                                                <?php echo esc_html( $min_cap ); ?>-<?php echo esc_html( $max_cap ); ?> Pers.
                                                            <?php endif; ?>
                                                        <?php else : ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="room-toggle">
                                                        <?php if ( $has_seating_details ) : ?>
                                                        <button type="button" class="btn-room-details" aria-expanded="false" aria-controls="room-details-<?php echo $index; ?>">
                                                            <?php esc_html_e( 'Details', 'seminargo' ); ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polyline points="6 9 12 15 18 9"></polyline>
                                                            </svg>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php if ( $has_seating_details ) : ?>
                                                <tr class="room-details-row<?php echo $is_hidden ? ' room-row-hidden' : ''; ?>" id="room-details-<?php echo $index; ?>" aria-hidden="true">
                                                    <td colspan="4">
                                                        <div class="room-details-content">
                                                            <p class="details-label"><?php esc_html_e( 'Kapazität nach Bestuhlungsart:', 'seminargo' ); ?></p>
                                                            <div class="seating-grid">
                                                                <?php if ( !empty($room['capacityTheater']) ) : ?>
                                                                <div class="seating-item">
                                                                    <span class="seating-type"><?php esc_html_e( 'Theater', 'seminargo' ); ?></span>
                                                                    <span class="seating-capacity"><?php echo esc_html( $room['capacityTheater'] ); ?> Pers.</span>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if ( !empty($room['capacityParlament']) ) : ?>
                                                                <div class="seating-item">
                                                                    <span class="seating-type"><?php esc_html_e( 'Parlament', 'seminargo' ); ?></span>
                                                                    <span class="seating-capacity"><?php echo esc_html( $room['capacityParlament'] ); ?> Pers.</span>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if ( !empty($room['capacityBankett']) ) : ?>
                                                                <div class="seating-item">
                                                                    <span class="seating-type"><?php esc_html_e( 'Bankett', 'seminargo' ); ?></span>
                                                                    <span class="seating-capacity"><?php echo esc_html( $room['capacityBankett'] ); ?> Pers.</span>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if ( !empty($room['capacityUForm']) ) : ?>
                                                                <div class="seating-item">
                                                                    <span class="seating-type"><?php esc_html_e( 'U-Form', 'seminargo' ); ?></span>
                                                                    <span class="seating-capacity"><?php echo esc_html( $room['capacityUForm'] ); ?> Pers.</span>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if ( !empty($room['capacityBlock']) ) : ?>
                                                                <div class="seating-item">
                                                                    <span class="seating-type"><?php esc_html_e( 'Block', 'seminargo' ); ?></span>
                                                                    <span class="seating-capacity"><?php echo esc_html( $room['capacityBlock'] ); ?> Pers.</span>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php if ( $has_hidden_rows ) : ?>
                                        <button type="button" class="btn-show-more-rooms" id="btn-show-more-rooms" data-expanded="false">
                                            <span class="show-more-text">
                                                <?php printf( esc_html__( 'Alle %d Räume anzeigen', 'seminargo' ), $rooms_count ); ?>
                                            </span>
                                            <span class="show-less-text">
                                                <?php esc_html_e( 'Weniger anzeigen', 'seminargo' ); ?>
                                            </span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6 9 12 15 18 9"></polyline>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                            <?php endif; ?>

                            <!-- Arrival Information -->
                            <?php if ( $hotel_data['arrival_car'] || $hotel_data['arrival_train'] || $hotel_data['arrival_flight'] ) : ?>
                            <section class="hotel-arrival-info">
                                <h2><?php esc_html_e( 'Anreise', 'seminargo' ); ?></h2>

                                <div class="arrival-methods">
                                    <?php if ( $hotel_data['arrival_car'] ) : ?>
                                    <div class="arrival-method">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path>
                                                <circle cx="7" cy="17" r="2"></circle>
                                                <circle cx="17" cy="17" r="2"></circle>
                                            </svg>
                                            <?php esc_html_e( 'Mit dem Auto', 'seminargo' ); ?>
                                        </h3>
                                        <div class="arrival-content"><?php echo wpautop( wp_kses_post( $hotel_data['arrival_car'] ) ); ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ( $hotel_data['arrival_train'] ) : ?>
                                    <div class="arrival-method">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="4" y="3" width="16" height="16" rx="2"></rect>
                                                <path d="M4 11h16"></path>
                                                <path d="M12 3v8"></path>
                                                <circle cx="8" cy="15" r="1"></circle>
                                                <circle cx="16" cy="15" r="1"></circle>
                                                <path d="M8 19l-2 3"></path>
                                                <path d="M16 19l2 3"></path>
                                            </svg>
                                            <?php esc_html_e( 'Mit der Bahn', 'seminargo' ); ?>
                                        </h3>
                                        <div class="arrival-content"><?php echo wpautop( wp_kses_post( $hotel_data['arrival_train'] ) ); ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ( $hotel_data['arrival_flight'] ) : ?>
                                    <div class="arrival-method">
                                        <h3>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>
                                            </svg>
                                            <?php esc_html_e( 'Mit dem Flugzeug', 'seminargo' ); ?>
                                        </h3>
                                        <div class="arrival-content"><?php echo wpautop( wp_kses_post( $hotel_data['arrival_flight'] ) ); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                            <?php endif; ?>

                        </div>

                        <!-- Sidebar -->
                        <aside class="hotel-sidebar">

                            <!-- Map -->
                            <?php
                            $lat = $hotel_data['latitude'] ?: 51.1657;
                            $lng = $hotel_data['longitude'] ?: 10.4515;
                            ?>
                            <div class="hotel-map-card">
                                <div class="map-card-header">
                                    <div class="map-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                    </div>
                                    <div class="map-header-text">
                                        <h3 class="map-title"><?php esc_html_e( 'Standort', 'seminargo' ); ?></h3>
                                        <?php if ( !empty($hotel_data['display_address']) ) : ?>
                                        <p class="map-address"><?php echo esc_html( $hotel_data['display_address'] ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <a href="https://www.google.com/maps?q=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>" target="_blank" class="btn-google-maps">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                            <polyline points="15 3 21 3 21 9"></polyline>
                                            <line x1="10" y1="14" x2="21" y2="3"></line>
                                        </svg>
                                        <?php esc_html_e( 'Google Maps', 'seminargo' ); ?>
                                    </a>
                                </div>

                                <?php if ( $hotel_data['distance_airport'] || $hotel_data['distance_train'] ) : ?>
                                <div class="map-distances">
                                    <?php if ( $hotel_data['distance_airport'] ) : ?>
                                    <div class="distance-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>
                                        </svg>
                                        <span class="distance-value"><?php echo round( $hotel_data['distance_airport'], 1 ); ?> km</span>
                                        <span class="distance-label"><?php esc_html_e( 'zum Flughafen', 'seminargo' ); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ( $hotel_data['distance_train'] ) : ?>
                                    <div class="distance-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="4" y="3" width="16" height="16" rx="2"></rect>
                                            <path d="M4 11h16"></path>
                                            <path d="M12 3v8"></path>
                                            <path d="M8 19l-2 3"></path>
                                            <path d="M16 19l2 3"></path>
                                        </svg>
                                        <span class="distance-value"><?php echo round( $hotel_data['distance_train'], 1 ); ?> km</span>
                                        <span class="distance-label"><?php esc_html_e( 'zum Bahnhof', 'seminargo' ); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="map-container">
                                    <div id="hotel-map"
                                         data-lat="<?php echo esc_attr( $lat ); ?>"
                                         data-lng="<?php echo esc_attr( $lng ); ?>"
                                         data-name="<?php echo esc_attr( get_the_title() ); ?>"></div>
                                </div>
                            </div>

                            <!-- Info Card -->
                            <?php
                            // Build Finder URL with hotel parameter
                            $finder_url = 'https://finder.dev.seminargo.eu/';
                            if ( !empty( $hotel_data['api_slug'] ) ) {
                                // Use slug parameter (preferred by client)
                                $finder_url .= '?addHotelBySlug=' . urlencode( $hotel_data['api_slug'] );
                            } elseif ( !empty( $hotel_data['ref_code'] ) ) {
                                // Fallback to refCode if slug not available
                                $finder_url .= '?addHotelByRefCode=' . urlencode( $hotel_data['ref_code'] );
                            }

                            if ( !empty( $hotel_data['api_slug'] ) || !empty( $hotel_data['ref_code'] ) ) :
                            ?>
                            <div class="booking-card sticky" id="booking-form-section">
                                <div class="booking-card-header">
                                    <div class="booking-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M12 16v-4"></path>
                                            <path d="M12 8h.01"></path>
                                        </svg>
                                    </div>
                                    <div class="booking-header-text">
                                        <h3 class="booking-title"><?php esc_html_e( 'Interesse an diesem Hotel?', 'seminargo' ); ?></h3>
                                        <p class="booking-subtitle"><?php esc_html_e( 'Alle Details, Preise & Verfügbarkeit', 'seminargo' ); ?></p>
                                    </div>
                                </div>

                                <div class="booking-card-body">
                                    <div class="info-card-content">
                                        <p class="info-card-text"><?php esc_html_e( 'Erhalten Sie weitere Informationen zu diesem Hotel, aktuelle Preise und prüfen Sie die Verfügbarkeit für Ihren Wunschtermin.', 'seminargo' ); ?></p>

                                        <a href="<?php echo esc_url( $finder_url ); ?>" target="_blank" rel="noopener" class="btn-booking btn-info-link">
                                            <?php esc_html_e( 'Mehr erfahren', 'seminargo' ); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                                <polyline points="15 3 21 3 21 9"></polyline>
                                                <line x1="10" y1="14" x2="21" y2="3"></line>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </aside>

                    </div>

                </div>

            </div>

        </div>

        <!-- Lightbox Overlay -->
        <?php if ( !empty( $gallery_images ) ) : ?>
        <div class="gallery-lightbox" id="gallery-lightbox" aria-hidden="true">
            <div class="lightbox-overlay"></div>
            <div class="lightbox-content">
                <button class="lightbox-close" aria-label="<?php esc_attr_e( 'Schließen', 'seminargo' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <div class="lightbox-image-container">
                    <img src="" alt="" id="lightbox-image">
                </div>
                <button class="lightbox-nav lightbox-prev" aria-label="<?php esc_attr_e( 'Vorheriges Bild', 'seminargo' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button class="lightbox-nav lightbox-next" aria-label="<?php esc_attr_e( 'Nächstes Bild', 'seminargo' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <div class="lightbox-counter">
                    <span class="current-slide">1</span> / <span class="total-slides"><?php echo count( $gallery_images ); ?></span>
                </div>

                <!-- Thumbnail Strip in Lightbox -->
                <div class="lightbox-thumbnails">
                    <?php foreach ( $gallery_images as $index => $image_url ) : ?>
                        <button class="lightbox-thumb-btn <?php echo $index === 0 ? 'active' : ''; ?>"
                                data-index="<?php echo esc_attr( $index ); ?>">
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Ähnliche Hotels Section -->
        <?php
        $current_hotel_id = get_the_ID();
        $current_city = $hotel_data['city'];
        $current_country = $hotel_data['country'];
        $current_stars = $hotel_data['stars'];

        $similar_args = array(
            'post_type'      => 'hotel',
            'post_status'    => 'publish',
            'posts_per_page' => 4,
            'post__not_in'   => array( $current_hotel_id ),
            'orderby'        => 'rand',
        );

        // Try to find hotels in same city/country
        if ( $current_city || $current_country ) {
            $similar_args['meta_query'] = array(
                'relation' => 'OR',
            );
            if ( $current_city ) {
                $similar_args['meta_query'][] = array(
                    'key'     => 'business_city',
                    'value'   => $current_city,
                    'compare' => 'LIKE',
                );
            }
            if ( $current_country ) {
                $similar_args['meta_query'][] = array(
                    'key'     => 'business_country',
                    'value'   => $current_country,
                    'compare' => '=',
                );
            }
        }

        $similar_hotels = new WP_Query( $similar_args );

        // Fallback: if not enough similar hotels found, get any random hotels
        if ( $similar_hotels->post_count < 4 ) {
            $similar_args = array(
                'post_type'      => 'hotel',
                'post_status'    => 'publish',
                'posts_per_page' => 4,
                'post__not_in'   => array( $current_hotel_id ),
                'orderby'        => 'rand',
            );
            $similar_hotels = new WP_Query( $similar_args );
        }

        if ( $similar_hotels->have_posts() ) :
        ?>
        <section class="similar-hotels-section">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline"><?php esc_html_e( 'Weitere Optionen', 'seminargo' ); ?></span>
                    <h2 class="section-title"><?php esc_html_e( 'Ähnliche Hotels', 'seminargo' ); ?></h2>
                </div>
                <div class="similar-hotels-grid">
                    <?php
                    while ( $similar_hotels->have_posts() ) : $similar_hotels->the_post();
                        $similar_id = get_the_ID();

                        // Get featured image
                        $similar_image = get_the_post_thumbnail_url( $similar_id, 'large' );
                        if ( ! $similar_image ) {
                            $similar_medias = json_decode( get_post_meta( $similar_id, 'medias_json', true ), true );
                            if ( !empty( $similar_medias[0]['previewUrl'] ) ) {
                                $similar_image = $similar_medias[0]['previewUrl'];
                            } else {
                                $similar_image = 'https://placehold.co/400x300/e2e8f0/64748b?text=Hotel';
                            }
                        }

                        // Build proper location string - prioritize city, then address
                        $sim_city = get_post_meta( $similar_id, 'business_city', true );
                        $sim_address = get_post_meta( $similar_id, 'business_address_1', true );

                        // Use city if available, otherwise extract location from address
                        if ( !empty( $sim_city ) ) {
                            $sim_location = $sim_city;
                        } elseif ( !empty( $sim_address ) ) {
                            // Try to extract city from address (usually after ZIP code)
                            $sim_location = $sim_address;
                        } else {
                            $sim_location = '';
                        }

                        $similar_hotel_data = array(
                            'id'        => $similar_id,
                            'title'     => get_the_title(),
                            'link'      => get_permalink(),
                            'image'     => $similar_image,
                            'location'  => $sim_location,
                            'stars'     => floatval( get_post_meta( $similar_id, 'stars', true ) ),
                            'rating'    => floatval( get_post_meta( $similar_id, 'rating', true ) ),
                            'rooms'     => intval( get_post_meta( $similar_id, 'rooms', true ) ),
                            'capacity'  => intval( get_post_meta( $similar_id, 'capacity', true ) ),
                        );

                        get_template_part( 'template-parts/hotel-card', null, $similar_hotel_data );
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
                <div class="section-footer">
                    <a href="<?php echo esc_url( home_url( '/seminarhotels' ) ); ?>" class="btn-view-all">
                        <?php esc_html_e( 'Alle Hotels ansehen', 'seminargo' ); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Call to Action Section -->
        <section class="hotel-cta">
            <div class="container">
                <div class="cta-content">
                    <div class="cta-text">
                        <h2><?php esc_html_e( 'Noch nicht das Richtige gefunden?', 'seminargo' ); ?></h2>
                        <p><?php esc_html_e( 'Entdecken Sie weitere Seminarhotels in Deutschland und Österreich', 'seminargo' ); ?></p>
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
