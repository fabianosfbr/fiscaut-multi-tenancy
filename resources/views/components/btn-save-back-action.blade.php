<div class="flex gap-4 text-left mt-6">
    @php
        $tenant = getTenant();
    @endphp
    <x-filament::button type="submit">
        Salvar
    </x-filament::button>
    <x-filament::button href="{{ route('filament.client.pages.dashboard') }}" color="warning" tag="a">
        Voltar
    </x-filament::button>

</div>
