<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SiegConfiguration extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
