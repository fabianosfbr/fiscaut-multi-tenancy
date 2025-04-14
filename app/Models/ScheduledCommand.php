<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledCommand extends Model
{
    protected $fillable = [
        'command',
        'arguments',
        'preset',
        'time',
        'cron_expression',
        'enabled',
        'last_run_at',
    ];

    protected $casts = [
        'arguments' => 'array',
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
    ];
}
