<?php

namespace App\Models\Tenant\Concerns;

use App\Scopes\TenancyScope;

trait Tenantable
{
    protected static function bootTenantable()
    {
        static::addGlobalScope(new TenancyScope);


    }

}
