<x-filament-panels::page>
    <div>
        <form wire:submit="create">
            {{ $this->form }}

            <div class="mt-4 flex justify-end gap-4">
                {{ $this->saveAction }}
                {{ $this->returnAction }}
            </div>
        </form>
    </div>
</x-filament-panels::page>
