<?php

/**
 * SSL Commerz Configuration
 * 
 * This config reads credentials from gateway_settings.json via correct path
 * The SslCommerzNotification library expects this structure
 */

// Get settings from gateway_settings.json (in assets/common/json)
$jsonPath = base_path('assets/common/json/gateway_settings.json');

$gatewayConfig = file_exists($jsonPath)
    ? json_decode(file_get_contents($jsonPath))
    : null;

// $gatewayConfig = getGatewaySettings();

$sslConfig = $gatewayConfig->gateways->ssl_commerz ?? null;

// Extract credentials
$storeId = $sslConfig->store_id ?? env('SSLCOMMERZ_STORE_ID', '');
$storePassword = $sslConfig->store_password ?? env('SSLCOMMERZ_STORE_PASSWORD', '');
$sandboxMode = $sslConfig->sandbox_mode ?? (env('SSLCOMMERZ_MODE', 'sandbox') === 'sandbox');

return [
    // API Credentials
    'apiCredentials' => [
        'store_id' => $storeId,
        'store_password' => $storePassword,
    ],

    // API Domain based on sandbox mode
    'apiDomain' => $sandboxMode
        ? 'https://sandbox.sslcommerz.com'
        : 'https://securepay.sslcommerz.com',

    // API URLs
    'apiUrl' => [
        'make_payment' => '/gwprocess/v4/api.php',
        'order_validate' => '/validator/api/validationserverAPI.php',
        'refund' => '/validator/api/merchantTransIDvalidationAPI.php',
    ],

    // Callback URLs
    'success_url' => '/payment/success',
    'failed_url' => '/payment/fail',
    'cancel_url' => '/payment/cancel',
    'ipn_url' => '/payment/ipn',

    // Connect from localhost
    'connect_from_localhost' => env('SSLCOMMERZ_LOCALHOST', true),
];
