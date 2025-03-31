<?php

namespace App\Filament\Fiscal\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{

    public static function getNavigationLabel(): string
    {
        return 'Painel';
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getColumns(): int|string|array
    {
        return 12;
    }
}
