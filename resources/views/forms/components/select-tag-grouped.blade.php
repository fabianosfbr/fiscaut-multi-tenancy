<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :id="$getId()">

    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }" x-init="const seletTom = new TomSelect($refs.selectTom, {
        hideSelected: false,
        plugins: ['remove_button'],
        onChange: function() {
            $wire.set('{{ $getStatePath() }}', this.getValue());
        },
        render: {
            no_results: function(data, escape) {
                return '<span>Nenhum resultado encontrado</span>';
            },
        }
    })">

        <div wire:ignore>
            <select x-ref="selectTom" placeholder="Selecione uma opção" autocomplete="off"
                @if ($getMultiple()) multiple @endif>48
                @foreach ($getOptions() as $key => $option)
                    <option value="" hidden>Selecione uma opção</option>
                    <optgroup label="{{ $option['text'] }}">
                        @foreach ($option['children'] as $child)
                            <option value="{{ $child['id'] }}" wire:key="brand_id-{{ $child['id'] }}">
                                {{ $child['name'] }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    </div>
</x-dynamic-component>
