<?php

namespace App\Services;

class RocketService
{
    protected $merchantId;
    protected $merchantPassword;
    protected $sandboxMode;
    protected $apiUrl;

    public function __construct()
    {
        $settings = gatewaySettings();
        $rocketConfig = $settings->gateways->rocket ?? null;
        
        $this->merchantId = $rocketConfig->merchant_id ?? '';
        $this->merchantPassword = $rocketConfig->merchant_password ?? '';
        $this->sandboxMode = $rocketConfig->sandbox_mode ?? true;
        
        $this->apiUrl = $this->sandboxMode 
            ? 'https://sandbox.rocketpay.com.bd/api/v1' 
            : 'https://api.rocketpay.com.bd/api/v1';
    }

    public function createPayment($data)
    {
        // Note: Rocket API implementation may vary
        // This is a generic implementation structure
        
        $postData = [
            'merchant_id' => $this->merchantId,
            'merchant_password' => $this->merchantPassword,
            'amount' => $data['amount'],
            'currency' => 'BDT',
            'transaction_id' => $data['transaction_id'],
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? '01700000000',
            'success_url' => $data['success_url'],
            'fail_url' => $data['fail_url'],
            'cancel_url' => $data['cancel_url'],
            'product_name' => $data['product_name'],
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl . '/payment/create');
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
                'message' => 'Unable to connect to Rocket payment gateway'
            ];
        }
    }

    public function validateTransaction($transactionId)
    {
        $postData = [
            'merchant_id' => $this->merchantId,
            'merchant_password' => $this->merchantPassword,
            'transaction_id' => $transactionId,
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl . '/payment/verify');
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
