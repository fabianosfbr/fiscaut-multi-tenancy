<?php

namespace App\Filament\Client\Pages\Tenant;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Livewire;
use App\Livewire\Organization\UserOrganizationForm;

class EditOrganizationPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.client.pages.tenant.edit-organization-page';

    protected static ?string $slug = 'edit-organization';

    protected static ?string $title = 'Editar empresa';

    protected static bool $shouldRegisterNavigation = false;


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
                        Tabs\Tab::make('Permissões')
                            ->schema([
                                Livewire::make(UserOrganizationForm::class),
                            ]),

                    ])

            ])
            ->statePath('data');
    }


    protected function getViewData(): array
    {
        return [
            'organization' => getTenant(),
        ];
    }


}
