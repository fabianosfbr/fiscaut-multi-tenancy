<div>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Salvar CFOPs
            </x-filament::button>
        </div>
    </form>
</div> 