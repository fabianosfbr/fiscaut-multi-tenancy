<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="flex flex-row gap-x-2" x-data="{ state: $wire.$entangle('{{ $getStatePath() }}'), url: '{{ route('download.file', ['document' => $getRecord()]) }}' }">
        <p class="text-sm">Download Arquivo</p>
        <a target="_blank" x-bind:href="url">
            @svg('heroicon-o-arrow-down-tray', 'w-5, h-5 cursor-pointer')
        </a>
    </div>
</x-dynamic-component>
