<div>

    @if ($getState())
        @foreach ($getState() as $value)
            <div class="flex items-center" x-data="{ tooltip: 'Carta de Correção' }">

                <x-filament::icon wire:key="{{ $value }}" icon="fluentui-notepad-16-o"
                    wire:target="gerarCartaCorrecao({{ $value }})" x-tooltip="tooltip"
                    wire:loading.class="opacity-50" class="h-5 w-5 py-0.5 text-gray-500 dark:text-gray-400 cursor-pointer"
                    wire:click="gerarCartaCorrecao({{ $value }})" />

            </div>
        @endforeach

    @endif

</div>
