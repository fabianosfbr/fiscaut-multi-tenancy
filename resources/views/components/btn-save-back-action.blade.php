<div class="flex gap-4 text-left mt-6">
    @php
        $tenant = filament()->getTenant();
    @endphp
    <x-filament::button href="/app/{{ $tenant->id }}" tag="a">
        Voltar
    </x-filament::button>
    <x-filament::button type="submit">
        Salvar
    </x-filament::button>
</div>
