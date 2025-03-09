<?php

namespace App\Livewire\Organization\Configuration;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Cfop;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use App\Models\Tenant\EntradasCfopsEquivalente;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Tenant\GrupoEntradasCfopsEquivalente;

class CteSaidaForm extends Component implements HasForms
{
    use InteractsWithForms;

    private const TIPO = 'cte-saida';

    public ?array $data = [];

    public $values;

    public function mount(): void
    {
        $this->values = GrupoEntradasCfopsEquivalente::with('cfops')
            ->whereHas('cfops', function ($query) {
                $query->where('tipo', self::TIPO);
            })
            ->where('organization_id', Auth::user()->last_organization_id)->get();

        $this->form->fill([
            'organization_cfop' => $this->values,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('organization_cfop')
                    ->label('')
                    ->schema([
                        Repeater::make('cfops')
                            ->label('')
                            ->schema([
                                Select::make('cfop_entrada')
                                    ->label('CFOP')
                                    ->searchable()
                                    ->required()
                                    ->options($this->cfopsForSearching())
                                    ->columnSpan(2),
                                TagsInput::make('valores')
                                    ->label('CFOP Saída')
                                    ->required()
                                    ->placeholder('Digite a nova etiqueta e tecle ENTER para adicionar')
                                    ->columnSpan(2),

                                Checkbox::make('uf_diferente')
                                    ->label('Aplicar UF diferente')
                                    // ->visible(function () {
                                    //     return getIssuerGeneralSettings(getCurrentIssuer(), 'verificar_uf_emitente_destinatario');
                                    // })
                                    ->inline(false),
                            ])
                            ->addActionLabel('Adicionar CFOP')
                            ->columns(5),

                    ])
                    ->collapsible()
                    ->addActionLabel('Adicionar Etiqueta'),

            ])
            ->statePath('data');
    }

    public function cfopsForSearching()
    {

        $tagData = Cfop::getAllForTag()->pluck('full_name', 'codigo');

        return $tagData;
    }

    public function submit()
    {
        $values = $this->form->getState();

        foreach ($this->values as $grupo) {
            EntradasCfopsEquivalente::where('grupo_id', $grupo->id)
                ->where('tipo', self::TIPO)
                ->delete();

            GrupoEntradasCfopsEquivalente::find($grupo->id)->delete();
        }

        foreach ($values['organization_cfop'] as $value) {
            $grupo = GrupoEntradasCfopsEquivalente::create([
                'tags' => $value['tags'] ?? null,
                'organization_id' => Auth::user()->last_organization_id,
            ]);

            foreach ($value['cfops'] as $cfop) {
                EntradasCfopsEquivalente::create([
                    'cfop_entrada' => intval($cfop['cfop_entrada']),
                    'valores' => $cfop['valores'],
                    'tipo' => self::TIPO,
                    'uf_diferente' => $cfop['uf_diferente'] ?? false,
                    'grupo_id' => $grupo->id,
                ]);
            }
        }

        Notification::make()
            ->success()
            ->title('O valores foram salvos com sucesso!')
            ->send();
    }

    public function render()
    {
        return view('livewire.organization.configuration.cte-saida-form');
    }
}
