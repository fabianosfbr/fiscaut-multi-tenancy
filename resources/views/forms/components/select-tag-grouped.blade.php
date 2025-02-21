<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :id="$getId()">

    <div x-data="{
        initTomSelect() {
            let select = new TomSelect($refs.tomSelect, {
                hideSelected: false,
                plugins: {
                    remove_button: {
                        title: 'Remover este item',
                    }
                },
                onChange: (value) => {
                    @this.set('{{ $getStatePath() }}', value);
                    adjustHeight(select);
                },
                onFocus: function() {
                    this.setTextboxValue(''); // Limpa o campo de busca ao focar
                },
                render: {
                    no_results: function(data, escape) {
                        return '<span>Nenhum resultado encontrado</span>';
                    },

                }
            });

            adjustHeight(select);

            function adjustHeight(select) {
                let control = select.wrapper.querySelector('.ts-control');

                let selectedItems = Math.max(1, select.items.length);
                let baseHeight = 18; // Altura mínima
                let itemHeight = 20; // Altura por item selecionado
                let maxHeight = 150; // Altura máxima antes de ativar o scroll

                let newHeight = baseHeight + (selectedItems * itemHeight);

                control.style.height = Math.min(newHeight, maxHeight) + 'px';
            }
        }
    }" x-init="initTomSelect">

        <div wire:ignore>
            <select x-ref="tomSelect" placeholder="Selecione uma opção" autocomplete="off"
                @if ($getMultiple()) multiple @endif>
                @foreach ($getOptions() as $key => $option)
                    <option value="" hidden>Selecione uma opção</option>
                    @if (isset($option['children']))
                        <optgroup label="{{ $option['text'] }}">
                            @foreach ($option['children'] as $child)
                                <option value="{{ $child['id'] }}"
                                    {{ is_array($getState()) ? (in_array($child['id'], $getState()) ? 'selected' : '') : ($child['id'] == $getState() ? 'selected' : '') }}>
                                    {{ $child['name'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @else
                        <option value="{{ $option['id'] }}"
                            {{ is_array($getState()) ? (in_array($option['id'], $getState()) ? 'selected' : '') : ($option['id'] == $getState() ? 'selected' : '') }}>
                            {{ $option['name'] }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>
</x-dynamic-component>
