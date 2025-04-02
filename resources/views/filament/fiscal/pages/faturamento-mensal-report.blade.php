<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="p-6">
                <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                    Relatório de Faturamento Mensal
                </h2>
                <div class="flex justify-between items-center">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Visualize os dados de faturamento mensal dos últimos 12 meses.
                    </p>
                    <x-filament::button
                        color="primary"
                        wire:click="gerarDeclaracaoPdf"
                        wire:loading.attr="disabled"
                        wire:target="gerarDeclaracaoPdf"
                    >
                        Gerar Declaração de Faturamento
                    </x-filament::button>
                </div>
            </div>
        </div>

        <!-- Gráfico de Barras -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Evolução do Faturamento</h3>
                <div style="height: 350px;">
                    <canvas id="faturamento-chart"></canvas>
                </div>
                <div id="chart-data" data-chart="{{ json_encode($chartData) }}" style="display: none;"></div>
            </div>
        </div>

        <!-- Tabela de Faturamento usando Blade Components -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="p-6 pb-0">
                <h3 class="text-lg font-semibold mb-4">Detalhamento do Faturamento Mensal</h3>
            </div>

            <div class="p-6">

                <x-filament-tables::container>
                    <table class="w-full table-auto text-start fi-ta-table divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                            <x-filament-tables::header-cell>
                                Mês
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                Ano
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell class="text-right">
                                Total de Notas
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell class="text-right">
                                Valor Faturado
                            </x-filament-tables::header-cell>
                        </thead>
                        <tbody class="whitespace-nowrap divide-y divide-gray-200 dark:divide-white/5">
                            @forelse($tableData as $item)
                            <x-filament-tables::row>
                                <x-filament-tables::cell class="text-sm">
                                    {{ $this->getMesNome($item->mes, $item->ano) }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="text-sm">
                                    {{ $item->ano }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="text-sm">
                                    {{ $item->total_notas }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell class="font-semibold text-primary-600 dark:text-primary-400">
                                    {{ formatar_moeda($item->valor_total) }}
                                </x-filament-tables::cell>
                            </x-filament-tables::row>
                            @empty
                            <x-filament-tables::row>
                                <x-filament-tables::cell colspan="4" class="text-center py-6">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-filament::icon
                                            icon="heroicon-o-information-circle"
                                            class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                        <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Nenhum dado de faturamento encontrado para o período.
                                        </p>
                                    </div>
                                </x-filament-tables::cell>
                            </x-filament-tables::row>
                            @endforelse
                        </tbody>
                    </table>
                </x-filament-tables::container>

            </div>

        </div>

        <!-- Resumo Totais -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Resumo</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-primary-50 dark:bg-primary-950 rounded-lg">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total de Notas no Período
                        </div>
                        <div class="text-2xl font-bold mt-1">
                            {{ array_sum(array_column($tableData, 'total_notas')) }}
                        </div>
                    </div>
                    <div class="p-4 bg-primary-50 dark:bg-primary-950 rounded-lg">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Valor Total Faturado
                        </div>
                        <div class="text-2xl font-bold mt-1 text-primary-600 dark:text-primary-400">
                            {{ formatar_moeda(array_sum(array_column($tableData, 'valor_total'))) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('faturamento-chart').getContext('2d');

            // Dados do gráfico
            const chartDataElement = document.getElementById('chart-data');
            const chartData = JSON.parse(chartDataElement.getAttribute('data-chart'));

            // Opções de formatação para moeda brasileira
            const currencyFormatter = new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            });

            // Criar o gráfico
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return currencyFormatter.format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Faturamento: ' + currencyFormatter.format(context.raw);
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>