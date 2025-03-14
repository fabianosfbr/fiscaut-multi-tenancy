<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use App\Models\Tenant\Tag;
use Filament\Actions\Action;
use App\Models\Tenant\CategoryTag;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\ConfiguracaoGeral;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use App\Forms\Components\SelectTagGrouped;

class ClassificarNotaAvancadoAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'advanced-classification';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Classificar')
            ->icon('heroicon-o-tag')
            ->modalHeading('Classificação Avançada da Nota Fiscal')
            ->modalWidth('7xl')
            ->modalDescription('Realize a classificação avançada para esta nota fiscal.')
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalSubmitActionLabel('Classificar')
            ->form($this->getFormSchema())
            ->action(function (array $data, Model $record): void {
           
                $record->untag();
               

                //Aplica a etiqueta a nfe
                foreach ($data['etiquetas'] as $tag_apply) {                    
                    $record->tag($tag_apply['tag_id'], $tag_apply['valor'], $tag_apply['produtos']);
                }

                if (isset($data['data_entrada'])) {
                    $record->data_entrada = $data['data_entrada'];
                    $record->saveQuietly();
                }

                //Aplica a mesma tag ao CTe do tomador
                // $ctes = ConhecimentoTransporteEletronico::whereJsonContains('nfe_chave', ['chave' => $record->chave])
                //     ->where('tomador_cnpj', $record->destinatario_cnpj)->get();

                // if (isset($ctes)) {
                //     foreach ($ctes as $cte) {
                //         $cte->untag();
                //         $cte->tag($tag, $cte->vCTe);
                //     }
                // }
            })
            ->after(function () {
                Notification::make()
                    ->success()
                    ->title('Nota fiscal classificada com sucesso!')
                    ->body('Sua nota fiscal foi classificada com sucesso!')
                    ->duration(3000)
                    ->send();
            });
    }


    private function getFormSchema(): array
    {
        return [
            Grid::make()
                ->schema([
                    TextInput::make('total_nfe')
                        ->label('Valor da NFe')
                        ->prefix('R$')
                        ->disabled()
                        ->placeholder(function ($get, $set) {

                            $set('total_nfe', number_format($this->record->valor_total, 2, ',', '.'));

                            return number_format($this->record->valor_total, 2, ',', ' ');
                        }),
                    TextInput::make('num_etiqueta')
                        ->label('Qtde Etiqueta')
                        ->disabled()
                        ->placeholder(function ($get, $set) {
                            $set('num_etiqueta', count($get('etiquetas')));

                            return count($get('etiquetas'));
                        }),
                    TextInput::make('valor_total_etiquetas')
                        ->label('Valor Total Etiquetas')
                        ->prefix('R$')
                        ->disabled()
                        ->placeholder(function ($get, $set, $record) {
                            $fields = $get('etiquetas');
                            $sum = 0.0;
                            foreach ($fields as $field) {
                                $sum = $sum + floatval($field['valor']);
                                // if (count($field['produtos']) > 0) { // selecionou algum produto

                                //     foreach ($field['produtos'] as $produto) {

                                //         //Todo pegar o valor do produto
                                //     }
                                // }
                            }

                            $set('valor_total_etiquetas', number_format($sum, 2, ',', '.'));

                            return number_format($sum, 2, ',', ' ');
                        })->rules([
                            function () {
                                return function (string $attribute, $value, $fail) {

                                    $value = str_replace(',', '.', str_replace('.', '', $value));

                                    if ($value != $this->record->valor_total) {
                                        $fail('O valor deve ser igual o valor da nota');
                                    }
                                };
                            },
                        ]),
                    DatePicker::make('data_entrada')
                        ->label('Data Entrada')
                        ->validationAttribute('')
                        ->default(now())
                        ->required()
                        ->columnSpan(1),
                    Repeater::make('etiquetas')
                        ->label('Etiqueta')
                        ->schema([
                            SelectTagGrouped::make('tag_id')
                                ->label('Etiqueta')
                                ->columnSpan(1)
                                ->options(function () {
                                    $categoryTag = CategoryTag::getAllEnabled(Auth::user()->last_organization_id);

                                    foreach ($categoryTag as $key => $category) {
                                        $tags = [];
                                        foreach ($category->tags as $tagKey => $tag) {
                                            if (! $tag->is_enable) {
                                                continue;
                                            }
                                            $tags[$tagKey]['id'] = $tag->id;
                                            $tags[$tagKey]['name'] = $tag->code . ' - ' . $tag->name;
                                        }
                                        $tagData[$key]['text'] = $category->name;
                                        $tagData[$key]['children'] = $tags;
                                    }

                                    return $tagData ?? [];
                                }),


                            TextInput::make('valor')
                                ->prefix('R$')
                                ->live(onBlur: true)
                                ->maxLength(13)
                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                ->required()
                                ->columnSpan(1),
                            Select::make('produtos')
                                ->multiple()
                                ->reactive()
                                ->preload()
                                ->options(function ($record) {
                                    //dd($record->itens()->get()->pluck('descricao', 'codigo')->toArray());
                                    return $record->itens()->get()->pluck('descricao', 'codigo')->toArray();
                                })->columnSpan(1),

                        ])->columns(3)
                        ->columnSpan(3),

                ])->columns(3),
        ];
    }
}
