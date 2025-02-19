<?php

namespace App\Models\Tenant;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Guard;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;


class Permission extends SpatiePermission
{

    use HasUuids;
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;


}

