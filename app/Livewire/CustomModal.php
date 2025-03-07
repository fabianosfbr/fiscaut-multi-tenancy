<?php

namespace App\Livewire;

use Livewire\Component;

class CustomModal extends Component
{
    public bool $showModal = false;
    public ?string $title = null;
    public ?string $description = null;
    public ?string $icon = null;
    public ?string $iconColor = null;
    public ?string $width = 'md';
    public string $id;
    public $modalData = [];

    protected $listeners = ['openModal' => 'open']; // Listener para o evento 'openModal'

    public function mount(
        string $id,
        ?string $title = null,
        ?string $description = null,
        ?string $icon = null,
        ?string $iconColor = null,
        ?string $width = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->icon = $icon;
        $this->iconColor = $iconColor;
    }

    public function open($data): void // Método chamado quando o evento 'openModal' é disparado
    {
        $this->modalData = $data; // Armazena os dados recebidos na propriedade $modalData
        $this->showModal = true;
        $this->dispatch('open-modal', id: $this->id); // Exibe o modal
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }


    public function render()
    {
        return view('livewire.custom-modal');
    }
}
