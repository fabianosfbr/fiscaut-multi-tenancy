<?php

namespace App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Actions;

use App\Models\Tenant\Tag;
use App\Models\Tenant\CategoryTag;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\ConfiguracaoGeral;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use App\Forms\Components\SelectTagGrouped;

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
                        $isShow = ConfiguracaoGeral::getValue('isNfeClassificarNaEntrada', auth()->user()->last_organization_id);
                        return $isShow;
                    }),
                SelectTagGrouped::make('tags')
                    ->label('Etiqueta')
                    ->columnSpan(1)
                    ->multiple(false)
                    ->options(function () {
                        $categoryTag = CategoryTag::getAllEnabled(auth()->user()->last_organization_id);

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
