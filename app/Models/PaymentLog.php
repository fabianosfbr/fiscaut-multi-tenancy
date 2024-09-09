<?php

namespace App\Models\Tenant;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expire_date' => 'datetime',
        ];
    }

    public function package(){
        return $this->belongsTo('PricePlan','package_id','id');
    }

    public function user(){
        return $this->belongsTo('User','user_id','id');
    }

    public function price_plan(){
        return $this->hasMany('PricePlan','package_id','id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id','id');
    }

    public function domain()
    {
        return $this->belongsTo(\Stancl\Tenancy\Database\Models\Domain::class, 'tenant_id', 'tenant_id');
    }
}
