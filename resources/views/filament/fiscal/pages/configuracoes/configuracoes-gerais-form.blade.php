<div>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-3 flex justify-end">
            <x-filament::button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save">
                Salvar Configurações                
            </x-filament::button>
        </div>
    </form>
</div>