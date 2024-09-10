<div>
    <form wire:submit.prevent="submit">

        <div class="pb-4">
            {{ $this->form }}
        </div>

        <x-filament::button type="submit">
            Salvar
        </x-filament::button>
    </form>
</div>
