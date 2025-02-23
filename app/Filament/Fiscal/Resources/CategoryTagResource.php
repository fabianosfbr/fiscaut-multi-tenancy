<?php

namespace App\Filament\Fiscal\Resources;

use App\Filament\Fiscal\Resources\CategoryTagResource\Pages;
use App\Filament\Fiscal\Resources\CategoryTagResource\RelationManagers\TagsRelationManager;
use App\Models\Tenant\CategoryTag;
use App\Tables\Columns\ColorNameColumn;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CategoryTagResource extends Resource
{
    protected static ?string $model = CategoryTag::class;

    protected static ?string $pluralLabel = 'Etiquetas';

    protected static ?string $modelLabel = 'Etiqueta';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema(self::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('organization_id', auth()->user()->last_organization_id))
            ->reorderable('order')
            ->columns(self::getColumnTableSchema())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getFormSchema(): array
    {
        return [

            Section::make('Categorias das Etiquetas')
                ->schema([
                    TextInput::make('order')
                        ->label('Ordem')
                        ->numeric()
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('grupo')
                        ->label('Código grupo do produto')
                        ->numeric()
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('conta_contabil')
                        ->label('Conta Contábil')
                        ->numeric()
                        ->required()
                        ->columnSpan(1),

                    Select::make('tipo_item')
                        ->label('Tipo do item')
                        ->required()
                        ->options([

                            '0' => 'Mercadoria',
                            '1' => 'Matéria Prima',
                            '2' => 'Produto Intermediário',
                            '3' => 'Produto em Fabricação',
                            '4' => 'Produto Acabado',
                            '5' => 'Embalagem',
                            '6' => 'Subproduto',
                            '7' => 'Material de Uso e Consumo',
                            '8' => 'Ativo Imobilizado',
                            '9' => 'Serviços',
                            '10' => 'Outros Insumos',
                            '99' => 'Outros',

                        ])
                        ->columnSpan(1),

                    ColorPicker::make('color')
                        ->label('Cor')
                        ->columnSpan(1),

                    Toggle::make('is_enable')
                        ->label('Habilitado')
                        ->default(true)
                        ->required()
                        ->columnSpan(1),

                    Toggle::make('is_difal')
                        ->label('Difal')
                        ->default(false)
                        ->required()
                        ->columnSpan(1),

                    Toggle::make('is_devolucao')
                        ->label('Devolução')
                        ->default(false)
                        ->required()
                        ->columnSpan(1),

                ])->columns(3),

        ];
    }

    public static function getColumnTableSchema(): array
    {
        return [

            TextColumn::make('order')
                ->label('Ordem')
                ->sortable()
                ->searchable(),
            TextColumn::make('name')
                ->label('Nome')
                ->sortable()
                ->searchable(),
            IconColumn::make('is_difal')
                ->label('Difal')
                ->boolean(),
            ColorNameColumn::make('color')
                ->label('Cor'),
            TextColumn::make('num_tags')
                ->label('Nº Etiquetas')
                ->getStateUsing(function (Model $record) {
                    return count($record->tags);
                }),
            IconColumn::make('is_devolucao')
                ->label('Devolução')
                ->boolean(),

        ];
    }

    public static function getRelations(): array
    {
        return [
            TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoryTags::route('/'),
            'create' => Pages\CreateCategoryTag::route('/create'),
            'edit' => Pages\EditCategoryTag::route('/{record}/edit'),
        ];
    }
}
