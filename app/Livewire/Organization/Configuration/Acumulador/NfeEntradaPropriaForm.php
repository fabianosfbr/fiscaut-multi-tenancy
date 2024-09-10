<?php

namespace App\Livewire\Organization\Configuration\Acumulador;

use Livewire\Component;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Tenant\EntradasAcumuladorEquivalente;

class NfeEntradaPropriaForm extends Component  implements HasForms
{
    use InteractsWithForms;

    private const TIPO = 'nfe-entrada-propria';

    public ?array $data = [];

    public function mount(): void
    {
        $values = EntradasAcumuladorEquivalente::where('tipo', self::TIPO)
            ->where('organization_id', auth()->user()->last_organization_id)
            ->get()->toArray();

        $this->form->fill([
            'configuracoes_acumuladores' => $values,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('configuracoes_acumuladores')
                    ->label('')
                    ->schema([
                        TextInput::make('etiqueta_entrada')
                            ->label('Acumulador')
                            ->required()
                            ->columnSpan(1),
                        TagsInput::make('valores')
                            ->label('Etiquetas')
                            ->placeholder('Digite a nova etiqueta e tecle ENTER para adicionar')
                            ->required()
                            ->columnSpan(1),
                        TagsInput::make('cfops')
                            ->label('CFOPs')
                            ->hint('CFOP já convertido')
                            ->placeholder('Digite o CFOP e tecle ENTER para adicionar')
                            ->columnSpan(1),

                    ])
                    ->columns(3)
                    ->addActionLabel('Adicionar Acumulador'),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $values = $this->form->getState();

        EntradasAcumuladorEquivalente::where('tipo', self::TIPO)
            ->where('organization_id', auth()->user()->last_organization_id)
            ->delete();

        foreach ($values['configuracoes_acumuladores'] as $value) {

            EntradasAcumuladorEquivalente::create([
                'etiqueta_entrada' => $value['etiqueta_entrada'],
                'organization_id' => auth()->user()->last_organization_id,
                'valores' => $value['valores'],
                'cfops' => $value['cfops'],
                'tipo' => self::TIPO,
            ]);
        }



        Notification::make()
            ->success()
            ->title('O valores foram salvos com sucesso!')
            ->send();
    }
    public function render()
    {
        return view('livewire.organization.configuration.acumulador.nfe-entrada-propria-form');
    }
}
