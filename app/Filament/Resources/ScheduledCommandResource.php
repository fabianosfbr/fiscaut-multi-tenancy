<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Forms\Components;
use Filament\Tables\Table;
use App\Models\ScheduledCommand;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ScheduledCommandResource\Pages;
use App\Filament\Resources\ScheduledCommandResource\RelationManagers;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;

class ScheduledCommandResource extends Resource
{
    protected static ?string $model = ScheduledCommand::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Comandos Agendados';
    protected static ?string $pluralLabel = 'Comandos Agendados';
    protected static ?string $modelLabel = 'Comando Agendado';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuração do Comando')
                    ->schema([
                        Forms\Components\Select::make('command')
                            ->label('Comando Artisan')
                            ->options(self::getAvailableCommands())
                            ->searchable()
                            ->required(),


                        Forms\Components\Select::make('preset')
                            ->label('Frequência')
                            ->options([
                                'every_minute' => 'A cada minuto',
                                'every_five_minutes' => 'A cada 5 minutos',
                                'hourly' => 'A cada hora',
                                'daily' => 'Diariamente (com horário)',
                                'weekly' => 'Semanalmente (com horário)',
                                'monthly' => 'Mensalmente (com horário)',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (in_array($state, ['every_minute', 'every_five_minutes', 'hourly'])) {
                                    $set('time', null);
                                }
                            })
                            ->required(),


                        Forms\Components\Textarea::make('arguments')
                            ->label('Argumentos (JSON ou linha de comando)')
                            ->helperText('Exemplo JSON: {"user": 1} ou linha: --user=1')
                            ->rows(2)
                            ->nullable(),

                        Forms\Components\TimePicker::make('time')
                            ->label('Horário de Execução')
                            ->seconds(false)
                            ->visible(fn(Forms\Get $get) => in_array($get('preset'), ['daily', 'weekly', 'monthly']))
                            ->reactive(),

                        Forms\Components\Toggle::make('enabled')
                            ->label('Ativo')
                            ->default(true)
                            ->columnSpanFull()
                            ->inline(false),
                    ])
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('command')
                    ->label('Comando'),

                Tables\Columns\TextColumn::make('preset')
                    ->label('Frequência')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'every_minute' => 'A cada minuto',
                        'every_five_minutes' => 'A cada 5 minutos',
                        'hourly' => 'A cada hora',
                        'daily' => 'Diariamente',
                        'weekly' => 'Semanalmente',
                        'monthly' => 'Mensalmente',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('time')
                    ->label('Horário'),

                Tables\Columns\TextColumn::make('arguments')
                    ->label('Argumentos')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\IconColumn::make('enabled')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Última Execução')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListScheduledCommands::route('/'),
            'create' => Pages\CreateScheduledCommand::route('/create'),
            'edit' => Pages\EditScheduledCommand::route('/{record}/edit'),
        ];
    }

    /**
     * Lista de comandos disponíveis no Artisan.
     */
    protected static function getAvailableCommands(): array
    {
        return collect(Artisan::all())
            ->mapWithKeys(fn($cmd, $name) => [$name => $name])
            ->sort()
            ->toArray();
    }

    /**
     * Presets de cron mais usados.
     */
    protected static function getCronPresets(): array
    {
        return [
            '* * * * *' => 'A cada minuto',
            '*/5 * * * *' => 'A cada 5 minutos',
            '0 * * * *' => 'A cada hora',
            '0 */6 * * *' => 'A cada 6 horas',
            '0 0 * * *' => 'Diariamente',
            '0 0 * * 0' => 'Semanalmente',
            '0 0 1 * *' => 'Mensalmente',
        ];
    }
}
