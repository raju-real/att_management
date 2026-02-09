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

    public function feeSettings()
    {
        $classes = getClassList();
        $settings = feeSettings();
        $classFees = isset($settings->class_fees) ? (array) $settings->class_fees : [];
        $lateFee = $settings->late_fee ?? 0;
        return view('fee_settings', compact('classes', 'classFees', 'lateFee'));
    }

    public function updateFeeSettings(Request $request)
    {
        $validated = $request->validate([
            'fees' => 'array',
            'fees.*' => 'nullable|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
        ]);

        // Load existing settings
        $path = base_path('assets/common/json/fee_setting.json');
        $currentSettings = [];
        
        if (file_exists($path)) {
            $currentSettings = json_decode(file_get_contents($path), true);
        }

        // Update with new fees
        $currentSettings['class_fees'] = $validated['fees'] ?? [];
        $currentSettings['late_fee'] = $validated['late_fee'] ?? 0;

        // Save back to JSON
        file_put_contents(
            $path,
            json_encode($currentSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        cache()->forget('fee_settings');

        return redirect()
            ->route('fee-settings')
            ->with(successMessage('success', 'Fee settings updated successfully!'));
    }


}
