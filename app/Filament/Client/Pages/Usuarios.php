<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Livewire;
use App\Livewire\Organization\UserOrganizationForm;

class Usuarios extends Page
{
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Permissões';
    protected static ?string $slug = 'configuracoes/permissoes';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.client.pages.usuarios';

    // Livewire::make(UserOrganizationForm::class)


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Livewire::make(UserOrganizationForm::class)
            ])
            ->statePath('data');
    }
}
