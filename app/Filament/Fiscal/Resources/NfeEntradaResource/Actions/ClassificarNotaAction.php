<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use App\Models\Tenant\Tag;
use App\Models\Tenant\CategoryTag;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\ConfiguracaoGeral;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use App\Forms\Components\SelectTagGrouped;
use App\Models\Tenant\OrganizacaoConfiguracao;
use App\Services\Configuracoes\ConfiguracaoFactory;
use App\Models\Tenant\ConhecimentoTransporteEletronico;

class ClassificarNotaAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'classificar';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Classificar')
            ->icon('heroicon-o-tag')
            ->modalHeading('Classificar Nota Fiscal')
            ->modalWidth('lg')
            ->modalDescription('Selecione uma etiqueta para esta nota fiscal.')
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalSubmitActionLabel('Sim, etiquetar')
            ->form([
                DatePicker::make('data_entrada')
                    ->label('Data Entrada')
                    ->required()
                    ->format('Y-m-d')
                    ->weekStartsOnSunday()
                    ->default(now())
                    ->displayFormat('d/m/Y')
                    ->visible(function () {
                        $config = ConfiguracaoFactory::criar(getOrganizationCached()->id);

                        $isShow = $config->obterValor('geral', null, null, 'nfe_classificacao_data_entrada');
                        return $isShow;
                    }),
                SelectTagGrouped::make('tags')
                    ->label('Etiqueta')
                    ->columnSpan(1)
                    ->multiple(false)
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
            ])
            ->action(function (array $data, Model $record): void {


                $record->retag($data['tags']);

                if (isset($data['data_entrada'])) {
                    $record->data_entrada = $data['data_entrada'];
                    $record->saveQuietly();
                }

                $record->referenciasRecebidas()->where('documento_origem_type', ConhecimentoTransporteEletronico::class)
                    ->get()
                    ->unique('chave_acesso_origem')
                    ->map(function ($referencia) use ($data) {
                        $cte = ConhecimentoTransporteEletronico::where('chave_acesso', $referencia->chave_acesso_origem)->first();
                        if ($cte) {
                            $cte->retag($data['tags']);
                        }
                        return $referencia;
                    });
            })->after(function () {
                Notification::make()
                    ->success()
                    ->title('Nota fiscal classificada com sucesso!')
                    ->body('Sua nota fiscal foi classificada com sucesso!')
                    ->duration(3000)
                    ->send();
            });
    }
}
