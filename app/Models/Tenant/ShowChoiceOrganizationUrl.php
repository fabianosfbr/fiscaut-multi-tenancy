<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ShowChoiceOrganizationUrl extends Model
{
    protected $connection = 'central';
    protected $table = 'render_hooks';

    protected $with = ['renderHookUrl'];


    public function scopeShow(Builder $query): Builder
    {
        return $query->where('hook_name', 'PanelsRenderHook::CONTENT_START');
    }


    public function renderHookUrl()
    {
        return $this->hasMany(RenderHookUrl::class, 'render_hook_id');
    }

}
