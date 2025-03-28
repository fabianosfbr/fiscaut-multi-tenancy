<?php

namespace App\Livewire\Component;

use Livewire\Component;
use App\Models\Tenant\NotaFiscalEletronica;

class DifalTableInfolist extends Component
{
    public ?NotaFiscalEletronica $record = null;
    
    public function render()
    {
        // Calcula os valores de DIFAL
        $difalProdutos = [];
        $totalDifal = 0;
        
        if ($this->record) {
            $difalProdutos = $this->record->calcularDifalProdutos();
            $totalDifal = $this->record->calcularTotalDifal();
        }
        
        return view('livewire.component.difal-table-infolist', [
            'difalProdutos' => $difalProdutos,
            'totalDifal' => $totalDifal
        ]);
    }
} 