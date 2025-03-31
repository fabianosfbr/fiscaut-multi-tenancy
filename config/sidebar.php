<?php

use Filament\Navigation\NavigationGroup;

return [
    NavigationGroup::make()
        ->label('NFe')
        ->icon('heroicon-o-clipboard-document-check')
        ->collapsed(),
    NavigationGroup::make()
        ->label('CTe')
        ->icon('heroicon-o-truck')
        ->collapsed(),
    NavigationGroup::make()
        ->label('NFSe')
        ->icon('heroicon-o-clipboard-document')
        ->collapsed(),
    NavigationGroup::make()
        ->label('CFe')
        ->icon('heroicon-o-clipboard-document')
        ->collapsed(),
    NavigationGroup::make()
        ->label('Demais docs. fiscais')
        ->icon('heroicon-o-document-duplicate')
        ->collapsed(),
    NavigationGroup::make()
        ->label('Relatórios')
        ->icon('heroicon-o-chart-bar')
        ->collapsed(),
    NavigationGroup::make()
        ->label('Ferramentas')
        ->icon('heroicon-o-cpu-chip')
        ->collapsed(),
    NavigationGroup::make()
        ->label('Usuários')
        ->icon('heroicon-o-users')
        ->collapsed(),
    NavigationGroup::make()
        ->label('Configurações')
        ->icon('heroicon-o-cog-6-tooth')
        ->collapsed(),
];
