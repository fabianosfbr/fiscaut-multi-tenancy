<?php

namespace App\Filament\Contabil\Resources;

use Exception;
use Filament\Forms;
use Filament\Tables;
use App\Models\Tenant\Layout;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Actions\UploadExcelAction;
use App\Models\ParametrosConciliacaoBancaria;
use App\Imports\Contabil\UploadFileExcelImport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Contabil\Resources\LayoutResource\Pages;
use App\Filament\Contabil\Resources\LayoutResource\RelationManagers;
use App\Filament\Contabil\Resources\LayoutResource\RelationManagers\LayoutRulesRelationManager;
use App\Filament\Contabil\Resources\LayoutResource\RelationManagers\LayoutColumnsRelationManager;


class LayoutResource extends Resource
{
    protected static ?string $model = Layout::class;

    protected static ?string $navigationLabel = 'Leiautes de Planilhas';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->maxLength(255)
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('organization_id', getOrganizationCached()->id);
            })
            ->recordUrl(null)
            ->headerActions([
                CreateAction::make()
                    ->label('Criar leiaute'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([

                Tables\Actions\EditAction::make()
                    ->label('Gerenciar'),

                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-square-2-stack')
                    ->action(function (Layout $record) {

                        $newLayout = $record->duplicateWithRelationships();
                        $newLayout->name = $record->name . ' - Cópia';
                        $newLayout->name = $record->description . ' - Cópia';
                        $newLayout->save();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->action(function (array $data, $record, $action) {
                        if ($record->layoutColumns->count() > 0) {
                            Notification::make()
                                ->title('Não é possível excluir o leiaute, pois existem colunas vinculadas a ele.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($record->layoutRules->count() > 0) {
                            Notification::make()
                                ->title('Não é possível excluir o leiaute, pois existem regras vinculadas a ele.')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Leiaute excluído com sucesso.')
                            ->success()
                            ->send();

                        $record->delete();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [

            LayoutColumnsRelationManager::class,
            LayoutRulesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLayouts::route('/'),
            'create' => Pages\CreateLayout::route('/create'),
            'edit' => Pages\EditLayout::route('/{record}/edit'),
        ];
    }
}
