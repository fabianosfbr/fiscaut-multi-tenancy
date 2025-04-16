<?php

namespace App\Livewire\Component;

use App\Models\Tenant\ShowChoiceOrganizationUrl;
use App\Models\Tenant\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class ChoiceOrganization extends Component implements HasForms
{
    use InteractsWithForms;

    public User $user;

    public $organizations;

    public ?array $data = [];

    public bool $exclude_page = false;

    public function mount(): void
    {
        $this->user = Auth::user();

        $this->urlRenderAvoid();

        $this->organizations = getAllValidOrganizationsForUser($this->user);

        $isLastOrganization = $this->organizations->where('id', $this->user->last_organization_id);

        if ($isLastOrganization->isEmpty()) {
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
                        return $this->organizations->mapWithKeys(function ($organization) {
                            $razaoSocial = $organization->razao_social;

                            if (str_contains($razaoSocial, ':')) {
                                $razaoSocial = trim(explode(':', $razaoSocial)[0]);
                            }

                            return [$organization->id => $razaoSocial];
                        });
                    })
                    ->afterStateUpdated(function (?string $state) {
                        if (filled($state)) {

                            $user = Auth::user();

                            $cacheKey = "organization_{$state}_{$user->id}";

                            $user->last_organization_id = $state;
                            $user->saveQuietly();

                            Cache::forget($cacheKey);

                            $this->redirect(request()->header('Referer'));
                        }
                    }),

            ])
            ->statePath('data');
    }

    public function urlRenderAvoid()
    {

        $routeName = Route::current()->getName();

        if (is_array(config('excluded-pages.urls'))) {
            $exclusions = config('excluded-pages.urls');
            foreach ($exclusions as $exclusion) {
                if ($routeName == $exclusion) {
                    $this->exclude_page = true;
                    break;
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.component.choice-organization');
    }
}
