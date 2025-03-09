<div class="flex gap-4 text-left mt-6">
    @php
        $tenant = getOrganizationCached();
    @endphp
    <x-filament::button type="submit">
        Salvar
    </x-filament::button>
    <x-filament::button href="{{ route('filament.client.pages.dashboard') }}"  tag="a">
        Voltar
    </x-filament::button>

</div>
