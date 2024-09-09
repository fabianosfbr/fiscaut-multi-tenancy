<?php

namespace App\Models;

use App\Enums\Tenant\PricePlanTypEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricePlan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => PricePlanTypEnum::class,
            'status' => 'boolean',
        ];
    }

    public function plan_features()
    {
        return $this->hasMany(PlanFeature::class,'plan_id','id');
    }
}
