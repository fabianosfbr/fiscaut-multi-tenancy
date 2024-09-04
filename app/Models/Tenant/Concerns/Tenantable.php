<?php

namespace App\Models\Tenant\Concerns;

use App\Scopes\TenancyScope;

trait Tenantable
{
    protected static function bootTenantable()
    {
        static::addGlobalScope(new TenancyScope);

        if (auth()->hasUser()) {
            static::creating(function ($model) {
                $model->organization_id = auth()->user()->last_organization_id;
            });
        }
    }
}
