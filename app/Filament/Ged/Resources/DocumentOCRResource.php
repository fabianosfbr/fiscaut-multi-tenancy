<?php

namespace App\Filament\Ged\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Tenant\DocumentOCR;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Ged\Resources\DocumentOCRResource\Pages;
use App\Filament\Ged\Resources\DocumentOCRResource\RelationManagers;

class DocumentOCRResource extends Resource
{
    protected static ?string $model = DocumentOCR::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documentos Enviados';

    protected static ?string $slug = 'documentos-enviados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Documento')
                    ->schema([
                        Forms\Components\TextInput::make('beneficiario_razao_social')
                            ->label('Beneficiário')
                            ->required(),
                        Forms\Components\TextInput::make('beneficiario_cnpj')
                            ->label('CNPJ Beneficiário')
                            ->required(),
                        Forms\Components\TextInput::make('pagador_razao_social')
                            ->label('Pagador')
                            ->required(),
                        Forms\Components\TextInput::make('pagador_cnpj')
                            ->label('CNPJ Pagador')
                            ->required(),
                        Forms\Components\TextInput::make('valor')
                            ->label('Valor')
                            ->formatStateUsing(function ($state) {                              
                                return number_format($state, 2, ',', '.');
                            })
                            ->dehydrateStateUsing(function ($state) {
                                $state = str_replace('.', '', $state);
                                
                                return str_replace(',', '.', $state);
                            })
                            ->required(),
                        Forms\Components\TextInput::make('vencimento')
                            ->label('Vencimento')
                            ->formatStateUsing(function ($state) {
                                return \Carbon\Carbon::parse($state)->format('d/m/Y');
                            })
                            ->dehydrateStateUsing(function ($state) {
                                return \Carbon\Carbon::createFromFormat('d/m/Y', $state)->format('Y-m-d');
                            })
                            ->required(),
                        Forms\Components\TextInput::make('linha_digitavel')
                            ->label('Linha Digitável')
                            ->required(),

                    ])->columns(2)

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('beneficiario_razao_social')
                    ->label('Beneficiário')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getListLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('beneficiario_cnpj')
                    ->label('CNPJ Beneficiário')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('pagador_razao_social')
                    ->label('Pagador')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getListLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('pagador_cnpj')
                    ->label('CNPJ Pagador')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('vencimento')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('valor')
                    ->money('BRL'),
                // Tables\Columns\TextColumn::make('linha_digitavel')
                //     ->label('Linha Digitável')
                //     ->wrap(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view')
                    ->label('Visualizar')
                    ->icon('heroicon-o-eye')
                    ->url(fn(DocumentOCR $record): string => route('document-ocr.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentOCRS::route('/'),
            // 'create' => Pages\CreateDocumentOCR::route('/create'),
            'edit' => Pages\EditDocumentOCR::route('/{record}/edit'),
        ];
    }
}
