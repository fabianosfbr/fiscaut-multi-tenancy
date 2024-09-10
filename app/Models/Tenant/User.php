<?php

namespace App\Models\Tenant;


use Filament\Panel;
use App\Enums\Tenant\UserTypeEnum;
use Illuminate\Support\Collection;
use App\Models\Tenant\Organization;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Tenant\PermissionTypeEnum;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use App\Models\Tenant\Concerns\HasPermissions;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\HasDefaultTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable  implements FilamentUser, HasTenants, HasDefaultTenant
{
    use HasFactory, HasUuids, Notifiable, HasRoles;

    protected $keyType = 'string';

    public $incrementing = false;
    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withPivot(['is_active', 'expires_at']);
    }

    public function getTenants(Panel $panel): Collection
    {

        return $this->organizations()->wherePivot('is_active', true)->get(); // @phpstan-ignore-line
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->latestOrganization;
    }

    public function latestOrganization(): BelongsTo
    {

        return $this->belongsTo(Organization::class, 'last_organization_id');
    }


    public function canAccessTenant(Model $tenant): bool
    {

        return $this->organizations->contains($tenant);
    }


    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function hasRole(...$roles)
    {
        foreach ($roles as $role) {
            if ($this->roles()->where('name', $role)->count()) {
                return true;
            }
        }

        return false;
    }
}
