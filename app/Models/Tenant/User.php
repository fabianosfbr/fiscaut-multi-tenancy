<?php

namespace App\Models\Tenant;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    use HasFactory, HasRoles, HasUuids, Notifiable;

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
            'owner' => 'boolean',
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


        return UserPanelPermission::where('user_id', $this->id)
            ->where('panel', $panel->getId())
            ->exists();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_users',
            'user_id',
            'role_id',
        )
            ->using(RoleUser::class)
            ->withPivot('organization_id');
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

    public function assignRole($roles, $organization_id)
    {

        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (empty($role)) {
                    return false;
                }

                return $this->getStoredRole($role);
            })
            ->filter(function ($role) {
                return $role instanceof Role;
            })
            ->each(function ($role) use ($organization_id) {

                $this->roles()->attach($role->id, ['organization_id' => $organization_id]);
            })
            ->all();

        $this->forgetCachedPermissions();

        return $this;
    }

    public function syncRolesWithOrganization($roles, $organization_id)
    {
        $existingRoles = $this->roles()->wherePivot('organization_id', $organization_id)->pluck('roles.id')->toArray();

        $rolesParaSincronizar = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (empty($role)) {
                    return false;
                }

                return $this->getStoredRole($role);
            })
            ->filter(function ($role) {
                return $role instanceof Role;
            })
            ->pluck('id')
            ->toArray();

        $rolesParaAdicionar = array_diff($rolesParaSincronizar, $existingRoles);
        $rolesParaRemover = array_diff($existingRoles, $rolesParaSincronizar);

        foreach ($rolesParaAdicionar as $roleId) {
            $this->roles()->attach($roleId, ['organization_id' => $organization_id]);
        }
        foreach ($rolesParaRemover as $roleId) {
            $this->roles()->detach($roleId);
        }

        $this->forgetCachedPermissions();

        return $this;
    }

    protected function getStoredRole($role): RoleContract
    {
        if (is_numeric($role)) {
            return Role::findById((int) $role, $this->getDefaultGuardName());
        }

        if (is_string($role)) {
            return Role::findByName($role, $this->getDefaultGuardName());
        }

        if ($role instanceof RoleContract) {
            return $role;
        }

        throw new RoleDoesNotExist;
    }

    protected function getDefaultGuardName(): string
    {
        return Guard::getDefaultName(static::class);
    }

    public function sieg()
    {
        return $this->hasOne(SiegConfiguration::class);
    }
}
