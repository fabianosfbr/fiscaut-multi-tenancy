<div>
    <x-filament::modal id="{{ $id }}"
        width="{{ $width }}"
        icon="{{ $icon }}"
        icon-color="{{ $iconColor }}"
        :slide-over="false" 
        :close-by-clicking-away="true"
        :close-by-escaping="false">

        @if ($title)
        <x-slot name="heading">
            {{ $title }}
        </x-slot>
        @endif

        @if ($description)
        <x-slot name="description">
            {{ $description }}
        </x-slot>
        @endif


     
        @if ($modalData)
        @dd($modalData)
        <ul>
            @foreach ($modalData['resultados'] as $resultado)
            <li>{{ $resultado }}</li>
            @endforeach
        </ul>
        @else
        <p>Nenhum dado disponível.</p>
        @endif

        <x-slot name="footer">
            <x-filament::button color="secondary" x-on:click="$dispatch('close-modal', { id: 'info_modal' })">
                Fechar
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>