<?php

namespace App\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;

class TagColumnNfe extends Column
{
    protected string $view = 'tables.columns.tag-column-nfe';

    protected bool $mostrarCodigo = false;

    public function showTagCode(bool|Closure $mostrarCodigo): static
    {
        if ($mostrarCodigo instanceof Closure) {
            $this->mostrarCodigo = $mostrarCodigo();
        } else {
            $this->mostrarCodigo = $mostrarCodigo;
        }

        return $this;
    }

    public function getShowTagCode(): bool
    {
        return $this->mostrarCodigo;
    }
}
