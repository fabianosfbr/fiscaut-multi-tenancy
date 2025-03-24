<?php

namespace App\Models\Tenant\Concerns;

use App\Models\Tenant\Organization;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasEscrituracao
{
    public function organizacoesEscrituradas(): MorphToMany
    {
        return $this->morphToMany(
            Organization::class,
            'escrituravel',
            'escrituracao_fiscal'
        )->withTimestamps();
    }

    public function isEscrituradaParaOrganization(Organization $organization): bool
    {
        return $this->organizacoesEscrituradas()
            ->where('organization_id', $organization->id)
            ->exists();
    }

    public function toggleEscrituracao(Organization $organization): bool
    {
        if ($this->isEscrituradaParaOrganization($organization)) {
            $this->organizacoesEscrituradas()->detach($organization->id);
            return false;
        }

        $this->organizacoesEscrituradas()->attach($organization->id);
        return true;
    }
} 