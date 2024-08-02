<p class="text-sm bg-gray-100 rounded-lg" style="position: relative;">

    @php
        $chaves = json_decode($getRecord()->nfe_chave, true);
    @endphp

    @if (!is_null($chaves))

        @foreach ($chaves as $key => $chave)
            @if (is_string($chave) && $key == 'chave')
                <div x-data="{ tooltip: '{{ $chave }}' }">
                    <x-heroicon-o-key x-tooltip.on.click.max-width.500.placement.left.interactive.debounce.250="tooltip"
                        class="cursor-pointer w-4 h4" />
                </div>
            @else
                @if (is_array($chave))
                    @foreach ($chave as $key => $value)
                        @if ($key == 'chave')
                            <div x-data="{ tooltip: '{{ $value }}' }">
                                <x-heroicon-o-key
                                    x-tooltip.on.click.max-width.500.placement.left.interactive.debounce.250="tooltip"
                                    class="w-4 h4" />
                            </div>
                        @endif
                    @endforeach
                @endif
            @endif
        @endforeach
    @endif
</p>
