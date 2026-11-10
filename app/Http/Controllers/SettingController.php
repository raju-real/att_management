<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Same page for everyone: the super admin edits the global Site Settings,
     * an institute edits its own (name, contact, logo, default times, holidays).
     */
    public function siteSettings()
    {
        $institute = $this->institute();

        return view('site_settings', ['site' => $this->formValues($institute), 'isInstitute' => (bool) $institute]);
    }

    public function updateSiteSettings(Request $request)
    {
        $institute = $this->institute();

        // office_holidays arrives as a JSON string from the date-chip picker.
        $holidays = json_decode((string) $request->input('office_holidays', '[]'), true);
        $request->merge(['office_holidays_list' => is_array($holidays) ? array_values(array_unique($holidays)) : null]);

        $validated = $request->validate([
            'site_name'              => 'required|string|max:' . ($institute ? 191 : 100),
            'email'                  => 'nullable|email|max:100',
            'mobile'                 => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'in_time'                => 'nullable|date_format:H:i',
            'out_time'               => 'nullable|date_format:H:i|after:in_time',
            'weekly_holidays'        => 'nullable|array|max:6',
            'weekly_holidays.*'      => ['string', Rule::in(weekDays())],
            'office_holidays_list'   => 'nullable|array|max:366',
            'office_holidays_list.*' => 'date_format:Y-m-d',
            'logo'                   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024',
            'address'                => 'nullable|string|max:255',
            'eiin'                   => 'nullable|string|max:30',
            'website'                => 'nullable|string|max:100',
            'remove_logo'            => 'nullable|boolean',
        ], [
            'logo.max'                        => 'The logo may not be larger than 1 MB.',
            'mobile.regex'                    => 'Enter a valid phone number (digits, +, -, spaces).',
            'out_time.after'                  => 'Default out time must be later than the default in time.',
            'weekly_holidays.max'             => 'At least one day of the week must be a working day.',
            'office_holidays_list.array'      => 'Office holidays could not be read. Please pick the dates again.',
            'office_holidays_list.*.date_format' => 'Every office holiday must be a valid date.',
        ]);

        $holidays = $validated['office_holidays_list'] ?? [];
        sort($holidays);

        // Logo (printed on reports). Kept unless replaced or removed.
        $logo = $institute ? $institute->logo : (siteSettings()->logo ?? null);
        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($logo && ($old = publicFilePath($logo))) {
                @unlink($old);
            }
            $logo = $request->hasFile('logo') ? uploadImage($request->file('logo'), $institute ? 'institutes' : 'site') : null;
        }

        $address = isset($validated['address']) ? trim($validated['address']) : null;
        $eiin    = isset($validated['eiin']) ? trim($validated['eiin']) : null;
        $website = isset($validated['website']) ? trim($validated['website']) : null;
        // Stored as H:i:s; the fallback standard for students and for
        // teachers whose department has no shift.
        $inTime  = !empty($validated['in_time']) ? $validated['in_time'] . ':00' : null;
        $outTime = !empty($validated['out_time']) ? $validated['out_time'] . ':00' : null;
        $weekly  = array_values($validated['weekly_holidays'] ?? []);

        if ($institute) {
            $institute->name     = trim($validated['site_name']);
            $institute->address  = $address;
            $institute->eiin     = $eiin;
            $institute->website  = $website;
            $institute->logo     = $logo;
            $institute->settings = array_merge((array) $institute->settings, [
                'in_time'         => $inTime,
                'out_time'        => $outTime,
                'weekly_holidays' => $weekly,
                'office_holidays' => $holidays,
                // printed on reports; the login email (users.email) is not touched
                'contact_email'   => $validated['email'] ?? null,
                'contact_mobile'  => $validated['mobile'] ?? null,
            ]);
            $institute->save();
        } else {
            try {
                writeJsonSettings('site_setting.json', 'site_settings', [
                    'logo'            => $logo,
                    'site_name'       => trim($validated['site_name']),
                    'email'           => $validated['email'] ?? null,
                    'mobile'          => $validated['mobile'] ?? null,
                    // Printed on report letterheads (schoolInfo()).
                    'address'         => $address,
                    'eiin'            => $eiin,
                    'website'         => $website,
                    'in_time'         => $inTime,
                    'out_time'        => $outTime,
                    'weekly_holidays' => $weekly,
                    'office_holidays' => $holidays,
                ]);
            } catch (\Throwable $e) {
                return back()->withInput()->with(dangerMessage('danger', $e->getMessage()));
            }
        }

        return redirect()
            ->route('site-settings')
            ->with(infoMessage('info', ($institute ? 'Institute' : 'Site') . ' settings updated successfully.'));
    }

    /** The logged-in institute (also for its users), or null for the super admin. */
    protected function institute(): ?User
    {
        return authInstituteId() ? User::institutes()->findOrFail(authInstituteId()) : null;
    }

    /** What the form shows. An institute that never saved sees the global defaults for times and holidays. */
    protected function formValues(?User $institute): object
    {
        if (! $institute) {
            return siteSettings();
        }

        $own      = (array) $institute->settings;
        $settings = instituteSettings($institute->id);

        return (object) [
            'site_name'       => $institute->name,
            'email'           => array_key_exists('contact_email', $own) ? $own['contact_email'] : $institute->email,
            'mobile'          => array_key_exists('contact_mobile', $own) ? $own['contact_mobile'] : $institute->mobile,
            'address'         => $institute->address,
            'eiin'            => $institute->eiin,
            'website'         => $institute->website,
            'logo'            => $institute->logo,
            'in_time'         => $settings->in_time,
            'out_time'        => $settings->out_time,
            'weekly_holidays' => $settings->weekly_holidays,
            'office_holidays' => $settings->office_holidays,
        ];
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
