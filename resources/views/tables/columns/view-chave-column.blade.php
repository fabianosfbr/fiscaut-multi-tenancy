<div class="text-sm rounded-lg" style="position: relative;">

    <div x-data="{ tooltip: '{{ $getState() }}' }">
        <x-heroicon-o-key x-tooltip.on.click.max-width.500.placement.left.interactive.debounce.250="tooltip"
            class="cursor-pointer w-4 h4" />
    </div>

</div>
