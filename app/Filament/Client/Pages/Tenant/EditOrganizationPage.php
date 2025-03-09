<?php

namespace App\Filament\Client\Pages\Tenant;

use App\Livewire\Organization\DigitalCertificateForm;
use App\Livewire\Organization\EditOrganizationForm;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Pages\Page;

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
                                Livewire::make(EditOrganizationForm::class, $this->getViewData()),
                            ]),
                        Tabs\Tab::make('Certificado Digital')
                            ->schema([
                                Livewire::make(DigitalCertificateForm::class, $this->getViewData()),
                            ]),

                    ]),

            ])
            ->statePath('data');
    }

    protected function getViewData(): array
    {
        return [
            'organization' => getOrganizationCached(),
        ];
    }
}
