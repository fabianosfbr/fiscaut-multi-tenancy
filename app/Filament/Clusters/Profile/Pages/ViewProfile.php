<?php

namespace App\Filament\Clusters\Profile\Pages;

use App\Filament\Clusters\Profile;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Concerns\InteractsWithFormActions;

class ViewProfile extends BaseProfile implements HasInfolists
{
    use InteractsWithFormActions;
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static string $view = 'filament.clusters.profile.pages.view-profile';

    protected static ?string $cluster = Profile::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'Visualizar Perfil';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    protected static ?string $slug = 'me';

    public function personalInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getUser())
            ->schema([
                Section::make('Informações pessoais')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome')
                            ->columnSpan(1),
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }
}
