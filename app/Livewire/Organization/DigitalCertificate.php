<?php

namespace App\Livewire\Organization;

use Livewire\Component;

class DigitalCertificate extends Component
{
    public mixed $organization;

    public array $state = [];

    public function mount(mixed $organization): void
    {
        $this->organization = $organization;

        $this->state = $organization->toArray();
    }

    public function updateOrganizationName(): void
    {
        dd($this->state);
    }

    public function render()
    {
        return view('livewire.organization.digital-certificate');
    }
}
