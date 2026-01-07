<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;


/**
 * Class ModelHelper.
 */
trait ModelHelper
{
    public function scopeActive(Builder $query)
    {
        return $query->where('status','active');
    }

    public function scopeSort(Builder $query)
    {
        return $query->orderBy('sorting_serial');
    }
}
