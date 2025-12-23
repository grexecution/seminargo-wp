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

        <!-- Hero Section -->
        <section class="seminarhotels-hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="page-title"><?php esc_html_e( 'Seminarhotels', 'seminargo' ); ?></h1>
                    <p class="page-subtitle"><?php esc_html_e( 'Entdecken Sie unsere Auswahl an Seminarhotels', 'seminargo' ); ?></p>
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

                // Build WP_Query args
                $query_args = array(
                    'post_type'      => 'hotel',
                    'posts_per_page' => $per_page,
                    'paged'          => $paged,
                    'post_status'    => 'publish',
                    'orderby'        => 'modified',
                    'order'          => 'DESC',
                );

                // Get total count
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
                        'link' => 'https://lister-staging.seminargo.com/hotel-83421'
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
                        'link' => 'https://lister-staging.seminargo.com/hotel-3111'
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
                        'link' => 'https://lister-staging.seminargo.com/hotel-9227'
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
                        'link' => 'https://lister-staging.seminargo.com/hotel-77341'
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
                        'link' => 'https://lister-staging.seminargo.com/hotel-70161'
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
                        'link' => 'https://lister-staging.seminargo.com/hotel-88251'
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
                    while ( $hotels_query->have_posts() ) {
                        $hotels_query->the_post();
                        $post_id = get_the_ID();

                        // Get the featured image
                        $image_url = get_the_post_thumbnail_url( $post_id, 'seminargo-thumbnail' );
                        if ( !$image_url ) {
                            // Try API media
                            $medias = json_decode( get_post_meta( $post_id, 'medias_json', true ), true );
                            if ( !empty( $medias[0]['previewUrl'] ) ) {
                                $image_url = $medias[0]['previewUrl'];
                            } else {
                                $image_url = 'https://placehold.co/400x300/e2e8f0/64748b?text=Hotel';
                            }
                        }

                        // Build location - prioritize city, then address (not country code)
                        $city = get_post_meta( $post_id, 'business_city', true );
                        $address = get_post_meta( $post_id, 'business_address_1', true );
                        if ( !empty( $city ) ) {
                            $location = $city;
                        } elseif ( !empty( $address ) ) {
                            $location = $address;
                        } else {
                            $location = '';
                        }

                        // Get hotel data using new API fields
                        $hotel_data = array(
                            'id'        => $post_id,
                            'image'     => $image_url,
                            'title'     => get_the_title(),
                            'location'  => $location ?: '',
                            'rating'    => floatval( get_post_meta( $post_id, 'rating', true ) ) ?: 0,
                            'stars'     => floatval( get_post_meta( $post_id, 'stars', true ) ) ?: 0,
                            'rooms'     => intval( get_post_meta( $post_id, 'rooms', true ) ) ?: 0,
                            'capacity'  => intval( get_post_meta( $post_id, 'capacity', true ) ) ?: 0,
                            'featured'  => (bool) get_post_meta( $post_id, 'featured', true ),
                            'link'      => get_permalink( $post_id ),
                        );

                        $current_page_hotels[] = $hotel_data;
                    }
                    wp_reset_postdata();
                } else {
                    // Fallback: When no hotels match the filters, get some real hotels without filters
                    $fallback_query = new WP_Query( array(
                        'post_type'      => 'hotel',
                        'posts_per_page' => $per_page,
                        'post_status'    => 'publish',
                        'orderby'        => 'rand',
                        'order'          => 'DESC',
                    ) );

                    if ( $fallback_query->have_posts() ) {
                        while ( $fallback_query->have_posts() ) {
                            $fallback_query->the_post();
                            $post_id = get_the_ID();

                            $image_url = get_the_post_thumbnail_url( $post_id, 'seminargo-thumbnail' );
                            if ( !$image_url ) {
                                $medias = json_decode( get_post_meta( $post_id, 'medias_json', true ), true );
                                if ( !empty( $medias[0]['previewUrl'] ) ) {
                                    $image_url = $medias[0]['previewUrl'];
                                } else {
                                    $image_url = 'https://placehold.co/400x300/e2e8f0/64748b?text=Hotel';
                                }
                            }

                            // Build location - prioritize city, then address (not country code)
                            $city = get_post_meta( $post_id, 'business_city', true );
                            $address = get_post_meta( $post_id, 'business_address_1', true );
                            if ( !empty( $city ) ) {
                                $location = $city;
                            } elseif ( !empty( $address ) ) {
                                $location = $address;
                            } else {
                                $location = '';
                            }

                            $hotel_data = array(
                                'id'        => $post_id,
                                'image'     => $image_url,
                                'title'     => get_the_title(),
                                'location'  => $location ?: '',
                                'rating'    => floatval( get_post_meta( $post_id, 'rating', true ) ) ?: 0,
                                'stars'     => floatval( get_post_meta( $post_id, 'stars', true ) ) ?: 0,
                                'rooms'     => intval( get_post_meta( $post_id, 'rooms', true ) ) ?: 0,
                                'capacity'  => intval( get_post_meta( $post_id, 'capacity', true ) ) ?: 0,
                                'featured'  => (bool) get_post_meta( $post_id, 'featured', true ),
                                'link'      => get_permalink( $post_id ),
                            );

                            $current_page_hotels[] = $hotel_data;
                        }
                        wp_reset_postdata();
                        $total_hotels = $fallback_query->found_posts;
                    } else {
                        // No hotels exist at all, show empty state
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
