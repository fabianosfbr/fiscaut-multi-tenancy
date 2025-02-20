<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenderHook extends Model
{
    protected $guarded = ['id'];


    public function renderHookUrl()
    {
        return $this->hasMany(RenderHookUrl::class);
    }
}
