<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Client as User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Issuer extends Model
{
    use HasFactory;


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(['is_active']);
    }
}
