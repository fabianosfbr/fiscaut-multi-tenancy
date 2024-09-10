<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Livewire;
use App\Livewire\Organization\Configuration\ProdutoGenericoForm;
use App\Livewire\Organization\Configuration\ConfiguracoesGeraisForm;
use App\Models\Tenant\Organization;

class ConfiguracoesGerais extends Page
{
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Configurações Gerais';

    protected static ?string $slug = 'configuracoes/configuracoes-gerais';

    protected static string $view = 'filament.client.pages.configuracoes-gerais';

    protected static ?int $navigationSort = 1;



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Livewire::make(ConfiguracoesGeraisForm::class),
            ])
            ->statePath('data');
    }
}
