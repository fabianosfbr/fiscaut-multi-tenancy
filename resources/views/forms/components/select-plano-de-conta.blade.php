<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">


    <div x-data="{
        selectedOption: $wire.$entangle('{{ $getStatePath() }}'),

        fetchItemData(query, callback) {

            // Garantir que sempre haverá um parâmetro de consulta
            const url = `{{ $getApiEndpoint() }}?query=${encodeURIComponent(query)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => callback(data))
                .catch(() => callback([])); // Em caso de erro, retorna uma lista vazia
        },
        initTomSelect() {
            tomSelectInstance = new TomSelect($refs.tomSelect, {
                hideSelected: false,
                plugins: ['remove_button'],
                valueField: 'codigo', // Campo da resposta da API que representa o valor
                labelField: 'nome', // Campo da resposta da API que representa o rótulo
                searchField: ['codigo', 'nome'],
                onChange: (value) => {
                    @this.set('{{ $getStatePath() }}', value);
                },
                load: (query, callback) => {
                    if (!query.length) return callback();
                    this.fetchItemData(query, callback);


                },
                render: {
                    option: (item) => {

                        return `<div>${item.codigo} | ${item.nome}</div>`;
                    },
                    item: (item) => {

                        return `<div>${item.codigo} | ${item.nome}</div>`;
                    }
                },
                onInitialize: () => {
                    if (this.selectedOption) {
                        this.fetchItemData(this.selectedOption, (data) => {
                            if (data.length > 0) {
                                tomSelectInstance.addOption(data[0]);
                                tomSelectInstance.setValue(this.selectedOption);

                            }
                        });

                    }
                }
            });

            $watch('selectedOption', value => {
                this.fetchItemData(value, (data) => {
                    if (data.length > 0) {
                        tomSelectInstance.addOption(data[0]);
                        tomSelectInstance.setValue(value);

                    }
                });
            });
        }
    }" x-init="initTomSelect">

        <div wire:ignore>
            <select x-ref="tomSelect" autocomplete="off" placeholder="Selecione uma opção...">
            </select>
        </div>
    </div>
</x-dynamic-component>
