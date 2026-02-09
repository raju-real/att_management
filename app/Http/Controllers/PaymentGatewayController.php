<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $settings = gatewaySettings();
        return view('gateway_settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'active_gateway' => 'required|in:ssl_commerz,bkash,rocket,nagad',
        ]);

        $path = base_path('assets/common/json/gateway_settings.json');
        
        // Get current settings
        $settings = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        
        // Update active gateway
        $settings['active_gateway'] = $request->active_gateway;
        
        // Update gateway credentials based on active gateway
        $activeGateway = $request->active_gateway;
        
        if ($activeGateway == 'ssl_commerz') {
            $settings['gateways']['ssl_commerz']['store_id'] = $request->ssl_store_id;
            $settings['gateways']['ssl_commerz']['store_password'] = $request->ssl_store_password;
            $settings['gateways']['ssl_commerz']['sandbox_mode'] = $request->has('ssl_sandbox_mode');
        } elseif ($activeGateway == 'bkash') {
            $settings['gateways']['bkash']['app_key'] = $request->bkash_app_key;
            $settings['gateways']['bkash']['app_secret'] = $request->bkash_app_secret;
            $settings['gateways']['bkash']['username'] = $request->bkash_username;
            $settings['gateways']['bkash']['password'] = $request->bkash_password;
            $settings['gateways']['bkash']['sandbox_mode'] = $request->has('bkash_sandbox_mode');
        } elseif ($activeGateway == 'rocket') {
            $settings['gateways']['rocket']['merchant_id'] = $request->rocket_merchant_id;
            $settings['gateways']['rocket']['merchant_password'] = $request->rocket_merchant_password;
            $settings['gateways']['rocket']['sandbox_mode'] = $request->has('rocket_sandbox_mode');
        } elseif ($activeGateway == 'nagad') {
            $settings['gateways']['nagad']['merchant_id'] = $request->nagad_merchant_id;
            $settings['gateways']['nagad']['merchant_key'] = $request->nagad_merchant_key;
            $settings['gateways']['nagad']['sandbox_mode'] = $request->has('nagad_sandbox_mode');
        }
        
        // Save to file
        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT));
        
        // Clear cache
        Cache::forget('gateway_settings');
        
        return back()->with(successMessage('success', 'Gateway settings updated successfully!'));
    }
}
