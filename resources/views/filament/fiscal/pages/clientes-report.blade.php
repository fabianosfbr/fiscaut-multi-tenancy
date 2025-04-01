<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="p-6">
                <h2 class="text-xl font-bold tracking-tight sm:text-2xl">
                    Relatório de Clientes
                </h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Visualize os dados de faturamento por cliente, ordenados por valor total.
                </p>
            </div>
        </div>

        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>