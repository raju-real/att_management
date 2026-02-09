<?php

namespace App\Services;

class SslCommerzService
{
    protected $storeId;
    protected $storePassword;
    protected $sandboxMode;
    protected $apiUrl;

    public function __construct()
    {
        $settings = gatewaySettings();
        $sslConfig = $settings->gateways->ssl_commerz ?? null;
        
        $this->storeId = $sslConfig->store_id ?? '';
        $this->storePassword = $sslConfig->store_password ?? '';
        $this->sandboxMode = $sslConfig->sandbox_mode ?? true;
        
        $this->apiUrl = $this->sandboxMode 
            ? 'https://sandbox.sslcommerz.com' 
            : 'https://securepay.sslcommerz.com';
    }

    public function createPaymentSession($data)
    {
        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $data['amount'],
            'currency' => 'BDT',
            'tran_id' => $data['transaction_id'],
            'success_url' => $data['success_url'],
            'fail_url' => $data['fail_url'],
            'cancel_url' => $data['cancel_url'],
            'cus_name' => $data['customer_name'],
            'cus_email' => $data['customer_email'] ?? 'student@example.com',
            'cus_add1' => $data['customer_address'] ?? 'N/A',
            'cus_city' => $data['customer_city'] ?? 'Dhaka',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $data['customer_phone'] ?? '01700000000',
            'product_name' => $data['product_name'],
            'product_category' => $data['product_category'] ?? 'Education',
            'product_profile' => 'general',
            'shipping_method' => 'NO',
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl . '/gwprocess/v4/api.php');
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $response = json_decode($content, true);
            return $response;
        } else {
            curl_close($handle);
            return [
                'status' => 'FAILED',
                'failedreason' => 'Unable to connect to payment gateway'
            ];
        }
    }

    public function validateTransaction($validationId)
    {
        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'val_id' => $validationId,
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl . '/validator/api/validationserverAPI.php');
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $response = json_decode($content, true);
            return $response;
        } else {
            curl_close($handle);
            return [
                'status' => 'FAILED',
                'error' => 'Unable to validate transaction'
            ];
        }
    }
}
