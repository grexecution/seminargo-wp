<?php
/**
 * WP-CLI command for importing hotels
 *
 * Usage:
 *   wp seminargo import-hotels [--limit=N] [--offset=N] [--all]
 *
 * Examples:
 *   wp seminargo import-hotels --limit=500        # Import first 500 hotels
 *   wp seminargo import-hotels --offset=500 --limit=500  # Import hotels 501-1000
 *   wp seminargo import-hotels --all              # Import ALL hotels (no limit)
 *
 * @package Seminargo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only load if WP-CLI is available
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class Seminargo_Hotel_Import_CLI {

    /**
     * Import hotels from Apollo API via WP-CLI
     *
     * ## OPTIONS
     *
     * [--limit=<number>]
     * : Maximum number of hotels to import. Default: 1000
     *
     * [--offset=<number>]
     * : Start importing from this offset. Default: 0
     *
     * [--all]
     * : Import ALL hotels (removes the safety limit)
     *
     * ## EXAMPLES
     *
     *     # Import first 500 hotels
     *     wp seminargo import-hotels --limit=500
     *
     *     # Import hotels 1001-2000
     *     wp seminargo import-hotels --offset=1000 --limit=1000
     *
     *     # Import ALL hotels (no limit)
     *     wp seminargo import-hotels --all
     *
     * @when after_wp_load
     */
    public function import_hotels( $args, $assoc_args ) {

        // Get the importer instance
        $importer_class = new ReflectionClass( 'Seminargo_Hotel_Importer' );

        // Parse arguments
        $limit = isset( $assoc_args['all'] ) ? PHP_INT_MAX : ( isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 1000 );
        $offset = isset( $assoc_args['offset'] ) ? intval( $assoc_args['offset'] ) : 0;

        WP_CLI::line( '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' );
        WP_CLI::line( '🏨 Seminargo Hotel Import' );
        WP_CLI::line( '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' );
        WP_CLI::line( "Limit: " . ( $limit === PHP_INT_MAX ? 'ALL' : $limit ) );
        WP_CLI::line( "Offset: $offset" );
        WP_CLI::line( '' );

        // Set high limits for CLI
        @ini_set( 'memory_limit', '1024M' );
        @ini_set( 'max_execution_time', 0 ); // No time limit
        @set_time_limit( 0 );

        try {
            // Get API URL from centralized configuration
            $api_url = seminargo_get_api_url();

            WP_CLI::line( '📡 Fetching hotels from API...' );

            $hotels = $this->fetch_hotels_from_api_cli( $limit, $offset );

            if ( empty( $hotels ) ) {
                WP_CLI::error( 'No hotels received from API' );
                return;
            }

            WP_CLI::success( 'Fetched ' . count( $hotels ) . ' hotels from API' );
            WP_CLI::line( '' );

            // Get existing hotel IDs
            $existing = $this->get_existing_hotel_ids();
            WP_CLI::line( "📊 Found " . count( $existing ) . " existing hotels in WordPress" );
            WP_CLI::line( '' );

            // Process each hotel
            $stats = [
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
            ];

            $progress = \WP_CLI\Utils\make_progress_bar( 'Processing hotels', count( $hotels ) );

            foreach ( $hotels as $index => $hotel ) {
                try {
                    $result = $this->process_hotel_cli( $hotel, $existing );

                    if ( $result === 'created' ) {
                        $stats['created']++;
                    } elseif ( $result === 'updated' ) {
                        $stats['updated']++;
                    }

                } catch ( Exception $e ) {
                    $stats['errors']++;
                    WP_CLI::warning( "Error processing {$hotel->businessName}: " . $e->getMessage() );
                }

                $progress->tick();
            }

            $progress->finish();

            WP_CLI::line( '' );
            WP_CLI::line( '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' );
            WP_CLI::success( 'Import Complete!' );
            WP_CLI::line( "✅ Created: {$stats['created']}" );
            WP_CLI::line( "🔄 Updated: {$stats['updated']}" );
            WP_CLI::line( "❌ Errors: {$stats['errors']}" );
            WP_CLI::line( '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' );

        } catch ( Exception $e ) {
            WP_CLI::error( $e->getMessage() );
        }
    }

    /**
     * Fetch hotels from API (CLI version without logging)
     */
    private function fetch_hotels_from_api_cli( $max_limit, $offset = 0 ) {
        $api_url = seminargo_get_api_url();
        $all_hotels = [];
        $batch_size = 200;
        $skip = $offset;
        $has_more = true;

        while ( $has_more && count( $all_hotels ) < $max_limit ) {
            $remaining = $max_limit - count( $all_hotels );
            $current_batch_size = min( $batch_size, $remaining );

            $query = '{
                hotelList(skip: ' . $skip . ', limit: ' . $current_batch_size . ') {
                    id
                    slug
                    refCode
                    name
                    businessName
                    businessAddress1
                    businessAddress2
                    businessAddress3
                    businessAddress4
                    businessEmail
                    businessZip
                    businessCity
                    businessCountry
                    locationLongitude
                    locationLatitude
                    distanceToNearestAirport
                    distanceToNearestRailroadStation
                    rating
                    maxCapacityRooms
                    maxCapacityPeople
                    hasActivePartnerContract
                    texts {
                        id
                        details
                        type
                        language
                    }
                    attributes {
                        id
                        attribute
                    }
                    medias {
                        id
                        name
                        mimeType
                        width
                        height
                        format
                        path
                        url
                        previewUrl
                    }
                    integrations {
                        directBooking
                    }
                    spaceId
                    space {
                        name
                    }
                    meetingRooms {
                        id
                        name
                        area
                        capacityUForm
                        capacityTheater
                        capacityParlament
                        capacityCircle
                        capacityBankett
                        capacityCocktail
                        capacityBlock
                        facilityId
                        facility {
                            id
                            sku
                            name
                            header
                            details
                        }
                    }
                }
            }';

            $response = wp_remote_post( $api_url, [
                'body'    => json_encode( [ 'query' => $query ] ),
                'headers' => [ 'Content-Type' => 'application/json' ],
                'timeout' => 120,
            ] );

            if ( is_wp_error( $response ) ) {
                throw new Exception( 'API Error: ' . $response->get_error_message() );
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );

            if ( isset( $data->errors ) ) {
                throw new Exception( 'GraphQL Error: ' . json_encode( $data->errors ) );
            }

            $batch_hotels = $data->data->hotelList ?? [];
            $batch_count = count( $batch_hotels );

            if ( $batch_count === 0 ) {
                $has_more = false;
            } else {
                $all_hotels = array_merge( $all_hotels, $batch_hotels );
                $skip += $batch_size;

                if ( $batch_count < $current_batch_size ) {
                    $has_more = false;
                }
            }
        }

        return $all_hotels;
    }

    /**
     * Get existing hotel IDs
     */
    private function get_existing_hotel_ids() {
        global $wpdb;

        $results = $wpdb->get_col(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = 'hotel_id'
             AND p.post_type = 'hotel'
             AND pm.meta_value IS NOT NULL
             AND pm.meta_value != ''"
        );

        return array_flip( array_map( 'strval', $results ) );
    }

    /**
     * Process a single hotel (simplified CLI version)
     */
    private function process_hotel_cli( $hotel, &$existing ) {
        // Check if hotel exists
        $hotel_id_str = strval( $hotel->id );
        $is_new = ! isset( $existing[ $hotel_id_str ] );

        // Get the actual Seminargo_Hotel_Importer to use its methods
        global $seminargo_hotel_importer_instance;
        if ( ! $seminargo_hotel_importer_instance ) {
            $seminargo_hotel_importer_instance = new Seminargo_Hotel_Importer();
        }

        // Use reflection to call private process_hotel method
        $reflection = new ReflectionClass( 'Seminargo_Hotel_Importer' );
        $method = $reflection->getMethod( 'process_hotel' );
        $method->setAccessible( true );

        $result = $method->invoke( $seminargo_hotel_importer_instance, $hotel );

        // Add to existing list if new
        if ( $is_new ) {
            $existing[ $hotel_id_str ] = true;
        }

        return $result;
    }
}

// Register WP-CLI command
WP_CLI::add_command( 'seminargo import-hotels', [ 'Seminargo_Hotel_Import_CLI', 'import_hotels' ] );
