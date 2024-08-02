<?php

namespace App\Models\Tenant;

use Illuminate\Support\Str;
use App\Models\Tenant\Client as User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasUuids, Tenantable;

    protected $guarded = ['id'];


    public function setNameAttribute($value):void
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}

