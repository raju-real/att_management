<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function siteSettings()
    {
        return view('site_settings');
    }

    public function updateSiteSettings(Request $request)
    {
        // ✅ Validation
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'mobile' => 'nullable|string|max:15',
            'in_time' => 'required|date_format:H:i:s',
            'out_time' => 'required|date_format:H:i:s',
        ]);

        // ✅ Save as JSON
        $jsonPath = base_path('assets/common/json/site_setting.json');
        file_put_contents($jsonPath, json_encode($validated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        cache()->forget('site_settings'); // 🔥 Clear & rebuild cache

        return redirect()->route('site-settings')->with(infoMessage());
    }

}
