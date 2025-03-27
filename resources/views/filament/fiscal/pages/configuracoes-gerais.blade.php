<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save">
                <div class="flex items-center gap-x-2">
                    Salvar Configurações
                    <span wire:loading wire:target="save" class="flex items-center gap-x-2">
                        <x-filament::loading-indicator class="h-4 w-4" />
                    </span>
                </div>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>