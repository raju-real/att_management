<?php

namespace App\Services;

class BkashService
{
    protected $appKey;
    protected $appSecret;
    protected $username;
    protected $password;
    protected $sandboxMode;
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $settings = gatewaySettings();
        $bkashConfig = $settings->gateways->bkash ?? null;
        
        $this->appKey = $bkashConfig->app_key ?? '';
        $this->appSecret = $bkashConfig->app_secret ?? '';
        $this->username = $bkashConfig->username ?? '';
        $this->password = $bkashConfig->password ?? '';
        $this->sandboxMode = $bkashConfig->sandbox_mode ?? true;
        
        $this->baseUrl = $this->sandboxMode 
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta' 
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function grantToken()
    {
        $url = $this->baseUrl . '/tokenized/checkout/token/grant';
        
        $postData = [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret
        ];

        $header = [
            'Content-Type: application/json',
            'Accept: application/json',
            'username: ' . $this->username,
            'password: ' . $this->password
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
        
        if (isset($result['id_token'])) {
            $this->token = $result['id_token'];
            return $this->token;
        }

        return null;
    }

    public function createPayment($data)
    {
        $token = $this->grantToken();
        
        if (!$token) {
            return [
                'status' => 'FAILED',
                'message' => 'Failed to get bKash token'
            ];
        }

        $url = $this->baseUrl . '/tokenized/checkout/create';
        
        $postData = [
            'mode' => '0011',
            'payerReference' => $data['customer_phone'] ?? '01700000000',
            'callbackURL' => $data['callback_url'],
            'amount' => $data['amount'],
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $data['transaction_id']
        ];

        $header = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . $token,
            'X-APP-Key: ' . $this->appKey
        ];

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($handle);
        curl_close($handle);

        return json_decode($response, true);
    }

    public function executePayment($paymentID)
    {
        $token = $this->grantToken();
        
        if (!$token) {
            return [
                'status' => 'FAILED',
                'message' => 'Failed to get bKash token'
            ];
        }

        $url = $this->baseUrl . '/tokenized/checkout/execute';
        
        $postData = [
            'paymentID' => $paymentID
        ];

        $header = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . $token,
            'X-APP-Key: ' . $this->appKey
        ];

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($handle);
        curl_close($handle);

        return json_decode($response, true);
    }
}
