<div>

    @if ($getState() == 0)
        <span>-</span>
    @elseif($getState() == 610110)
        <x-filament::badge color="danger"  tooltip="Prestação de serviço em desarcordo">
            PSD
        </x-filament::badge>
    @endif

</div>
