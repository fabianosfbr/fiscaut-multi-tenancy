<?php

namespace App\Filament\Clusters\Profile\Pages;

use App\Filament\Clusters\Profile;
use App\Models\Tenant\PaymentLog as PaymentLogModel;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class PaymentLog extends BaseProfile implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'Pagamentos';

    protected static ?string $slug = 'me/payment';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.clusters.profile.pages.payment-log';

    protected static ?string $cluster = Profile::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(PaymentLogModel::query())
            ->columns([
                TextColumn::make('package_name')
                    ->label('Nome do Pacote'),
                TextColumn::make('status')
                    ->label('Status'),
                TextColumn::make('start_date')
                    ->label('Data Início'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['admin', 'super-admin']);
    }
}
