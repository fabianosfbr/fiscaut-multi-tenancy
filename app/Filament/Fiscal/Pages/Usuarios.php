<?php

namespace App\Filament\Fiscal\Pages;

use App\Livewire\Organization\UserOrganizationForm;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Usuarios extends Page
{
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Permissões';

    protected static ?string $slug = 'configuracoes/permissoes';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.fiscal.pages.usuarios';

    // Livewire::make(UserOrganizationForm::class)

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Livewire::make(UserOrganizationForm::class),
            ])
            ->statePath('data');
    }
}
