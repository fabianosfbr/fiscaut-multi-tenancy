<?php

namespace App\Filament\App\Pages\Tenancy;

use Filament\Forms\Form;

use Filament\Facades\Filament;
use Filament\Forms\Components\Livewire;

use function Filament\authorize;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Auth\Access\AuthorizationException;


class EditOrganizationPage extends EditTenantProfile  implements HasForms
{

    use InteractsWithForms;
    protected static string $view = 'filament.client.pages.tenancy.edit-organization';

    protected static ?string $slug = 'manager-organization';


    public static function getLabel(): string
    {
        return 'Gerenciar Organização';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Dados Gerais')
                            ->schema([
                                Livewire::make('organization.edit-organization-form', $this->getViewData()),
                            ]),
                        Tabs\Tab::make('Certificado Digital')
                            ->schema([
                                Livewire::make('organization.digital-certificate-form', $this->getViewData()),
                            ]),
                        Tabs\Tab::make('Configuração Geral')
                            ->schema([
                                Livewire::make('organization.configuration-organization-form', $this->getViewData()),
                            ]),
                        Tabs\Tab::make('Usuários')
                            ->schema([
                                Livewire::make('organization.user-organization-form', $this->getViewData()),
                            ]),

                    ])

            ])
            ->statePath('data');
    }

    public static function canView(Model $tenant): bool
    {

        try {
            return authorize('view', $tenant)->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    protected function getViewData(): array
    {
        return [
            'organization' => Filament::getTenant(),
        ];
    }
}
