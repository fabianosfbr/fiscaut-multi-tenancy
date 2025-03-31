<div class="flex space-x-2">
    @if (is_string($getState()))
    <div class="text-sm">
    {{ $getState() }}
    </div>

    @else

    @foreach ($getState() as $chaveNfe)
    <div class=" text-sm rounded-lg" style="position: relative;">
        <div x-data="{ tooltip: '{{ $chaveNfe }}' }">
            <x-heroicon-o-key x-tooltip.on.click.max-width.500.placement.left.interactive.debounce.250="tooltip"
                class="cursor-pointer w-4 h-4" />
        </div>
    </div>
    @endforeach
    @endif
</div>