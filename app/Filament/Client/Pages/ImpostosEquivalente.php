<?php

namespace App\Filament\Client\Pages;

use Filament\Pages\Page;
use App\Models\Tenant\Tag;
use Filament\Tables\Table;
use App\Models\Tenant\CategoryTag;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ToggleColumn;
use App\Forms\Components\SelectTagGrouped;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Tenant\EntradasImpostosEquivalente;

class ImpostosEquivalente extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Impostos Equivalentes';

    protected static ?string $slug = 'configuracoes/impostos-equivalentes';
    protected static string $view = 'filament.client.pages.impostos-equivalentes';

    protected static ?int $navigationSort = 4;


    public function table(Table $table): Table
    {
        return $table
            ->query(EntradasImpostosEquivalente::query()->where('organization_id', auth()->user()->last_organization_id))
            ->columns([
                TextColumn::make('tag')
                    ->label('Estiqueta'),
                TextColumn::make('description')
                    ->label('Descrição'),
                ToggleColumn::make('status_icms')
                    ->label('ICMS'),
                ToggleColumn::make('status_ipi')
                    ->label('IPI'),
            ])
            ->filters([
                // ...
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Adicionar')
                    ->modalWidth('xl')
                    ->form([
                        SelectTagGrouped::make('tag')
                            ->label('Etiqueta')
                            ->columnSpan(1)
                            ->organization(auth()->user()->last_organization_id),
                        Toggle::make('status_icms')
                            ->label('Modifica ICMS')
                            ->default(true),
                        Toggle::make('status_ipi')
                            ->label('Modifica IPI')
                            ->default(true),
                    ])
                    ->action(function (array $data) {

                        $tag = Tag::find($data['tag']);

                        EntradasImpostosEquivalente::create([
                            'tag' => $tag->code,
                            'description' => 'Zera tag de IPI e/ou ICMS da Nfe',
                            'status_icms' => $data['status_icms'],
                            'status_ipi' => $data['status_ipi'],
                            'organization_id' => auth()->user()->last_organization_id,
                        ]);

                        // Cache::forget('entradas_impostos_equivalentes');

                        Notification::make()
                            ->title('Etiqueta salva com sucesso')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }
}
