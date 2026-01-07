<?php

use App\Models\Device;
use Carbon\Carbon;
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
            (object)['value' => 'student', 'title' => 'Student'],
            (object)['value' => 'teacher', 'title' => 'Teacher']
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

if (!function_exists('siteSettings')) {
    function siteSettings()
    {
        $jsonString = file_get_contents('assets/common/json/site_setting.json');
        return json_decode($jsonString, true);
    }
}

if (!function_exists('authUser')) {
    function authUser()
    {
        return Auth::check() ? Auth::user() : null;
    }
}

// Project related

if (!function_exists('activeDevices')) {
    function activeDevices()
    {
        return Device::latest('name')->select('id', 'name', 'serial_no')->get();
    }
}

if (!function_exists('showStudentFullName')) {
    function showStudentFullName($first_name, $middle_name, $last_name): string
    {
        return $first_name. ' '. $middle_name. ' '. $last_name;
    }
}



