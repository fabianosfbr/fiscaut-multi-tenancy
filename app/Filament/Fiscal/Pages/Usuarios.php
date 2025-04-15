<?php

namespace App\Filament\Fiscal\Pages;

use App\Livewire\Organization\UserOrganizationForm;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

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
                Section::make('Configurações de Usuário')
                    ->description('Defina as permissões e painéis de acesso do usuário')
                    ->schema([
                        Livewire::make(UserOrganizationForm::class),
                        
                    ])
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->form->fill([
            'panels' => ['fiscal'] // Valor padrão
        ]);
    }
}
