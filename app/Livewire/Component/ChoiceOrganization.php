<?php

namespace App\Livewire\Component;

use App\Models\Tenant\Organization;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Cache;

class ChoiceOrganization extends Component implements HasForms
{
    use InteractsWithForms;


    public User $user;
    public  $organizations;

    public ?array $data = [];



    public function mount(): void
    {
        $this->user = Auth::user();

        $this->organizations = getAllValidOrganizationsForUser($this->user);

        $isLastOrganization = $this->organizations->where('id', $this->user->last_organization_id);

        if($isLastOrganization->isEmpty()){
            $this->user->last_organization_id = $this->organizations->first()->id;
            $this->user->saveQuietly();
        }

        $this->form->fill([
            'organization_id' => $this->user->last_organization_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('organization_id')
                    ->label('Empresa')
                    ->hiddenLabel()
                    ->required()
                    ->searchable()
                    ->prefix('Empresa:')
                    ->live()
                    ->options(function () {

                        return $this->organizations->pluck('razao_social', 'id');
                    })
                    ->afterStateUpdated(function (?string $state) {
                        if (filled($state)) {

                            Auth::user()->update(['last_organization_id' => $state]);

                            $this->redirect(request()->header('Referer'));
                        }
                    }),

            ])
            ->statePath('data');
    }

    public function render()
    {
        return view('livewire.component.choice-organization');
    }
}
