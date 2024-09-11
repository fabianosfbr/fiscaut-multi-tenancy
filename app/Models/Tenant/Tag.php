<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tag extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $appends = ['namecode'];

    public function category()
    {
        return $this->belongsTo(CategoryTag::class, 'category_id');
    }

    public function getNameCodeAttribute()
    {
        return "{$this->code} - {$this->name}";
    }
}
