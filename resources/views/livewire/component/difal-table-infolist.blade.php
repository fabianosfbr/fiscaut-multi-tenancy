<div>
    <div class="mb-4 p-4 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 shadow-sm">
        <h4 class="text-xl font-medium">Valor Total DIFAL</h4>
        <p class="text-2xl font-bold text-primary-600 dark:text-primary-500 mt-1">
            {{ 'R$ ' . number_format($totalDifal, 2, ',', '.') }}
        </p>
    </div>

    @if(empty($difalProdutos))
        <div class="p-4 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg">
            <p>Não há produtos com DIFAL a calcular. Isso pode ocorrer quando a alíquota de destino é igual ou menor que a alíquota de origem, ou quando não há base de cálculo para os produtos.</p>
        </div>
    @else
        <x-filament-tables::container>
            <table class="w-full table-auto text-start fi-ta-table divide-y divide-gray-200 dark:divide-white/5">
                <thead class="divide-y divide-gray-200 dark:divide-white/5">
                    <tr>
                        <x-filament-tables::header-cell class="border-x-[0.5px]">
                            Código
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell class="border-x-[0.5px]">
                            Descrição
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell>
                            NCM
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell class="border-x-[0.5px]">
                            CFOP
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell class="text-right border-x-[0.5px]">
                            Valor Contábil
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell class="text-right border-x-[0.5px]">
                            Base de Cálculo
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell class="text-right border-x-[0.5px]">
                            Alíq. Origem (%)
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell class="text-right border-x-[0.5px]">
                            Alíq. Destino (%)
                        </x-filament-tables::header-cell>
                        <x-filament-tables::header-cell class="text-right border-x-[0.5px]">
                            Valor DIFAL
                        </x-filament-tables::header-cell>
                    </tr>
                </thead>
                <tbody class="whitespace-nowrap divide-y divide-gray-200 dark:divide-white/5">
                    @foreach($difalProdutos as $item)
                    
                        <tr>
                            <x-filament-tables::cell class="text-sm">
                                {{ $item['codigo'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm">
                                {{ Str::limit($item['descricao'], 30) }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm text-right">
                                {{ $item['ncm'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm text-right">
                                {{ $item['cfop'] }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm py-2 text-right">
                                {{ 'R$ ' . number_format($item['valor_contabil'], 2, ',', '.') }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm py-2 text-right">
                                {{ 'R$ ' . number_format($item['base_calculo'], 2, ',', '.') }}
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm py-2 text-right">
                                {{ number_format($item['aliquota_origem'], 2, ',', '.') . '%' }}                               
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm py-2 text-right">
                                {{ number_format($item['aliquota_destino'], 2, ',', '.') . '%' }}                             
                            </x-filament-tables::cell>
                            <x-filament-tables::cell class="text-sm py-2 font-bold text-right">
                                {{ 'R$ ' . number_format($item['valor_difal'], 2, ',', '.') }}
                            </x-filament-tables::cell>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament-tables::container>
    @endif
</div> 