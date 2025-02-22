<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenancyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->hasUser()) {
            // $builder->where('organization_id', auth()->user()->last_organization_id);
            $builder->where($model->getTable().'.organization_id', auth()->user()->last_organization_id);
        }

    }
}
