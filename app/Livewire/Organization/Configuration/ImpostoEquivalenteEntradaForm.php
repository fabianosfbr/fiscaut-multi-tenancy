<?php

namespace App\Livewire\Organization\Configuration;

use Livewire\Component;
use App\Models\Tenant\Tag;
use Filament\Tables\Table;
use App\Models\Tenant\CategoryTag;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Toggle;

use Illuminate\Support\Facades\Cache;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ToggleColumn;
use App\Forms\Components\SelectTagGrouped;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Tenant\ImpostoEquivalenteEntrada;
use Filament\Tables\Concerns\InteractsWithTable;

class ImpostoEquivalenteEntradaForm extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;


    public function table(Table $table): Table
    {
        return $table
            ->query(ImpostoEquivalenteEntrada::query())
            ->defaultSort('created_at', 'desc')
            ->searchDebounce(750)
            ->columns([
                TextColumn::make('tag')
                ->label('Etiqueta')
                ->formatStateUsing(function (ImpostoEquivalenteEntrada $record) {
                    return $record->tag . ' - ' . $record->tag_description;
                })
                ->searchable(),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable('tag_description'),
                ToggleColumn::make('status_icms')
                    ->label('ICMS'),
                ToggleColumn::make('status_ipi')
                    ->label('IPI'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('edit')
                ->label('Editar')
                ->modalWidth('lg')
                ->closeModalByEscaping(false)
                ->modalSubmitActionLabel('Salvar')
                ->fillForm(function (ImpostoEquivalenteEntrada $record) {

                    return [
                        'tag' => $record->tag_id,
                        'status_icms' => $record->status_icms,
                        'status_ipi' => $record->status_ipi,

                    ];
                })
                ->form(self::getFormSchema())
                ->action(function (array $data) {

                    $this->updateOrCreate($data);
                }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                // ...
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Adicionar')
                    ->modalHeading('Adicionar Imposto Equivalente')
                    ->modalWidth('lg')
                    ->modalSubmitActionLabel('Salvar')
                    ->closeModalByEscaping(false)
                    ->form(self::getFormSchema())
                    ->action(function (array $data) {

                        $this->updateOrCreate($data);
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.organization.configuration.imposto-equivalente-entrada-form');
    }

    public static function getFormSchema()
    {
        return [
            SelectTagGrouped::make('tag')
                ->label('Etiqueta')
                ->columnSpan(1)
                ->multiple(false)
                ->options(function () {
                    $categoryTag = CategoryTag::getAllEnabled(auth()->user()->last_organization_id);

                    foreach ($categoryTag as $key => $category) {
                        $tags = [];
                        foreach ($category->tags  as $tagKey => $tag) {
                            if (!$tag->is_enable) {
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

            Toggle::make('status_icms')
                ->label('Modifica ICMS')
                ->default(true),

            Toggle::make('status_ipi')
                ->label('Modifica IPI')
                ->default(true),


        ];
    }

    public function updateOrCreate($data)
    {

        $tag = Tag::find($data['tag']);

        $organization_id = auth()->user()->last_organization_id;

        ImpostoEquivalenteEntrada::updateOrCreate(
            [
                'tag' => $tag->code,
                'organization_id' => $organization_id
            ],
            [
                'tag' => $tag->code,
                'tag_id' => $tag->id,
                'tag_description' => $tag->name,
                'description' => 'Zera tag de IPI e/ou ICMS da Nfe',
                'status_icms' => $data['status_icms'],
                'status_ipi' => $data['status_ipi'],
            ]
        );

        Cache::forget('entradas_impostos_equivalentes_'. $organization_id);

        Notification::make()
            ->title('Etiqueta salva com sucesso')
            ->success()
            ->send();
    }
}
