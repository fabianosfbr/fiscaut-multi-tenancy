<div>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-4 flex justify-end">
            <x-filament::button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save">
                Salvar Acumuladores
            </x-filament::button>
        </div>
    </form>
</div> 