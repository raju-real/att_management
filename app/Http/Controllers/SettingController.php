<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function siteSettings()
    {
        return view('settings.site_settings');
    }

    public function updateSiteSettings(Request $request)
    {
        $this->validate($request,[
            'company_name' => 'required|string|max:100',
            'company_email' => 'nullable|sometimes|email|max:20',
            'company_mobile' => 'nullable|sometimes|string|max:15',
            'company_phone' => 'nullable|sometimes|string|max:20',
            'logo' => 'nullable|sometimes|mimes:jpg,jpeg,png|max:1024',
            'favicon' => 'nullable|sometimes|mimes:png|max:1024',
            'address' => 'nullable|sometimes|string|max:500',
            'slogan' => 'nullable|sometimes|string|max:255',
            'footer_text' => 'nullable|sometimes|string|max:1000',
            'website_url' => 'nullable|sometimes|url|max:255',
            'facebook_url' => 'nullable|sometimes|url|max:255',
            'linkedin_url' => 'nullable|sometimes|url|max:255',
            'youtube_url' => 'nullable|sometimes|url|max:255',
            'google_map_url' => 'nullable|sometimes|url|max:1000',
            'support_policy' => 'nullable|sometimes|string|max:5000',
            'return_policy' => 'nullable|sometimes|string|max:5000',
            'about_us' => 'nullable|sometimes|string|max:5000',
            'mission_and_vision' => 'nullable|sometimes|string|max:5000',
        ]);
        // Process keys and values
        $keys = array_keys($request->all());// Fetch all keys from the request
        $setting_data = [];
        foreach ($keys as $key) {
            $setting_data[$key] = $request->input($key, '');
        }
        if ($request->file('favicon')) {
            $setting_data['favicon'] = uploadImage($request->file('favicon'), 'settings');
        } elseif(isset(siteSettings()['favicon'])) {
            $setting_data['favicon'] = siteSettings()['favicon'];
        }
        if ($request->file('logo')) {
            $setting_data['logo'] = uploadImage($request->file('logo'), 'settings');
        } elseif(isset(siteSettings()['logo'])) {
            $setting_data['logo'] = siteSettings()['logo'];
        }
        $newJsonString = json_encode($setting_data, JSON_PRETTY_PRINT);
        file_put_contents(base_path('assets/common/json/site_setting.json'), $newJsonString);
        return redirect()->route('site-settings')->with(infoMessage());
    }
}
