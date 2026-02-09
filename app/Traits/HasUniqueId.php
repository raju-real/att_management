<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUniqueId
{
    protected static function bootHasUniqueId()
    {
        static::creating(function ($model) {

            // Skip if already set manually
            if (!empty($model->unique_id)) {
                return;
            }

            $model->unique_id = static::generateUniqueId();
        });
    }

    protected static function generateUniqueId(): string
    {
        $length = property_exists(static::class, 'uniqueIdLength')
                    ? static::$uniqueIdLength
                    : 10;

        $prefix = property_exists(static::class, 'uniqueIdPrefix')
                    ? static::$uniqueIdPrefix
                    : '';

        $lowercase = property_exists(static::class, 'uniqueIdLowercase')
                    ? static::$uniqueIdLowercase
                    : true;

        do {
            $random = Str::random($length);
            $random = $lowercase ? Str::lower($random) : Str::upper($random);

            $uniqueId = $prefix . $random;
        } while (
            static::where('unique_id', $uniqueId)->exists()
        );

        return $uniqueId;
    }
}
