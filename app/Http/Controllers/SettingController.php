<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function siteSettings()
    {
        return view('site_settings');
    }

    public function updateSiteSettings(Request $request)
    {
        // office_holidays arrives as a JSON string from the date-chip picker.
        $holidays = json_decode((string) $request->input('office_holidays', '[]'), true);
        $request->merge(['office_holidays_list' => is_array($holidays) ? array_values(array_unique($holidays)) : null]);

        $validated = $request->validate([
            'site_name'              => 'required|string|max:100',
            'email'                  => 'nullable|email|max:100',
            'mobile'                 => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'in_time'                => 'nullable|date_format:H:i',
            'out_time'               => 'nullable|date_format:H:i|after:in_time',
            'weekly_holidays'        => 'nullable|array|max:6',
            'weekly_holidays.*'      => ['string', Rule::in(weekDays())],
            'office_holidays_list'   => 'nullable|array|max:366',
            'office_holidays_list.*' => 'date_format:Y-m-d',
        ], [
            'mobile.regex'                    => 'Enter a valid phone number (digits, +, -, spaces).',
            'out_time.after'                  => 'Default out time must be later than the default in time.',
            'weekly_holidays.max'             => 'At least one day of the week must be a working day.',
            'office_holidays_list.array'      => 'Office holidays could not be read. Please pick the dates again.',
            'office_holidays_list.*.date_format' => 'Every office holiday must be a valid date.',
        ]);

        $holidays = $validated['office_holidays_list'] ?? [];
        sort($holidays);

        try {
            writeJsonSettings('site_setting.json', 'site_settings', [
                'site_name'       => trim($validated['site_name']),
                'email'           => $validated['email'] ?? null,
                'mobile'          => $validated['mobile'] ?? null,
                // Stored as H:i:s; the fallback standard for students and for
                // teachers whose department has no shift.
                'in_time'         => !empty($validated['in_time']) ? $validated['in_time'] . ':00' : null,
                'out_time'        => !empty($validated['out_time']) ? $validated['out_time'] . ':00' : null,
                'weekly_holidays' => array_values($validated['weekly_holidays'] ?? []),
                'office_holidays' => $holidays,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with(dangerMessage('danger', $e->getMessage()));
        }

        return redirect()
            ->route('site-settings')
            ->with(infoMessage('info', 'Site settings updated successfully.'));
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

        writeJsonSettings('fee_setting.json', 'fee_settings', [
            'class_fees' => $validated['fees'] ?? [],
            'late_fee'   => $validated['late_fee'] ?? 0,
        ]);


        return redirect()
            ->route('fee-settings')
            ->with(successMessage('success', 'Fee settings updated successfully!'));
    }


}
