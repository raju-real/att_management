<?php

namespace App\Services;

class NagadService
{
    protected $merchantId;
    protected $merchantKey;
    protected $sandboxMode;
    protected $baseUrl;

    public function __construct()
    {
        $settings = gatewaySettings();
        $nagadConfig = $settings->gateways->nagad ?? null;
        
        $this->merchantId = $nagadConfig->merchant_id ?? '';
        $this->merchantKey = $nagadConfig->merchant_key ?? '';
        $this->sandboxMode = $nagadConfig->sandbox_mode ?? true;
        
        $this->baseUrl = $this->sandboxMode 
            ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs' 
            : 'https://api.mynagad.com/api/dfs';
    }

    public function createPayment($data)
    {
        // Nagad API implementation
        // Note: Nagad uses complex signature generation
        
        $dateTime = date('YmdHis');
        $orderId = $data['transaction_id'];
        
        $postData = [
            'accountNumber' => $this->merchantId,
            'dateTime' => $dateTime,
            'sensitiveData' => [
                'merchantId' => $this->merchantId,
                'orderId' => $orderId,
                'currencyCode' => '050',
                'amount' => $data['amount'],
                'challenge' => $this->generateRandomString(40)
            ],
            'signature' => $this->generateSignature($orderId, $data['amount'])
        ];

        $url = $this->baseUrl . '/check-out/initialize/' . $this->merchantId . '/' . $orderId;

        $header = [
            'Content-Type: application/json',
            'X-KM-IP-V4: ' . request()->ip(),
            'X-KM-Client-Type: PC_WEB',
            'X-KM-Api-Version: v-0.2.0'
        ];

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($handle);
        curl_close($handle);

        $result = json_decode($response, true);
        
        if (isset($result['callBackUrl'])) {
            return [
                'status' => 'SUCCESS',
                'redirectUrl' => $result['callBackUrl']
            ];
        }

        return [
            'status' => 'FAILED',
            'message' => 'Failed to initialize Nagad payment'
        ];
    }

    protected function generateSignature($orderId, $amount)
    {
        // Simplified signature generation
        // In production, use proper RSA signing with private key
        $data = $this->merchantId . $orderId . $amount;
        return hash_hmac('sha256', $data, $this->merchantKey);
    }

    protected function generateRandomString($length = 40)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function validateTransaction($paymentRefId)
    {
        $url = $this->baseUrl . '/verify/payment/' . $paymentRefId;

        $header = [
            'Content-Type: application/json',
            'X-KM-IP-V4: ' . request()->ip(),
            'X-KM-Client-Type: PC_WEB',
            'X-KM-Api-Version: v-0.2.0'
        ];

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($handle);
        curl_close($handle);

        return json_decode($response, true);
    }
}
