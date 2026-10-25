<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Data isolation: when an INSTITUTE user is logged in, every Eloquent query
 * on an institute-owned model only sees that institute's rows.
 * A super admin (or no logged-in user, e.g. a device push) is not limited.
 */
class InstituteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $instituteId = authInstituteId();
        if ($instituteId !== null) {
            $builder->where($model->qualifyColumn('institute_id'), $instituteId);
        }
    }
}
