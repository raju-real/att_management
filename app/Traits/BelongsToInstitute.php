<?php

namespace App\Traits;

use App\Models\Scopes\InstituteScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * For models that belong to an institute (institute_id = users.id of the
 * institute):
 *  - InstituteScope limits queries for institute users automatically
 *  - new rows get the logged-in institute's id when none was given
 *  - ->forInstitute($id) narrows a query for the super admin's filter
 *    (null = all institutes)
 */
trait BelongsToInstitute
{
    public static function bootBelongsToInstitute(): void
    {
        static::addGlobalScope(new InstituteScope());

        static::creating(function ($model) {
            if (empty($model->institute_id) && ($id = authInstituteId()) !== null) {
                $model->institute_id = $id;
            }
        });
    }

    public function institute()
    {
        return $this->belongsTo(User::class, 'institute_id');
    }

    public function scopeForInstitute(Builder $query, $instituteId): Builder
    {
        return $instituteId ? $query->where($this->qualifyColumn('institute_id'), (int) $instituteId) : $query;
    }
}
