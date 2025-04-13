<?php

namespace App\Filament\Contabil\Resources\LayoutResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;

class LayoutColumnsRelationManager extends RelationManager
{
    protected static string $relationship = 'layoutColumns';

    protected static ?string $title = 'Estrutura da Planilha';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('excel_column_name')
                    ->label('Nome da Coluna no Excel')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('target_column_name')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('data_type')
                    ->label('Tipo de Dado')
                    ->live()
                    ->options([
                        'text' => 'Texto',
                        'number' => 'Número',
                        'date' => 'Data',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('format')
                    ->label('Formato')
                    ->maxLength(255),

                Forms\Components\Select::make('date_adjustment')
                    ->label('Ajuste de Data')
                    ->default('same')
                    ->options([
                        'same' => 'Mesma data',
                        'd-1' => 'D-1 (dia anterior)',
                        'd+1' => 'D+1 (próximo dia)',
                    ])
                    ->required()
                    ->visible(function (Get $get) {
                        return $get('data_type') === 'date';
                    })
                    ->helperText('Define como a data será ajustada durante a importação'),

                Forms\Components\Toggle::make('is_sanitize')
                    ->label('Limpar conteúdo')
                    ->default(false)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('excel_column_name')
            ->heading('Estrutura da Planilha')
            ->description('Estrutura de colunas que serão importados do arquivo Excel')
            ->modelLabel('Coluna')
            ->pluralModelLabel('Colunas')
            ->emptyStateHeading('Nenhuma coluna cadastrada')
            ->emptyStateDescription('Quando cadastrar uma coluna ela aparecerá aqui')
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('excel_column_name')
                    ->label('Nome da Coluna no Excel'),
                Tables\Columns\TextColumn::make('target_column_name')
                    ->label('Descrição'),
                Tables\Columns\TextColumn::make('data_type')
                    ->label('Tipo de Dado'),
                Tables\Columns\TextColumn::make('format')
                    ->label('Formato'),


            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
