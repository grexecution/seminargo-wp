<?php
/**
 * Template Name: Seminarhotels Archive
 * Template for displaying searchable seminarhotels archive
 *
 * @package Seminargo
 */

get_header();
?>

<div id="primary" class="content-area seminarhotels-archive">
    <main id="main" class="site-main">

        <!-- Hero Search Section -->
        <section class="seminarhotels-hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="page-title"><?php esc_html_e( 'Finden Sie Ihr perfektes Seminarhotel', 'seminargo' ); ?></h1>
                    <p class="page-subtitle"><?php esc_html_e( 'Über 24.000 Seminarhotels in Deutschland und Österreich', 'seminargo' ); ?></p>
                </div>

                <!-- Advanced Search Filters -->
                <div class="search-filters-wrapper">
                    <form id="hotel-search-form" class="hotel-search-form" method="GET">

                        <!-- Main Search Bar -->
                        <div class="search-bar-main">
                            <div class="search-field search-location">
                                <label for="search-location">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </label>
                                <input type="text" id="search-location" name="location" placeholder="Wo? (Ort, Region oder PLZ)" value="<?php echo esc_attr( isset( $_GET['location'] ) ? $_GET['location'] : '' ); ?>">
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
                                <input type="number" id="search-capacity" name="capacity" placeholder="Personen" min="1" value="<?php echo esc_attr( isset( $_GET['capacity'] ) ? $_GET['capacity'] : '' ); ?>">
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
                                <input type="date" id="search-date" name="date" value="<?php echo esc_attr( isset( $_GET['date'] ) ? $_GET['date'] : '' ); ?>">
                            </div>

                            <button type="button" class="toggle-filters" id="toggle-filters" aria-label="<?php esc_attr_e( 'Weitere Filter', 'seminargo' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                </svg>
                            </button>

                            <button type="submit" class="btn-search-submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                <span><?php esc_html_e( 'Suchen', 'seminargo' ); ?></span>
                            </button>
                        </div>

                        <!-- Advanced Filters Panel -->
                        <div class="advanced-filters" id="advanced-filters">
                            <div class="filters-grid">

                                <!-- Price Range -->
                                <div class="filter-group">
                                    <label><?php esc_html_e( 'Preis pro Person/Nacht', 'seminargo' ); ?></label>
                                    <div class="price-range-inputs">
                                        <input type="number" name="price_min" placeholder="Von €" value="<?php echo esc_attr( isset( $_GET['price_min'] ) ? $_GET['price_min'] : '' ); ?>">
                                        <span>-</span>
                                        <input type="number" name="price_max" placeholder="Bis €" value="<?php echo esc_attr( isset( $_GET['price_max'] ) ? $_GET['price_max'] : '' ); ?>">
                                    </div>
                                </div>

                                <!-- Stars Rating -->
                                <div class="filter-group">
                                    <label><?php esc_html_e( 'Sterne', 'seminargo' ); ?></label>
                                    <div class="checkbox-group">
                                        <?php for ( $i = 5; $i >= 3; $i-- ) : ?>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="stars[]" value="<?php echo $i; ?>" <?php echo ( isset( $_GET['stars'] ) && in_array( $i, (array) $_GET['stars'] ) ) ? 'checked' : ''; ?>>
                                                <span><?php echo str_repeat( '★', $i ); ?></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <!-- Event Type -->
                                <div class="filter-group">
                                    <label><?php esc_html_e( 'Veranstaltungsart', 'seminargo' ); ?></label>
                                    <select name="event_type" class="filter-select">
                                        <option value=""><?php esc_html_e( 'Alle Arten', 'seminargo' ); ?></option>
                                        <option value="seminar" <?php selected( isset( $_GET['event_type'] ) ? $_GET['event_type'] : '', 'seminar' ); ?>><?php esc_html_e( 'Seminar', 'seminargo' ); ?></option>
                                        <option value="tagung" <?php selected( isset( $_GET['event_type'] ) ? $_GET['event_type'] : '', 'tagung' ); ?>><?php esc_html_e( 'Tagung', 'seminargo' ); ?></option>
                                        <option value="konferenz" <?php selected( isset( $_GET['event_type'] ) ? $_GET['event_type'] : '', 'konferenz' ); ?>><?php esc_html_e( 'Konferenz', 'seminargo' ); ?></option>
                                        <option value="workshop" <?php selected( isset( $_GET['event_type'] ) ? $_GET['event_type'] : '', 'workshop' ); ?>><?php esc_html_e( 'Workshop', 'seminargo' ); ?></option>
                                    </select>
                                </div>

                                <!-- Room Configuration -->
                                <div class="filter-group">
                                    <label><?php esc_html_e( 'Raumbestuhlung', 'seminargo' ); ?></label>
                                    <select name="seating" class="filter-select">
                                        <option value=""><?php esc_html_e( 'Alle', 'seminargo' ); ?></option>
                                        <option value="theater" <?php selected( isset( $_GET['seating'] ) ? $_GET['seating'] : '', 'theater' ); ?>><?php esc_html_e( 'Bestuhlung', 'seminargo' ); ?></option>
                                        <option value="classroom" <?php selected( isset( $_GET['seating'] ) ? $_GET['seating'] : '', 'classroom' ); ?>><?php esc_html_e( 'Klassenzimmer', 'seminargo' ); ?></option>
                                        <option value="u-shape" <?php selected( isset( $_GET['seating'] ) ? $_GET['seating'] : '', 'u-shape' ); ?>><?php esc_html_e( 'U-Form', 'seminargo' ); ?></option>
                                        <option value="banquet" <?php selected( isset( $_GET['seating'] ) ? $_GET['seating'] : '', 'banquet' ); ?>><?php esc_html_e( 'Bankett', 'seminargo' ); ?></option>
                                    </select>
                                </div>

                                <!-- Amenities -->
                                <div class="filter-group filter-group-full">
                                    <label><?php esc_html_e( 'Ausstattung', 'seminargo' ); ?></label>
                                    <div class="checkbox-group checkbox-grid">
                                        <?php
                                        $amenities = array(
                                            'wifi' => 'WLAN',
                                            'parking' => 'Parkplatz',
                                            'spa' => 'Spa & Wellness',
                                            'restaurant' => 'Restaurant',
                                            'pool' => 'Pool',
                                            'gym' => 'Fitness',
                                            'terrace' => 'Terrasse/Garten',
                                            'ac' => 'Klimaanlage',
                                        );
                                        foreach ( $amenities as $key => $label ) :
                                        ?>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="amenities[]" value="<?php echo esc_attr( $key ); ?>" <?php echo ( isset( $_GET['amenities'] ) && in_array( $key, (array) $_GET['amenities'] ) ) ? 'checked' : ''; ?>>
                                                <span><?php echo esc_html( $label ); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>

                            <div class="filters-actions">
                                <button type="button" class="btn-clear-filters" id="clear-filters">
                                    <?php esc_html_e( 'Filter zurücksetzen', 'seminargo' ); ?>
                                </button>
                                <button type="submit" class="btn-apply-filters">
                                    <?php esc_html_e( 'Filter anwenden', 'seminargo' ); ?>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </section>

        <!-- Results Section -->
        <section class="seminarhotels-results">
            <div class="container">

                <?php
                // Get current page from URL
                $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
                $per_page = 9; // Hotels per page

                // Get filter parameters from URL
                $filters = array(
                    'location' => isset( $_GET['location'] ) ? sanitize_text_field( $_GET['location'] ) : '',
                    'capacity' => isset( $_GET['capacity'] ) ? intval( $_GET['capacity'] ) : 0,
                    'date' => isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '',
                    'price_min' => isset( $_GET['price_min'] ) ? intval( $_GET['price_min'] ) : 0,
                    'price_max' => isset( $_GET['price_max'] ) ? intval( $_GET['price_max'] ) : 0,
                    'stars' => isset( $_GET['stars'] ) ? (array) $_GET['stars'] : array(),
                    'event_type' => isset( $_GET['event_type'] ) ? sanitize_text_field( $_GET['event_type'] ) : '',
                    'seating' => isset( $_GET['seating'] ) ? sanitize_text_field( $_GET['seating'] ) : '',
                    'amenities' => isset( $_GET['amenities'] ) ? (array) $_GET['amenities'] : array(),
                );

                // Build WP_Query args based on filters
                $query_args = array(
                    'post_type'      => 'hotel',
                    'posts_per_page' => $per_page,
                    'paged'          => $paged,
                    'post_status'    => 'publish',
                    'orderby'        => 'modified',
                    'order'          => 'DESC',
                );

                // Apply filters to query
                $meta_query = array( 'relation' => 'AND' );
                $tax_query = array( 'relation' => 'AND' );

                // Location filter (search in post title, content, or custom field)
                if ( !empty( $filters['location'] ) ) {
                    $query_args['s'] = $filters['location'];
                }

                // Capacity filter
                if ( !empty( $filters['capacity'] ) ) {
                    $meta_query[] = array(
                        'key'     => 'capacity',
                        'value'   => intval( $filters['capacity'] ),
                        'compare' => '>=',
                        'type'    => 'NUMERIC'
                    );
                }

                // Stars filter
                if ( !empty( $filters['stars'] ) ) {
                    $meta_query[] = array(
                        'key'     => 'stars',
                        'value'   => array_map( 'intval', $filters['stars'] ),
                        'compare' => 'IN',
                        'type'    => 'NUMERIC'
                    );
                }

                // Event type filter (assuming taxonomy)
                if ( !empty( $filters['event_type'] ) ) {
                    $tax_query[] = array(
                        'taxonomy' => 'event_type',
                        'field'    => 'slug',
                        'terms'    => sanitize_title( $filters['event_type'] ),
                    );
                }

                // Seating filter (assuming taxonomy)
                if ( !empty( $filters['seating'] ) ) {
                    $tax_query[] = array(
                        'taxonomy' => 'seating_type',
                        'field'    => 'slug',
                        'terms'    => sanitize_title( $filters['seating'] ),
                    );
                }

                // Amenities filter (assuming taxonomy or serialized meta)
                if ( !empty( $filters['amenities'] ) ) {
                    $tax_query[] = array(
                        'taxonomy' => 'amenity',
                        'field'    => 'slug',
                        'terms'    => array_map( 'sanitize_title', $filters['amenities'] ),
                        'operator' => 'AND',
                    );
                }

                // Add meta query if not empty
                if ( count( $meta_query ) > 1 ) {
                    $query_args['meta_query'] = $meta_query;
                }

                // Add tax query if not empty
                if ( count( $tax_query ) > 1 ) {
                    $query_args['tax_query'] = $tax_query;
                }

                // First, get total count with filters (for display)
                $count_args = $query_args;
                $count_args['posts_per_page'] = -1;
                $count_args['fields'] = 'ids';
                $count_args['no_found_rows'] = false;
                $count_query = new WP_Query( $count_args );
                $total_hotels = $count_query->found_posts;
                wp_reset_postdata();

                // Now run the actual query for current page
                $hotels_query = new WP_Query( $query_args );

                // Demo hotels data (for fallback)
                $demo_hotels = array(
                    array(
                        'id' => 1,
                        'image' => 'https://images.seminargo.pro/hotel-83421-4-400x300-FIT_AND_TRIM-f09c5c96e1bc6e5e8f88c37c951bbaa2.webp',
                        'title' => 'Dorint Hotel & Sportresort',
                        'location' => 'Winterberg/Sauerland',
                        'rating' => 4.5,
                        'reviews' => 245,
                        'stars' => 4,
                        'rooms' => 32,
                        'capacity' => 500,
                        'bedrooms' => 135,
                        'price' => 83.00,
                        'amenities' => array( 'WLAN', 'Parkplatz', 'Spa', 'Restaurant', 'Pool' ),
                        'features' => array( 'Tagungsräume', 'Freizeitaktivitäten', 'Wellnessbereich' ),
                        'link' => 'https://finder.dev.seminargo.eu/hotel-83421'
                    ),
                    array(
                        'id' => 2,
                        'image' => 'https://images.seminargo.pro/hotel-3111-2-400x300-FIT_AND_TRIM-fea59c5c951cbc623e866c7a87b23e81.webp',
                        'title' => 'Hotel Residence',
                        'location' => 'Bad Griesbach',
                        'rating' => 4.7,
                        'reviews' => 189,
                        'stars' => 5,
                        'rooms' => 6,
                        'capacity' => 220,
                        'bedrooms' => 100,
                        'price' => 79.00,
                        'amenities' => array( 'WLAN', 'Parkplatz', 'Spa', 'Restaurant' ),
                        'features' => array( 'Moderne Technik', 'Golfplatz', 'Thermalbad' ),
                        'link' => 'https://finder.dev.seminargo.eu/hotel-3111'
                    ),
                    array(
                        'id' => 3,
                        'image' => 'https://images.seminargo.pro/hotel-9227-2-400x300-FIT_AND_TRIM-f74f04e91f4e5b0aef5f436b93d09f07.webp',
                        'title' => 'Hotel Esperanto',
                        'location' => 'Fulda',
                        'rating' => 4.3,
                        'reviews' => 312,
                        'stars' => 4,
                        'rooms' => 21,
                        'capacity' => 1700,
                        'bedrooms' => 265,
                        'price' => 89.00,
                        'amenities' => array( 'WLAN', 'Parkplatz', 'Restaurant', 'Fitness' ),
                        'features' => array( 'Großer Konferenzsaal', 'Zentrale Lage', 'Event-Services' ),
                        'link' => 'https://finder.dev.seminargo.eu/hotel-9227'
                    ),
                    array(
                        'id' => 4,
                        'image' => 'https://images.seminargo.pro/hotel-77341-13-400x300-FIT_AND_TRIM-a017c8c1f2e50bc83e82e7b5c438bdf4.webp',
                        'title' => 'Kloster Maria Hilf',
                        'location' => 'Bühl',
                        'rating' => 4.8,
                        'reviews' => 98,
                        'stars' => 3,
                        'rooms' => 8,
                        'capacity' => 80,
                        'bedrooms' => 37,
                        'price' => 65.00,
                        'amenities' => array( 'WLAN', 'Parkplatz', 'Garten' ),
                        'features' => array( 'Ruhige Lage', 'Historisches Ambiente', 'Meditation' ),
                        'link' => 'https://finder.dev.seminargo.eu/hotel-77341'
                    ),
                    array(
                        'id' => 5,
                        'image' => 'https://images.seminargo.pro/hotel-70161-20-400x300-FIT_AND_TRIM-3e5c5e5bc951c2c623e86847193d09b4.webp',
                        'title' => 'Kloster Schöntal',
                        'location' => 'Schöntal',
                        'rating' => 4.6,
                        'reviews' => 156,
                        'stars' => 4,
                        'rooms' => 25,
                        'capacity' => 250,
                        'bedrooms' => 140,
                        'price' => 75.00,
                        'amenities' => array( 'WLAN', 'Parkplatz', 'Restaurant', 'Terrasse' ),
                        'features' => array( 'Klosteranlage', 'Kulturevents', 'Naturumgebung' ),
                        'link' => 'https://finder.dev.seminargo.eu/hotel-70161'
                    ),
                    array(
                        'id' => 6,
                        'image' => 'https://images.seminargo.pro/hotel-88251-8-400x300-FIT_AND_TRIM-e40c4761df5e8ce93e85f437b93d0961.webp',
                        'title' => 'Hotel Villa Toskana',
                        'location' => 'Leimen',
                        'rating' => 4.4,
                        'reviews' => 203,
                        'stars' => 4,
                        'rooms' => 8,
                        'capacity' => 100,
                        'bedrooms' => 146,
                        'price' => 99.00,
                        'amenities' => array( 'WLAN', 'Parkplatz', 'Spa', 'Pool', 'Restaurant' ),
                        'features' => array( 'Mediterrane Atmosphäre', 'Weinproben', 'Außenbereich' ),
                        'link' => 'https://finder.dev.seminargo.eu/hotel-88251'
                    ),
                );

                // Convert WP_Query results to hotel array format for template
                $current_page_hotels = array();

                // Debug: Show if real hotels were found
                if ( current_user_can( 'administrator' ) ) {
                    echo '<!-- DEBUG: Hotels found in database: ' . $hotels_query->found_posts . ' -->';
                    echo '<!-- DEBUG: Using ' . ( $hotels_query->have_posts() ? 'REAL HOTELS' : 'DEMO HOTELS' ) . ' -->';
                }

                if ( $hotels_query->have_posts() ) {
                    $demo_index = 0;
                    while ( $hotels_query->have_posts() ) {
                        $hotels_query->the_post();

                        // Get the featured image
                        $image_url = get_the_post_thumbnail_url( get_the_ID(), 'seminargo-thumbnail' );
                        if ( !$image_url ) {
                            // Fallback to demo image
                            $image_url = $demo_hotels[ $demo_index % count( $demo_hotels ) ]['image'];
                        }

                        // Get custom fields (adjust field names based on your actual setup)
                        // IMPORTANT: Always use get_permalink() for the link, never use external URLs
                        $hotel_data = array(
                            'id'        => get_the_ID(),
                            'image'     => $image_url,
                            'title'     => get_the_title(),
                            'location'  => get_post_meta( get_the_ID(), 'location', true ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['location'],
                            'rating'    => floatval( get_post_meta( get_the_ID(), 'rating', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['rating'],
                            'reviews'   => intval( get_post_meta( get_the_ID(), 'reviews', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['reviews'],
                            'stars'     => intval( get_post_meta( get_the_ID(), 'stars', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['stars'],
                            'rooms'     => intval( get_post_meta( get_the_ID(), 'rooms', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['rooms'],
                            'capacity'  => intval( get_post_meta( get_the_ID(), 'capacity', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['capacity'],
                            'bedrooms'  => intval( get_post_meta( get_the_ID(), 'bedrooms', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['bedrooms'],
                            'price'     => floatval( get_post_meta( get_the_ID(), 'price', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['price'],
                            'amenities' => get_post_meta( get_the_ID(), 'amenities', true ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['amenities'],
                            'features'  => get_post_meta( get_the_ID(), 'features', true ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['features'],
                            'featured'  => (bool) get_post_meta( get_the_ID(), 'featured', true ),
                            // CRITICAL: Always force WordPress permalink, never external URLs
                            'link'      => get_permalink( get_the_ID() ),
                        );

                        $current_page_hotels[] = $hotel_data;
                        $demo_index++;
                    }
                    wp_reset_postdata();
                } else {
                    // Fallback: When no hotels match the filters, get some real hotels without filters
                    // This ensures we always show actual WordPress hotels with proper permalinks
                    $fallback_query = new WP_Query( array(
                        'post_type'      => 'hotel',
                        'posts_per_page' => $per_page,
                        'post_status'    => 'publish',
                        'orderby'        => 'rand', // Random hotels
                        'order'          => 'DESC',
                    ) );

                    if ( $fallback_query->have_posts() ) {
                        // Use actual WordPress hotels
                        $demo_index = 0;
                        while ( $fallback_query->have_posts() ) {
                            $fallback_query->the_post();

                            $image_url = get_the_post_thumbnail_url( get_the_ID(), 'seminargo-thumbnail' );
                            if ( !$image_url ) {
                                $image_url = $demo_hotels[ $demo_index % count( $demo_hotels ) ]['image'];
                            }

                            $hotel_data = array(
                                'id'        => get_the_ID(),
                                'image'     => $image_url,
                                'title'     => get_the_title(),
                                'location'  => get_post_meta( get_the_ID(), 'location', true ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['location'],
                                'rating'    => floatval( get_post_meta( get_the_ID(), 'rating', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['rating'],
                                'reviews'   => intval( get_post_meta( get_the_ID(), 'reviews', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['reviews'],
                                'stars'     => intval( get_post_meta( get_the_ID(), 'stars', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['stars'],
                                'rooms'     => intval( get_post_meta( get_the_ID(), 'rooms', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['rooms'],
                                'capacity'  => intval( get_post_meta( get_the_ID(), 'capacity', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['capacity'],
                                'bedrooms'  => intval( get_post_meta( get_the_ID(), 'bedrooms', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['bedrooms'],
                                'price'     => floatval( get_post_meta( get_the_ID(), 'price', true ) ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['price'],
                                'amenities' => get_post_meta( get_the_ID(), 'amenities', true ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['amenities'],
                                'features'  => get_post_meta( get_the_ID(), 'features', true ) ?: $demo_hotels[ $demo_index % count( $demo_hotels ) ]['features'],
                                'featured'  => (bool) get_post_meta( get_the_ID(), 'featured', true ),
                                // ALWAYS use get_permalink for proper slug
                                'link'      => get_permalink( get_the_ID() ),
                            );

                            $current_page_hotels[] = $hotel_data;
                            $demo_index++;
                        }
                        wp_reset_postdata();
                        $total_hotels = $fallback_query->found_posts;
                    } else {
                        // Absolute last resort: no hotels exist at all, show empty state
                        $current_page_hotels = array();
                        $total_hotels = 0;
                    }
                }
                ?>

                <!-- Results Header -->
                <div class="results-header">
                    <div class="results-count">
                        <span class="count-text">
                            <span class="count-number"><?php echo esc_html( $total_hotels ); ?></span>
                            <?php esc_html_e( 'Seminarhotels gefunden', 'seminargo' ); ?>
                        </span>
                    </div>

                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" aria-label="<?php esc_attr_e( 'Grid View', 'seminargo' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </button>
                        <button class="view-btn" data-view="list" aria-label="<?php esc_attr_e( 'List View', 'seminargo' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Active Filters Tags -->
                <div class="active-filters" id="active-filters">
                    <!-- Populated by JavaScript -->
                </div>

                <!-- Hotels Grid -->
                <div class="hotels-grid view-grid" id="hotels-grid">
                    <?php
                    // Display hotels
                    foreach ( $current_page_hotels as $hotel ) :
                        get_template_part( 'template-parts/hotel', 'card', $hotel );
                    endforeach;

                    // If no hotels found, show a message
                    if ( empty( $current_page_hotels ) ) :
                    ?>
                        <div class="no-hotels-found">
                            <p><?php esc_html_e( 'Keine Hotels gefunden. Bitte passen Sie Ihre Suchkriterien an.', 'seminargo' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php
                $total_pages = ceil( $total_hotels / $per_page );

                if ( $total_pages > 1 ) :
                    // Build base URL with current filters
                    $base_url = strtok( $_SERVER['REQUEST_URI'], '?' );
                    $query_params = $_GET;
                    unset( $query_params['paged'] ); // Remove paged param
                    $query_string = http_build_query( $query_params );
                    $base_url .= $query_string ? '?' . $query_string . '&paged=' : '?paged=';
                ?>
                <div class="pagination-wrapper">
                    <nav class="pagination" aria-label="<?php esc_attr_e( 'Pagination Navigation', 'seminargo' ); ?>">

                        <?php if ( $paged > 1 ) : ?>
                            <a href="<?php echo esc_url( $base_url . ( $paged - 1 ) ); ?>" class="pagination-prev" aria-label="<?php esc_attr_e( 'Previous Page', 'seminargo' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </a>
                        <?php else : ?>
                            <span class="pagination-prev disabled">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </span>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            // Show max 7 pages
                            $range = 2;
                            $start = max( 1, $paged - $range );
                            $end = min( $total_pages, $paged + $range );

                            // Always show first page
                            if ( $start > 1 ) {
                                echo '<a href="' . esc_url( $base_url . '1' ) . '" class="pagination-number">1</a>';
                                if ( $start > 2 ) {
                                    echo '<span class="pagination-dots">...</span>';
                                }
                            }

                            // Show page numbers
                            for ( $i = $start; $i <= $end; $i++ ) {
                                if ( $i == $paged ) {
                                    echo '<span class="pagination-number active">' . $i . '</span>';
                                } else {
                                    echo '<a href="' . esc_url( $base_url . $i ) . '" class="pagination-number">' . $i . '</a>';
                                }
                            }

                            // Always show last page
                            if ( $end < $total_pages ) {
                                if ( $end < $total_pages - 1 ) {
                                    echo '<span class="pagination-dots">...</span>';
                                }
                                echo '<a href="' . esc_url( $base_url . $total_pages ) . '" class="pagination-number">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>

                        <?php if ( $paged < $total_pages ) : ?>
                            <a href="<?php echo esc_url( $base_url . ( $paged + 1 ) ); ?>" class="pagination-next" aria-label="<?php esc_attr_e( 'Next Page', 'seminargo' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        <?php else : ?>
                            <span class="pagination-next disabled">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </span>
                        <?php endif; ?>

                    </nav>
                </div>
                <?php endif; ?>

            </div>
        </section>

    </main>
</div>

<?php get_footer(); ?>
