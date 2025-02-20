<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenderHookUrl extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function renderHook()
    {
        return $this->belongsTo(RenderHook::class);
    }
}
