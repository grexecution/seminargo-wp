<?php
/**
 * Simple Hotel Importer - Rebuilt from Scratch
 * Single API call, live logs, clean code
 */

class Seminargo_Hotel_Importer_Simple {
    private $api_url;
    private $logs = [];

    public function __construct() {
        // Initialize API URL from centralized configuration
        $this->api_url = seminargo_get_api_url();
        
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'wp_ajax_seminargo_import_hotels', [ $this, 'ajax_import_hotels' ] );
        add_action( 'wp_ajax_seminargo_get_import_logs', [ $this, 'ajax_get_logs' ] );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=hotel',
            'Hotel Import',
            'Hotel Import',
            'manage_options',
            'hotel-import-simple',
            [ $this, 'render_admin_page' ]
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>🏨 Hotel Import</h1>

            <div class="card" style="max-width: 100%; padding: 20px; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h2 style="margin: 0;">Import All Hotels</h2>
                        <p style="margin: 5px 0 0; color: #666;">Fetches all hotels from API in a single call</p>
                    </div>
                    <button id="btn-import" class="button button-primary button-hero">
                        🚀 Start Import
                    </button>
                </div>

                <div id="import-status" style="display: none; margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 4px;">
                    <div style="font-weight: 600; margin-bottom: 10px;" id="status-text">Starting...</div>
                    <div style="background: #ddd; border-radius: 4px; height: 30px; overflow: hidden;">
                        <div id="progress-bar" style="background: #AC2A6E; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;"></div>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px; background: #1e1e1e; padding: 0;">
                    <div style="padding: 15px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; color: #fff;">📊 Live Import Logs</h3>
                        <button id="btn-refresh" class="button button-small">🔄 Refresh</button>
                    </div>
                    <div id="logs-container" style="padding: 15px; max-height: 600px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 13px; color: #e5e7eb;">
                        <div style="color: #868e96; text-align: center; padding: 40px;">
                            No import running. Click "Start Import" to begin.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .log-entry {
                padding: 8px 10px;
                margin-bottom: 2px;
                border-left: 3px solid #60a5fa;
                background: #1e3a5f;
                border-radius: 3px;
            }
            .log-entry.success {
                border-left-color: #10b981;
                background: #1e4d3d;
                color: #51cf66;
            }
            .log-entry.error {
                border-left-color: #ff6b6b;
                background: #4d1e1e;
                color: #ff6b6b;
            }
            .log-entry.info {
                border-left-color: #60a5fa;
                background: #1e3a5f;
                color: #72aee6;
            }
            .log-entry:first-child {
                animation: fadeIn 0.3s ease-in;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var logInterval = null;

            $('#btn-import').on('click', function() {
                var btn = $(this);

                if (!confirm('Import all hotels from API? This will take several minutes.')) {
                    return;
                }

                btn.prop('disabled', true).text('⏳ Importing...');
                $('#import-status').show();
                $('#status-text').text('Starting import...');
                $('#progress-bar').css('width', '5%').text('5%');

                // Clear logs
                $('#logs-container').html('<div style="color: #fbbf24; text-align: center; padding: 20px;">⏳ Import starting...</div>');

                // Start log polling
                logInterval = setInterval(loadLogs, 2000);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'seminargo_import_hotels' },
                    timeout: 3600000, // 1 hour
                    success: function(response) {
                        clearInterval(logInterval);
                        btn.prop('disabled', false).text('🚀 Start Import');

                        if (response.success) {
                            $('#progress-bar').css('width', '100%').css('background', '#10b981').text('100%');
                            $('#status-text').html('✅ <strong>Import completed!</strong> ' +
                                'Created: ' + response.data.created + ', ' +
                                'Updated: ' + response.data.updated);
                            loadLogs();
                            setTimeout(function() { location.reload(); }, 3000);
                        } else {
                            $('#progress-bar').css('background', '#ef4444').text('Error');
                            $('#status-text').html('❌ <strong>Import failed:</strong> ' + response.data);
                            loadLogs();
                        }
                    },
                    error: function(xhr, status, error) {
                        clearInterval(logInterval);
                        btn.prop('disabled', false).text('🚀 Start Import');
                        $('#progress-bar').css('background', '#ef4444').text('Error');
                        $('#status-text').html('❌ <strong>Error:</strong> ' + error);
                        loadLogs();
                    }
                });
            });

            $('#btn-refresh').on('click', function() {
                loadLogs();
            });

            function loadLogs() {
                $.post(ajaxurl, { action: 'seminargo_get_import_logs' }, function(response) {
                    if (response.success && response.data.length > 0) {
                        renderLogs(response.data);
                    }
                });
            }

            function renderLogs(logs) {
                var container = $('#logs-container');
                container.empty();

                logs.forEach(function(log) {
                    var typeClass = log.type || 'info';
                    var entry = $('<div class="log-entry ' + typeClass + '"></div>');
                    entry.text('[' + log.time + '] ' + log.message);
                    container.append(entry);
                });

                // Auto-scroll to bottom
                container.scrollTop(container[0].scrollHeight);
            }

            // Load initial logs
            loadLogs();
        });
        </script>
        <?php
    }

    public function ajax_import_hotels() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        // Set high limits
        @ini_set( 'memory_limit', '1024M' );
        @ini_set( 'max_execution_time', 0 );
        @set_time_limit( 0 );
        ignore_user_abort( true );

        // Clear previous logs
        $this->logs = [];
        delete_option( 'seminargo_import_logs_simple' );

        $this->log( 'info', '🚀 STARTING HOTEL IMPORT' );
        $this->log( 'info', 'Timestamp: ' . current_time( 'Y-m-d H:i:s' ) );

        try {
            // Fetch ALL hotels in ONE call
            $this->log( 'info', '📥 Fetching hotels from API...' );
            $this->log( 'info', 'API: ' . $this->api_url );
            $this->log( 'info', 'Parameters: limit=10000, skip=null' );

            $hotels = $this->fetch_hotels();

            if ( empty( $hotels ) ) {
                $this->log( 'error', '❌ No hotels received from API' );
                wp_send_json_error( 'No hotels received from API' );
                return;
            }

            $this->log( 'success', '✅ Fetched ' . count( $hotels ) . ' hotels from API' );

            // Process each hotel
            $created = 0;
            $updated = 0;
            $errors = 0;
            $total = count( $hotels );

            foreach ( $hotels as $index => $hotel ) {
                $progress = round( ( ( $index + 1 ) / $total ) * 100 );

                try {
                    $result = $this->process_hotel( $hotel );
                    if ( $result === 'created' ) {
                        $created++;
                        $this->log( 'success', '✅ Created: ' . $hotel->businessName );
                    } elseif ( $result === 'updated' ) {
                        $updated++;
                        $this->log( 'info', '🔄 Updated: ' . $hotel->businessName );
                    }

                    // Log progress every 100 hotels
                    if ( ( $index + 1 ) % 100 === 0 ) {
                        $this->log( 'info', sprintf( '📊 Progress: %d/%d (%d%%)', $index + 1, $total, $progress ) );
                    }

                } catch ( Exception $e ) {
                    $errors++;
                    $this->log( 'error', '❌ Error processing ' . $hotel->businessName . ': ' . $e->getMessage() );
                }
            }

            $this->log( 'success', '✅ IMPORT COMPLETED' );
            $this->log( 'info', sprintf( 'Created: %d, Updated: %d, Errors: %d', $created, $updated, $errors ) );

            wp_send_json_success( [
                'created' => $created,
                'updated' => $updated,
                'errors'  => $errors,
                'total'   => $total,
            ] );

        } catch ( Exception $e ) {
            $this->log( 'error', '❌ IMPORT FAILED: ' . $e->getMessage() );
            wp_send_json_error( $e->getMessage() );
        }
    }

    private function fetch_hotels() {
        // Single GraphQL query with limit 10000
        $query = '{
            hotelList(limit: 10000, skip: null) {
                id
                slug
                migSlug
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
                cancellationRules {
                    id
                    sequence
                    daysToEvent
                    minCapacity
                    maxCapacity
                    minOvernight
                    maxOvernight
                    minTotalGuests
                    maxTotalGuests
                    toleranceRate
                    rate
                }
            }
        }';

        $response = wp_remote_post( $this->api_url, [
            'body'    => json_encode( [ 'query' => $query ] ),
            'headers' => [ 'Content-Type' => 'application/json' ],
            'timeout' => 120,
        ] );

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'API Error: ' . $response->get_error_message() );
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        if ( $http_code !== 200 ) {
            throw new Exception( 'HTTP Error ' . $http_code );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( isset( $data->errors ) ) {
            throw new Exception( 'GraphQL Error: ' . json_encode( $data->errors ) );
        }

        return $data->data->hotelList ?? [];
    }

    private function process_hotel( $hotel ) {
        $hotel_id = strval( $hotel->id );
        $ref_code = $hotel->refCode ?? '';
        $hotel_title = $hotel->businessName ?? $hotel->name ?? 'Unnamed Hotel';

        // Check if hotel exists - check BOTH hotel_id AND ref_code as backup
        $args = [
            'post_type'      => 'hotel',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'   => 'hotel_id',
                    'value' => $hotel_id,
                ],
            ],
        ];
        
        // Add ref_code check if available (as backup duplicate detection)
        if ( ! empty( $ref_code ) ) {
            $args['meta_query'][] = [
                'key'   => 'ref_code',
                'value' => $ref_code,
            ];
        }
        
        $existing = get_posts( $args );
        
        // If found by ref_code but hotel_id doesn't match, update the hotel_id to fix data inconsistency
        if ( ! empty( $existing ) && ! empty( $ref_code ) ) {
            $found_post_id = $existing[0]->ID;
            $existing_hotel_id = get_post_meta( $found_post_id, 'hotel_id', true );
            
            // If hotel_id doesn't match, update it (fixes type mismatch issues)
            if ( strval( $existing_hotel_id ) !== $hotel_id ) {
                update_post_meta( $found_post_id, 'hotel_id', $hotel_id );
            }
        }

        if ( empty( $existing ) ) {
            // Create new hotel
            $post_id = wp_insert_post( [
                'post_title'   => $hotel_title,
                'post_type'    => 'hotel',
                'post_status'  => 'publish',
                'post_content' => $hotel->texts[0]->details ?? '',
            ] );

            if ( is_wp_error( $post_id ) ) {
                throw new Exception( 'Failed to create post: ' . $post_id->get_error_message() );
            }

            // Set hotel_id
            update_post_meta( $post_id, 'hotel_id', $hotel_id );

            // Set all metadata
            $this->update_hotel_meta( $post_id, $hotel );

            return 'created';

        } else {
            // Update existing hotel
            $post_id = $existing[0]->ID;

            wp_update_post( [
                'ID'           => $post_id,
                'post_title'   => $hotel_title,
                'post_content' => $hotel->texts[0]->details ?? '',
                'post_status'  => 'publish',
            ] );

            $this->update_hotel_meta( $post_id, $hotel );

            return 'updated';
        }
    }

    private function update_hotel_meta( $post_id, $hotel ) {
        $meta_fields = [
            'ref_code'                          => $hotel->refCode ?? '',
            'api_slug'                          => $hotel->slug ?? '',
            'mig_slug'                          => $hotel->migSlug ?? '',
            'address_1'                         => $hotel->businessAddress1 ?? '',
            'address_2'                         => $hotel->businessAddress2 ?? '',
            'zip'                               => $hotel->businessZip ?? '',
            'city'                              => $hotel->businessCity ?? '',
            'country'                           => $hotel->businessCountry ?? '',
            'email'                             => $hotel->businessEmail ?? '',
            'longitude'                         => $hotel->locationLongitude ?? '',
            'latitude'                          => $hotel->locationLatitude ?? '',
            'distance_airport'                  => $hotel->distanceToNearestAirport ?? '',
            'distance_railroad'                 => $hotel->distanceToNearestRailroadStation ?? '',
            'rating'                            => $hotel->rating ?? '',
            'max_capacity_rooms'                => $hotel->maxCapacityRooms ?? '',
            'max_capacity_people'               => $hotel->maxCapacityPeople ?? '',
            'has_active_partner_contract'       => $hotel->hasActivePartnerContract ? 1 : 0,
            'direct_booking'                    => $hotel->integrations->directBooking ?? '',
        ];

        foreach ( $meta_fields as $key => $value ) {
            update_post_meta( $post_id, $key, $value );

            if ( function_exists( 'update_field' ) ) {
                update_field( $key, $value, $post_id );
            }
        }

        // Store meeting rooms and cancellation rules as JSON
        if ( ! empty( $hotel->meetingRooms ) ) {
            update_post_meta( $post_id, 'meeting_rooms', json_encode( $hotel->meetingRooms ) );
        }

        if ( ! empty( $hotel->cancellationRules ) ) {
            update_post_meta( $post_id, 'cancellation_rules', json_encode( $hotel->cancellationRules ) );
        }
    }

    private function log( $type, $message ) {
        $entry = [
            'time'    => current_time( 'H:i:s' ),
            'type'    => $type,
            'message' => $message,
        ];

        $this->logs[] = $entry;

        // Save to database for AJAX polling
        update_option( 'seminargo_import_logs_simple', $this->logs, false );
    }

    public function ajax_get_logs() {
        $logs = get_option( 'seminargo_import_logs_simple', [] );
        wp_send_json_success( $logs );
    }
}

// Initialize
new Seminargo_Hotel_Importer_Simple();
