<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Livewire;
use App\Livewire\Organization\Configuration\ProdutoGenericoForm;

class ProdutoGenerico extends Page
{
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Produtos Genéricos';

    protected static ?string $slug = 'configuracoes/produtos-genericos';

    protected static string $view = 'filament.client.pages.produto-generico';

    protected static ?int $navigationSort = 5;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Livewire::make(ProdutoGenericoForm::class),
            ])
            ->statePath('data');
    }
}
