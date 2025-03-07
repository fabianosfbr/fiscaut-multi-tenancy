<x-filament::modal id="{{ $id }}"
    :close-button="false"
    width="{{ $width }}"
    icon="{{ $icon }}"
    icon-color="{{ $iconColor }}">

    @if ($title)
    <x-slot name="heading">
        {{ $title }}
    </x-slot>
    @endif

    @if ($description)
    <x-slot name="description">
        {{ $description }}
    </x-slot>
    @endif



    @if ($resultados)

    <div class="space-y-4">
        <div class="border-b border-gray-200 pb-3 mb-4 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">
                Resultado da Importação
            </h2>
            <button
                type="button"
                x-on:click="close"
                class="text-sm rounded-full p-2 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500">
                <span class="sr-only">Fechar</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        {{-- Resumo Geral --}}
        <div class="grid grid-cols-4 gap-3">
            <div class="rounded-lg bg-gray-100 p-3 flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-700">Total Processado</div>
                    <div class="text-lg font-semibold text-gray-800">
                        {{ ($resultados['nfe']['total'] ?? 0) + ($resultados['cte']['total'] ?? 0) }}
                    </div>
                </div>
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
        {{-- Tabs para NFe e CTe --}}
        <div x-data="{ activeTab: 'nfe' }" class="mt-4">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                    <button
                        class="px-3 py-2 text-sm font-medium"
                        :class="activeTab === 'nfe' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'nfe'">
                        NFe ({{ $resultados['nfe']['total'] ?? 0 }})
                    </button>
                    <button
                        class="px-3 py-2 text-sm font-medium"
                        :class="activeTab === 'cte' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'cte'">
                        CTe ({{ $resultados['cte']['total'] ?? 0 }})
                    </button>
                </nav>
            </div>

            <div x-show="activeTab === 'nfe'" x-cloak>
                <div class="grid grid-cols-3 gap-3 mt-4">
                    <div class="rounded-lg bg-green-100 p-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs text-green-700">Importadas</div>
                            <div class="text-lg font-semibold text-green-800">
                                {{ count($resultados['nfe']['sucessos'] ?? []) }}
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <div class="rounded-lg bg-blue-100 p-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs text-blue-700">Atualizadas</div>
                            <div class="text-lg font-semibold text-blue-800">
                                {{ count($resultados['nfe']['atualizacoes'] ?? []) }}
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>

                    <div class="rounded-lg bg-red-100 p-3 flex items-center justify-between">
                        <div>
                            <div class="text-xs text-red-700">Falhas</div>
                            <div class="text-lg font-semibold text-red-800">
                                {{ count($resultados['nfe']['falhas'] ?? []) }}
                            </div>
                        </div>
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                
            </div>

            {{-- Conteúdo CTe --}}
        <div x-show="activeTab === 'cte'" x-cloak>
            <div class="grid grid-cols-3 gap-3 mt-4">
                <div class="rounded-lg bg-green-100 p-3 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-green-700">Importados</div>
                        <div class="text-lg font-semibold text-green-800">
                            {{ count($resultados['cte']['sucessos'] ?? []) }}
                        </div>
                    </div>
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <div class="rounded-lg bg-blue-100 p-3 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-blue-700">Atualizados</div>
                        <div class="text-lg font-semibold text-blue-800">
                            {{ count($resultados['cte']['atualizacoes'] ?? []) }}
                        </div>
                    </div>
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                
                <div class="rounded-lg bg-red-100 p-3 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-red-700">Falhas</div>
                        <div class="text-lg font-semibold text-red-800">
                            {{ count($resultados['cte']['falhas'] ?? []) }}
                        </div>
                    </div>
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

        </div>

        </div>


        @else
        <p>Nenhum dado disponível.</p>
        @endif


</x-filament::modal>