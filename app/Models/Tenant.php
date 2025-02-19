<?php

namespace App\Models;


use App\Models\PaymentLog;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'password',
            'razao_social',
            'cnpj',
            'domain',
        ];
    }

    protected $hidden = [
        'password',
    ];

    public function payment_log(): HasOne
    {
        return $this->hasOne(PaymentLog::class, 'tenant_id', 'id')->latest();
    }
}
