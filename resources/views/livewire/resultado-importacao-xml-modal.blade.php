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
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg bg-green-100 p-3 flex items-center justify-between">
                <div>
                    <div class="text-xs text-green-700">Importadas</div>
                    <div class="text-lg font-semibold text-green-800">{{ count($resultados['sucessos']) }}</div>
                </div>
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <div class="rounded-lg bg-blue-100 p-3 flex items-center justify-between">
                <div>
                    <div class="text-xs text-blue-700">Atualizadas</div>
                    <div class="text-lg font-semibold text-blue-800">{{ count($resultados['atualizacoes']) }}</div>
                </div>
                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>

            <div class="rounded-lg bg-red-100 p-3 flex items-center justify-between">
                <div>
                    <div class="text-xs text-red-700">Falhas</div>
                    <div class="text-lg font-semibold text-red-800">{{ count($resultados['falhas']) }}</div>
                </div>
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

        </div>
        <div x-data="{ activeTab: 'sucessos' }" class="mt-4">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                    @if(!empty($resultados['sucessos']))
                    <button
                        class="px-3 py-2 text-sm font-medium"
                        :class="activeTab === 'sucessos' ? 'border-b-2 border-green-500 text-green-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'sucessos'">
                        Importadas ({{ count($resultados['sucessos']) }})
                    </button>
                    @endif

                    @if(!empty($resultados['atualizacoes']))
                    <button
                        class="px-3 py-2 text-sm font-medium"
                        :class="activeTab === 'atualizacoes' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'atualizacoes'">
                        Atualizadas ({{ count($resultados['atualizacoes']) }})
                    </button>
                    @endif

                    @if(!empty($resultados['falhas']))
                    <button
                        class="px-3 py-2 text-sm font-medium"
                        :class="activeTab === 'falhas' ? 'border-b-2 border-red-500 text-red-600' : 'text-gray-500 hover:text-gray-700'"
                        @click="activeTab = 'falhas'">
                        Falhas ({{ count($resultados['falhas']) }})
                    </button>
                    @endif
                </nav>
            </div>

            {{-- Conteúdo das Tabs --}}
            <div class="mt-4">
                {{-- Tab Sucessos --}}
                <div x-show="activeTab === 'sucessos'" x-cloak>
                    @if(!empty($resultados['sucessos']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Número</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Emitente</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($resultados['sucessos'] as $sucesso)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $sucesso['numero'] }}</td>
                                    <td class="px-3 py-2">{{ $sucesso['emitente'] }}</td>
                                    <td class="px-3 py-2 text-right">R$ {{ $sucesso['valor'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- Tab Atualizações --}}
                <div x-show="activeTab === 'atualizacoes'" x-cloak>
                    @if(!empty($resultados['atualizacoes']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Número</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status Anterior</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Novo Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($resultados['atualizacoes'] as $atualizacao)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $atualizacao['numero'] }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $atualizacao['status_anterior'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $atualizacao['status_novo'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- Tab Falhas --}}
                <div x-show="activeTab === 'falhas'" x-cloak>
                    @if(!empty($resultados['falhas']))
                    <div class="space-y-2">
                        @foreach($resultados['falhas'] as $falha)
                        <div class="rounded-md bg-red-50 p-3">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">{{ $falha['arquivo'] }}</h3>
                                    <div class="mt-1 text-sm text-red-700">{{ $falha['erro'] }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


    @else
    <p>Nenhum dado disponível.</p>
    @endif


</x-filament::modal>