<?php

namespace App\Models\Tenant;


use Filament\Panel;
use App\Enums\Tenant\UserTypeEnum;
use Illuminate\Support\Collection;
use App\Models\Tenant\Organization;
use Illuminate\Database\Eloquent\Model;
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
    use HasFactory, HasUuids, Notifiable, HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

   // protected $with = ['roles'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
        dd($this->organizations->contains($tenant));
        return $this->organizations->contains($tenant);
    }


    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function is_super_admin(): bool
    {
        return $this->hasRole(UserTypeEnum::SUPER_ADMIN);
    }
}
