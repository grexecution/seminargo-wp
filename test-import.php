<?php
/**
 * Direct test of hotel import
 */
define('WP_USE_THEMES', false);
require('./wp-load.php');

echo "Starting hotel import test...\n\n";

// Get importer instance
if (!class_exists('Seminargo_Hotel_Importer')) {
    require(get_template_directory() . '/inc/hotel-importer.php');
}

$reflection = new ReflectionClass('Seminargo_Hotel_Importer');
$instance = $reflection->newInstanceWithoutConstructor();

// Test fetch_hotels_from_api
$method = $reflection->getMethod('fetch_hotels_from_api');
$method->setAccessible(true);

try {
    echo "Calling fetch_hotels_from_api()...\n";
    $hotels = $method->invoke($instance);
    
    echo "\n";
    echo "✅ SUCCESS!\n";
    echo "Total hotels fetched: " . count($hotels) . "\n\n";
    
    // Show first and last hotel
    if (!empty($hotels)) {
        echo "First hotel: {$hotels[0]->name} (ID: {$hotels[0]->id})\n";
        echo "Last hotel: {$hotels[count($hotels)-1]->name} (ID: {$hotels[count($hotels)-1]->id})\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- Checking logs ---\n";
$logs = get_option('seminargo_hotel_import_logs', []);
echo "Total log entries: " . count($logs) . "\n\n";

// Show last 20 logs
foreach (array_slice(array_reverse($logs), 0, 20) as $log) {
    echo "[{$log['type']}] {$log['message']}\n";
}
