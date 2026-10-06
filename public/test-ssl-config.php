<?php
/**
 * Quick test script to verify SSL Commerz configuration
 * Remove this file after testing
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SSL Commerz Config Test ===\n\n";

// Test config loading
$config = config('sslcommerz');

echo "Store ID: " . ($config['apiCredentials']['store_id'] ?? 'NOT SET') . "\n";
echo "Store Password: " . (empty($config['apiCredentials']['store_password']) ? 'NOT SET' : '***' . substr($config['apiCredentials']['store_password'], -4)) . "\n";
echo "API Domain: " . ($config['apiDomain'] ?? 'NOT SET') . "\n";
echo "Success URL: " . ($config['success_url'] ?? 'NOT SET') . "\n";
echo "Connect from localhost: " . ($config['connect_from_localhost'] ? 'YES' : 'NO') . "\n";

echo "\n=== Gateway Settings JSON ===\n\n";

$jsonPath = base_path('assets/common/json/gateway_settings.json');
echo "JSON Path: $jsonPath\n";
echo "File exists: " . (file_exists($jsonPath) ? 'YES' : 'NO') . "\n";

if (file_exists($jsonPath)) {
    $data = json_decode(file_get_contents($jsonPath));
    echo "Active Gateway: " . ($data->active_gateway ?? 'NOT SET') . "\n";
    echo "SSL Store ID: " . ($data->gateways->ssl_commerz->store_id ?? 'NOT SET') . "\n";
    echo "Sandbox Mode: " . ($data->gateways->ssl_commerz->sandbox_mode ? 'YES' : 'NO') . "\n";
}

echo "\n=== SSL Commerz Library Test ===\n\n";

try {
    $sslc = new \App\Library\SslCommerz\SslCommerzNotification();
    echo "SslCommerzNotification initialized: SUCCESS\n";
} catch (\Exception $e) {
    echo "SslCommerzNotification error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
