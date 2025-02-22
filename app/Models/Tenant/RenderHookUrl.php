<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class RenderHookUrl extends Model
{
    protected $connection = 'central';

    protected $table = 'render_hook_urls';
}
