<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\Column;

class TagColumnDocs extends Column
{
    protected string $view = 'tables.columns.tag-column-docs';

    protected bool $mostrarCodigo = false;

    public function showTagCode(bool $mostrarCodigo): static
    {
        $this->mostrarCodigo = $mostrarCodigo;

        return $this;
    }

    public function getShowTagCode(): bool
    {
        return $this->mostrarCodigo;
    }
}
