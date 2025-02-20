<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\Pivot;


class RoleUser extends Pivot
{

    protected $table = 'role_users';
    protected $fillable = ['organization_id', 'role_id', 'user_id'];

}
