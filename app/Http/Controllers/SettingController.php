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
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'mobile' => 'nullable|string|max:15',
            'in_time' => 'required',
            'out_time' => 'required',
            'weekly_holidays' => 'nullable|sometimes|array',
            'office_holidays' => 'nullable|string|max:2000',
        ]);

        // ✅ Decode JSON safely
        $officeHolidays = [];

        if ($request->filled('office_holidays')) {
            $decoded = json_decode($request->office_holidays, true);

            if (is_array($decoded)) {
                $officeHolidays = $decoded;
            }
        }

        $validated['office_holidays'] = $officeHolidays;

        // ✅ Save as JSON file
        $jsonPath = base_path('assets/common/json/site_setting.json');

        file_put_contents(
            $jsonPath,
            json_encode($validated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        cache()->forget('site_settings');

        return redirect()
            ->route('site-settings')
            ->with(infoMessage());
    }


}
