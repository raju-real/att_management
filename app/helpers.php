<?php

use Carbon\Carbon;
use App\Models\Device;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

if (!function_exists('successMessage')) {
    function successMessage(string $type = 'success', string $message = "Information has been saved successfully!"): array
    {
        return [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('infoMessage')) {
    function infoMessage(string $type = 'info', string $message = "Information has been updated successfully!"): array
    {
        return [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('deleteMessage')) {
    function deleteMessage(string $type = 'primary', string $message = "Information has been deleted successfully!"): array
    {
        return [
            'type' => $type,
            'message' => $message
        ];
    }
}


if (!function_exists('dangerMessage')) {
    function dangerMessage(string $type = 'danger', string $message = "Information has been deleted successfully!"): array
    {
        return [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('warningMessage')) {
    function warningMessage(string $type = 'warning', string $message = "Something is wrong!"): array
    {
        return [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('starSign')) {
    function starSign(): string
    {
        return " <span class='text-danger'>" . " *" . "</span>";
    }
}

if (!function_exists('displayError')) {
    function displayError(string $error = "Something went wrong!"): string
    {
        return '<div class="invalid-feedback d-block fw-bold">
            <i class="fas fa-exclamation-circle"></i>' . $error .
            '</div>';
    }
}

if (!function_exists('devLogo')) {
    function devLogo(): string
    {
        return "assets/dev/ex_logo.jpg";
    }
}

if (!function_exists('hasError')) {
    function hasError(string $fieldName): string
    {
        $errors = session()->get('errors');
        return $errors && $errors->has($fieldName) ? 'border-danger is-invalid' : '';
    }
}

if (!function_exists('commonSpinner')) {
    function commonSpinner(): string
    {
        return "<i class='fa fa-spinner fa-spin me-2 spinner d-none'></i>";
    }
}

if (!function_exists('getStatus')) {
    function getStatus(): array
    {
        return [
            (object)['value' => 'active', 'title' => 'Active'],
            (object)['value' => 'inactive', 'title' => 'In Active']
        ];
    }
}

if (!function_exists('getDeviceFor')) {
    function getDeviceFor(): array
    {
        return [
            (object)['value' => 'student_teacher', 'title' => 'Student and Teacher/Staff'],
            (object)['value' => 'student', 'title' => 'Student'],
            (object)['value' => 'teacher', 'title' => 'Teacher/Staff']
        ];
    }
}

if (!function_exists('getSureStatus')) {
    function getSureStatus(): array
    {
        return [
            (object)['value' => 'yes', 'title' => 'Yes'],
            (object)['value' => 'no', 'title' => 'No']
        ];
    }
}

if (!function_exists('getClosedEnded')) {
    function getClosedEnded(): array
    {
        return [
            (object)['value' => 'n/a', 'title' => 'N/A'],
            (object)['value' => 'yes', 'title' => 'Yes'],
            (object)['value' => 'no', 'title' => 'No']
        ];
    }
}

if (!function_exists('isActive')) {
    function isActive($status): bool
    {
        return $status == 'active';
    }
}

if (!function_exists('showStatus')) {
    function showStatus($status): string
    {
        $status_badge = $status == 'active' ? 'info' : 'danger';
        $status_text = firstUpper($status);
        return "<span class='badge badge-{$status_badge}'>" . $status_text . "</span>";
    }
}

if (!function_exists('tooltip')) {
    function tooltip($title = "", $placement = "top"): string
    {
        return 'data-bs-toggle="tooltip" data-bs-placement="' . $placement . '" title="' . $title . '"';
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat($date, $format = 'Y-m-d'): string
    {
        return Carbon::parse($date)->format($format);
    }
}

if (!function_exists('timeFormat')) {
    function timeFormat($time = null, $format = 'H:i'): string
    {

        return $time ? Carbon::parse($time)->format($format) : '-';
    }
}

if (!function_exists('imageInfo')) {
    function imageInfo($image): array
    {
        return [
            'is_image' => isImage($image),
            'extension' => fileExtension($image),
            'width' => imageWidthHeight($image)['width'],
            'height' => imageWidthHeight($image)['height'],
            'size' => $image->getSize(),
            'mb_size' => fileSizeInMB($image->getSize())
        ];
    }
}

if (!function_exists('isImage')) {
    function isImage($file): bool
    {
        return $fileType = $file->getClientMimeType();
        $text = explode('/', $fileType)[0];
        return $text == "image";
    }
}

if (!function_exists('fileExtension')) {
    function fileExtension($file): mixed
    {
        if (isset($file)) {
            return $file->getClientOriginalExtension();
        } else {
            return "Invalid file";
        }
    }
}

if (!function_exists('imageWidthHeight')) {
    function imageWidthHeight($image): array
    {
        $imageSize = getimagesize($image);
        $width = $imageSize[0];
        $height = $imageSize[1];
        return array('width' => $width, 'height' => $height);
    }
}

if (!function_exists('fileSizeInMB')) {
    function fileSizeInMB($size): mixed
    {
        if ($size > 0) {
            return number_format($size / 1048576, 2);
        }
        return $size;
    }
}


if (!function_exists('userAvatar')) {
    function userAvatar(): string
    {
        return 'assets/common/images/avatar.png';
    }
}


if (!function_exists('firstUpper')) {
    function firstUpper($text): string
    {
        return ucfirst($text);
    }
}

if (!function_exists('uploadImage')) {
    function uploadImage($file, string $folderName = "partial/", $size = "", $width = "", $height = ""): string
    {
        $folderPath = "assets/files/images/" . $folderName;
        File::isDirectory($folderPath) || File::makeDirectory($folderPath, 0777, true, true);
        $imageName = time() . '-' . $file->getClientOriginalName();
        $image = Image::make($file->getRealPath());
        if ((isset($height)) && (isset($width))) {
            $image->resize($width, $height);
        }
        if (isset($size)) {
            $image->filesize($size);
        }
        $image->save($folderPath . "/" . $imageName);
        return $folderPath . "/" . $imageName;
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($file, string $folderName = "partial/")
    {
        try {
            $folderPath = "assets/files/" . trim($folderName, '/');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $uniqueFileName = time() . '_' . Str::slug($originalName) . '.' . $extension;

            if ($file->move($folderPath, $uniqueFileName)) {
                return $folderPath . "/" . $uniqueFileName;
            }
        } catch (\Throwable $e) {
            // Log the error or silently fail
            Log::error("File upload failed: " . $e->getMessage());
        }

        return null;
    }
}


if (!function_exists('segmentOne')) {
    function segmentOne(): ?string
    {
        return request()->segment(1);
    }
}

if (!function_exists('encrypt_decrypt')) {
    function encrypt_decrypt($key, $type)
    {
        # type = encrypt/decrypt
        $str_rand = "XxOx*4e!hQqG5b~9a";

        if (!$key) {
            return false;
        }
        if ($type == 'decrypt') {
            $en_slash_added1 = trim(str_replace(array('attmanagement'), '/', $key));
            $en_slash_added = trim(str_replace(array('dcattmanagement'), '%', $en_slash_added1));
            $key_value = $return = openssl_decrypt($en_slash_added, "AES-128-ECB", $str_rand);
            return $key_value;
        } elseif ($type == 'encrypt') {
            $key_value = openssl_encrypt($key, "AES-128-ECB", $str_rand);
            $en_slash_remove1 = trim(str_replace(array('/'), 'attmanagement', $key_value));
            $en_slash_remove = trim(str_replace(array('%'), 'dcattmanagement', $en_slash_remove1));
            return $en_slash_remove;
        }
        return FALSE;    # if function is not used properly
    }
}

if (!function_exists('textLimit')) {
    function textLimit($text = "", $limit = 20)
    {
        return Str::limit($text, $limit, '...');
    }
}

if (!function_exists('numberFormat')) {
    function numberFormat($number, $format = 2): mixed
    {
        return number_format($number, $format);
    }
}

if (!function_exists('ucFirst')) {
    function ucFirst($string = Null): string
    {
        return Str::ucfirst($string);
    }
}

if (!function_exists('jsonSettingsPath')) {
    /**
     * Single location for every JSON settings file (site / fee / gateway).
     * Reading and writing MUST both go through here.
     */
    function jsonSettingsPath(string $file): string
    {
        return public_path('assets/common/json/' . $file);
    }
}

if (!function_exists('readJsonSettings')) {
    function readJsonSettings(string $file, string $cacheKey): object
    {
        return cache()->rememberForever($cacheKey, function () use ($file) {
            $path = jsonSettingsPath($file);
            if (!is_file($path)) {
                return (object)[];
            }
            $data = json_decode((string) file_get_contents($path));
            return is_object($data) ? $data : (object)[];
        });
    }
}

if (!function_exists('writeJsonSettings')) {
    /**
     * Merge $values into the existing file (unknown keys are kept), write it
     * atomically and drop the cached copy.
     */
    function writeJsonSettings(string $file, string $cacheKey, array $values): void
    {
        $path = jsonSettingsPath($file);
        File::ensureDirectoryExists(dirname($path));
        $current = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        $json = json_encode(array_merge($current, $values), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($path, $json, LOCK_EX) === false) {
            throw new RuntimeException("Cannot write settings file: {$path}");
        }
        cache()->forget($cacheKey);
    }
}

if (!function_exists('isSuperAdmin')) {
    /** Logged-in user is the super admin (sees and manages every institute). */
    function isSuperAdmin(): bool
    {
        $u = Auth::user();
        return $u !== null && ($u->role ?? 'admin') === 'admin';
    }
}

if (!function_exists('authInstituteId')) {
    /**
     * The logged-in INSTITUTE's id (users.id), or null for the super admin /
     * guests. Used by InstituteScope to isolate each institute's data.
     */
    function authInstituteId(): ?int
    {
        $u = Auth::user();
        if ($u === null) {
            return null;
        }
        if (($u->role ?? null) === 'institute') {
            return (int) $u->id;
        }
        if (($u->role ?? null) === 'inst_user') {
            // an institute user works inside its institute; -1 (matches nothing) if it has none
            return $u->institute_id ? (int) $u->institute_id : -1;
        }
        return null;
    }
}

if (!function_exists('authInstitute')) {
    /** The logged-in institute account (also for an inst_user), or null for the super admin. */
    function authInstitute(): ?\App\Models\User
    {
        static $cache = [];
        $id = authInstituteId();
        if (! $id) {
            return null;
        }
        return $cache[$id] ??= \App\Models\User::institutes()->find($id);
    }
}

if (!function_exists('userCan')) {
    /**
     * May the logged-in user open this menu (App\Support\Permissions key)?
     * Admin and institute: always; inst_user: only its permissions.
     */
    function userCan(string $key): bool
    {
        $u = Auth::user();
        return $u !== null && $u->hasPermission($key);
    }
}

if (!function_exists('isInstUser')) {
    function isInstUser(): bool
    {
        return (Auth::user()->role ?? null) === 'inst_user';
    }
}

if (!function_exists('selectedInstituteId')) {
    /**
     * Institute a page/report works on: an institute user is always locked
     * to itself; the super admin picks one from the dropdown (null = all).
     */
    function selectedInstituteId(): ?int
    {
        if (($own = authInstituteId()) !== null) {
            return $own;
        }
        $req = request('institute_id');
        return ctype_digit((string) $req) && (int) $req > 0 ? (int) $req : null;
    }
}

if (!function_exists('instituteOptions')) {
    /** Institutes for dropdowns (an institute user only sees itself). */
    function instituteOptions()
    {
        return \App\Models\User::institutes()
            ->when(authInstituteId(), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('name')
            ->get(['id', 'name', 'status']);
    }
}

if (!function_exists('schoolInfo')) {
    /**
     * Letterhead data for every report (page, PDF, Excel): the institute's
     * own details (users row) when one is selected, otherwise the global
     * Site Settings. Change it there and every report follows.
     *
     * @return object{name:string, address:?string, eiin:?string, phone:?string, email:?string,
     *                website:?string, logo_path:?string, logo_url:?string, contact_line:string, is_institute:bool}
     */
    function schoolInfo(?int $instituteId = null): object
    {
        $instituteId = $instituteId ?? selectedInstituteId();
        $inst = $instituteId ? \App\Models\User::institutes()->find($instituteId) : null;
        $s    = siteSettings();
        $own  = $inst ? (array) $inst->settings : [];

        $src = $inst ? (object) [
            'site_name' => $inst->name, 'address' => $inst->address, 'eiin' => $inst->eiin,
            // contact details from its settings; until saved, the login email / mobile
            'mobile' => array_key_exists('contact_mobile', $own) ? $own['contact_mobile'] : $inst->mobile,
            'email' => array_key_exists('contact_email', $own) ? $own['contact_email'] : $inst->email,
            'website' => $inst->website,
            // its own logo only: no fallback to the super admin's Site Settings logo
            'logo' => $inst->logo,
        ] : $s;

        $logoFile = !empty($src->logo) ? publicFilePath($src->logo) : null;
        $logo     = $logoFile ? $src->logo : null;

        $contact = array_filter([
            !empty($src->eiin) ? 'EIIN: ' . $src->eiin : null,
            !empty($src->mobile) ? 'Phone: ' . $src->mobile : null,
            !empty($src->email) ? 'Email: ' . $src->email : null,
            !empty($src->website) ? $src->website : null,
        ]);

        return (object) [
            'name'         => !empty($src->site_name) ? $src->site_name : config('app.name', 'School'),
            'address'      => $src->address ?? null,
            'eiin'         => $src->eiin ?? null,
            'phone'        => $src->mobile ?? null,
            'email'        => $src->email ?? null,
            'website'      => $src->website ?? null,
            'logo_path'    => $logoFile, // absolute file, for PDF / Excel
            // embedded image data for mPDF: no file path / permission issue on any server
            'logo_pdf'     => $logoFile && ($bin = @file_get_contents($logoFile)) !== false
                ? 'data:' . (@mime_content_type($logoFile) ?: 'image/png') . ';base64,' . base64_encode($bin) : null,
            'logo_url'     => $logo ? asset($logo) : null,       // for web pages
            'contact_line' => implode('  |  ', $contact),
            'is_institute' => (bool) $inst,
        ];
    }
}

if (!function_exists('publicFilePath')) {
    /**
     * Absolute path of an uploaded public file (e.g. "assets/files/images/institutes/x.png"), or null.
     *
     * Uploads are saved relative to the folder the site runs from, which on some servers
     * (cPanel public_html, project root served directly) is not Laravel's public_path().
     * So look in every place the file can be.
     */
    function publicFilePath(?string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', (string) $relative), '/');
        if ($relative === '') {
            return null;
        }
        $roots = array_filter([
            public_path(),
            getcwd() ?: null,
            !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : null,
            !empty($_SERVER['SCRIPT_FILENAME']) ? dirname($_SERVER['SCRIPT_FILENAME']) : null,
            base_path(),
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'public_html',
        ]);
        foreach (array_unique($roots) as $root) {
            $file = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . $relative;
            if (is_file($file)) {
                return realpath($file) ?: $file;
            }
        }
        return null;
    }
}

if (!function_exists('siteSettings')) {
    function siteSettings()
    {
        return readJsonSettings('site_setting.json', 'site_settings');
    }
}

if (!function_exists('dayNameFromDate')) {
    /**
     * Get full day name (Monday, Tuesday, etc.) from a date
     *
     * @param \DateTimeInterface|string|null $date
     * @return string|null
     */
    function dayNameFromDate(DateTimeInterface|string $date = null)
    {
        if (!$date) {
            return null;
        }
        $carbonDate = $date instanceof \DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        return $carbonDate->format('l'); // Full day name: Monday, Tuesday...
    }
}


if (!function_exists('weeklyHolidays')) {
    /** Weekly holidays of an institute (default: the logged-in one; super admin: global). */
    function weeklyHolidays(?int $instituteId = null)
    {
        return array_values((array) (instituteSettings($instituteId ?? authInstituteId())->weekly_holidays ?? []));
    }
}

if (!function_exists('officeHolidays')) {
    /** Office / government holidays of an institute (default: the logged-in one; super admin: global). */
    function officeHolidays(?int $instituteId = null)
    {
        return array_values((array) (instituteSettings($instituteId ?? authInstituteId())->office_holidays ?? []));
    }
}

if (!function_exists('instituteSettings')) {
    /**
     * Default times and holidays that apply to an institute. A key the institute
     * never saved falls back to the global Site Settings; a saved empty value
     * stays empty (e.g. no weekly holiday). Null id = the global settings.
     *
     * @return object{in_time:?string, out_time:?string, weekly_holidays:mixed, office_holidays:mixed}
     */
    function instituteSettings(?int $instituteId = null, ?array $own = null): object
    {
        $global = siteSettings();
        if ($own === null && $instituteId) {
            $own = \App\Models\User::institutes()->whereKey($instituteId)->first(['id', 'settings'])?->settings;
        }
        $own = (array) $own;

        $out = [];
        foreach (['in_time', 'out_time', 'weekly_holidays', 'office_holidays'] as $key) {
            $out[$key] = array_key_exists($key, $own) ? $own[$key] : ($global->{$key} ?? null);
        }
        return (object) $out;
    }
}

if (!function_exists('holidayChecker')) {
    /** fn ($date): bool — is this date a weekly or office holiday of the institute? */
    function holidayChecker(?int $instituteId = null): \Closure
    {
        $weekly = array_map('strtolower', weeklyHolidays($instituteId));
        $office = officeHolidays($instituteId);

        return function ($date) use ($weekly, $office): bool {
            $day = $date instanceof \DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
            return in_array(strtolower($day->format('l')), $weekly, true) || in_array($day->toDateString(), $office, true);
        };
    }
}


if (!function_exists('authUser')) {
    function authUser()
    {
        return Auth::check() ? Auth::user() : null;
    }
}

// Project related

if(!function_exists('getClassList')) {
    function getClassList(): array
    {
        // Standard classes first (so the list is never empty on a fresh
        // install), then any other class names already used by students.
        $defaults = ['Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve'];
        $used = Student::whereNotNull('class')->where('class', '<>', '')->distinct()->pluck('class')->all();
        return array_values(array_unique(array_merge($defaults, $used)));
    }
}

if (!function_exists('weekDays')) {
    function weekDays(): array
    {
        return ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
//        return [
//            (object)['value' => 'Saturday', 'title' => 'Saturday'],
//            (object)['value' => 'Sunday', 'title' => 'Sunday'],
//            (object)['value' => 'Monday', 'title' => 'Monday'],
//            (object)['value' => 'Tuesday', 'title' => 'Tuesday'],
//            (object)['value' => 'Wednesday', 'title' => 'Wednesday'],
//            (object)['value' => 'Thursday', 'title' => 'Thursday'],
//            (object)['value' => 'Friday', 'title' => 'Friday'],
//        ];
    }
}

if (!function_exists('activeDevices')) {
    function activeDevices()
    {
        return Device::latest('name')->select('id', 'name', 'serial_no')->get();
    }
}

if (!function_exists('activeDeviceSerials')) {
    /**
     * Serial numbers of active devices, from the "device_serial_number"
     * cache maintained by App\Observers\DeviceObserver (rebuilt if missing).
     *
     * @return string[]
     */
    function activeDeviceSerials(): array
    {
        $cached = cache()->get(\App\Observers\DeviceObserver::CACHE_KEY);
        return is_array($cached) ? $cached : \App\Observers\DeviceObserver::refresh();
    }
}

if (!function_exists('showStudentFullName')) {
    function showStudentFullName($first_name, $middle_name, $last_name): string
    {
        return $first_name . ' ' . $middle_name . ' ' . $last_name;
    }
}

if (!function_exists('minuteCount')) {
    function minuteCount($check_out_time, $check_in_time)
    {
        return ceil((strtotime($check_out_time) - strtotime($check_in_time)) / 60);
    }
}

if (!function_exists('hourCount')) {
    function hourCount($check_out_time, $check_in_time)
    {
        if (!$check_in_time || !$check_out_time) {
            return '-';
        }

        $seconds = strtotime($check_out_time) - strtotime($check_in_time);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}

if (!function_exists('isLateIn')) {
    /**
     * $standard_in optionally overrides the comparison time — pass a
     * teacher's assigned Shift in_time so lateness is judged against their
     * own shift rather than the site-wide default.
     */
    function isLateIn($in_time = null, $standard_in = null): bool
    {
        $standard_in = $standard_in ?: (siteSettings()->in_time ?? null);
        if (!$in_time || empty($standard_in)) {
            return false;
        }
        $standard_in = Carbon::parse($standard_in);
        $user_in = Carbon::parse($in_time);
        return $user_in->gt($standard_in);
    }
}

if(!function_exists('feeSettings')) {
function feeSettings()
    {
        return readJsonSettings('fee_setting.json', 'fee_settings');
    }
}

if (!function_exists('gatewaySettings')) {
    function gatewaySettings()
    {
        return readJsonSettings('gateway_settings.json', 'gateway_settings');
    }
}


if (!function_exists('isEarlyOut')) {
    /**
     * $standard_out optionally overrides the comparison time — pass a
     * teacher's assigned Shift out_time so early-out is judged against their
     * own shift rather than the site-wide default.
     */
    function isEarlyOut($check_out = null, $standard_out = null): bool
    {
        $standard_out = $standard_out ?: (siteSettings()->out_time ?? null);
        if (!$check_out || empty($standard_out)) {
            return false;
        }
        $standard_out = Carbon::parse($standard_out);
        $user_out = Carbon::parse($check_out);
        return $user_out->lt($standard_out);
    }
}






if (!function_exists('fitFontSize')) {
    /**
     * Largest font size (pt) at which $text stays on ONE line in a box
     * $widthMm wide — for PDF letterheads (mPDF cannot measure for us).
     * Bold sans glyphs average ~0.62 em; 1 pt = 0.3528 mm.
     * Web pages use the .fit-one-line class (layouts/app) instead.
     */
    function fitFontSize(?string $text, float $maxPt, float $widthMm, float $minPt = 5): float
    {
        $len = max(1, mb_strlen(trim((string) $text)));
        $fit = $widthMm / ($len * 0.62 * 0.3528);
        return round(max($minPt, min($maxPt, $fit)), 1);
    }
}
