<?php
/**
 * Template part for displaying hotel cards
 *
 * @package Seminargo
 */

$hotel = $args;
?>

<article class="hotel-card" data-hotel-id="<?php echo esc_attr( $hotel['id'] ); ?>" data-link-debug="<?php echo esc_attr( $hotel['link'] ); ?>">
    <a href="<?php echo esc_url( $hotel['link'] ); ?>" class="hotel-card-link">

        <!-- Hotel Image -->
        <div class="hotel-card-image">
            <img src="<?php echo esc_url( $hotel['image'] ); ?>" alt="<?php echo esc_attr( $hotel['title'] ); ?>" loading="lazy">

            <!-- Rating Badge (top-left) -->
            <?php if ( ! empty( $hotel['rating'] ) && $hotel['rating'] > 0 ) :
                $rating_out_of_10 = floatval( $hotel['rating'] );
                $rating_out_of_5 = $rating_out_of_10 / 2;
                $full_rating_stars = floor( $rating_out_of_5 );
                $partial_fill = ( $rating_out_of_5 - $full_rating_stars ) * 100;
                $empty_stars = 5 - ceil( $rating_out_of_5 );
            ?>
                <div class="hotel-rating-badge">
                    <div class="rating-stars-container">
                        <?php for ( $i = 0; $i < $full_rating_stars; $i++ ) : ?>
                            <span class="star full">★</span>
                        <?php endfor; ?>
                        <?php if ( $partial_fill > 0 && $full_rating_stars < 5 ) : ?>
                            <span class="star partial" style="--fill-percent: <?php echo $partial_fill; ?>%;">★</span>
                        <?php endif; ?>
                        <?php for ( $i = 0; $i < $empty_stars; $i++ ) : ?>
                            <span class="star empty">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-score"><?php echo number_format( $rating_out_of_10, 1 ); ?>/10</span>
                </div>
            <?php endif; ?>

            <!-- Featured Badge (if applicable) -->
            <?php if ( ! empty( $hotel['featured'] ) ) : ?>
                <div class="hotel-featured-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <?php esc_html_e( 'Top', 'seminargo' ); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hotel Content -->
        <div class="hotel-card-content">

            <!-- Title and Location -->
            <div class="hotel-header">
                <h3 class="hotel-title"><?php echo esc_html( $hotel['title'] ); ?></h3>
                <div class="hotel-location">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span><?php echo esc_html( $hotel['location'] ); ?></span>
                </div>
            </div>

            <!-- Hotel Info Grid -->
            <div class="hotel-info-grid">
                <?php if ( ! empty( $hotel['rooms'] ) ) : ?>
                    <div class="info-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <span><?php echo esc_html( $hotel['rooms'] ); ?> Tagungsräume</span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $hotel['capacity'] ) ) : ?>
                    <div class="info-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>max. <?php echo esc_html( $hotel['capacity'] ); ?> Personen</span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $hotel['bedrooms'] ) ) : ?>
                    <div class="info-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 4v16"></path>
                            <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                            <path d="M2 17h20"></path>
                            <path d="M6 8v9"></path>
                        </svg>
                        <span><?php echo esc_html( $hotel['bedrooms'] ); ?> Zimmer</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Amenities -->
            <?php if ( ! empty( $hotel['amenities'] ) ) : ?>
                <div class="hotel-amenities">
                    <?php foreach ( array_slice( $hotel['amenities'], 0, 5 ) as $amenity ) : ?>
                        <span class="amenity-tag"><?php echo esc_html( $amenity ); ?></span>
                    <?php endforeach; ?>
                    <?php if ( count( $hotel['amenities'] ) > 5 ) : ?>
                        <span class="amenity-more">+<?php echo count( $hotel['amenities'] ) - 5; ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Features/Highlights -->
            <?php if ( ! empty( $hotel['features'] ) ) : ?>
                <div class="hotel-features">
                    <?php foreach ( $hotel['features'] as $feature ) : ?>
                        <div class="feature-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span><?php echo esc_html( $feature ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Hotel Footer -->
        <div class="hotel-card-footer">
            <div class="card-action">
                <span class="btn-details"><?php esc_html_e( 'Details ansehen', 'seminargo' ); ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </div>
        </div>

    </a>
</article>
