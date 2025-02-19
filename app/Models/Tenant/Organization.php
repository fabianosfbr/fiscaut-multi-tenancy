<?php

namespace App\Models\Tenant;

use App\Models\Tenant\User;
use Filament\Models\Contracts\HasName;
use App\Observers\Tenant\OrganizationObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


#[ObservedBy([OrganizationObserver::class])]
class Organization extends Model implements HasName, HasCurrentTenantLabel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'atividade' => 'array',
            'tagsCreditoIcms' => 'array',
        ];
    }


    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(['is_active']);
    }



    public function categoryTags(): HasMany
    {
        return $this->hasMany(CategoryTag::class);
    }


    public function digitalCertificate(): HasOne
    {
        return $this->hasOne(DigitalCertificate::class);
    }


    public function getFilamentName(): string
    {

        return "{$this->razao_social}";
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Empresa atual';
    }


}
