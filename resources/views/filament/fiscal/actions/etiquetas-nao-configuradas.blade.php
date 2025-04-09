<div class="space-y-4">

    @if(count($notas) > 0)

    <div class="text-red-600 font-medium text-lg">
        Atenção! Existem notas fiscais com etiquetas não configuradas
    </div>

    <div class="text-gray-600 mb-4">
        As seguintes notas possuem etiquetas que não estão configuradas.
        Por favor, configure as etiquetas faltantes nas configurações da empresa antes de gerar o arquivo.
    </div>

    <div class="border rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Número da Nota
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Emitente
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Etiqueta
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Configuração
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($notas as $nota)
                @foreach($nota['etiquetas'] as $index => $etiqueta)
                <tr>
                    @if($index === 0)
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        {{ $nota['numero'] }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $nota['nome_emitente'] }}
                    </td>
                    @endif
                    <td class="px-4 py-3 text-sm text-gray-700">
                        {{ $etiqueta['nome'] }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            @if($etiqueta['falta_acumulador'])
                            <x-filament::badge color="warning">
                                Acumulador
                            </x-filament::badge>
                            @endif

                            @if($etiqueta['falta_cfop'])
                            <x-filament::badge color="warning">
                                CFOP
                            </x-filament::badge>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-gray-600 text-sm">
        <p class="font-medium mb-2">Para configurar as etiquetas, acesse:</p>

        <div class="space-y-2">
            <div class="flex items-center space-x-2">
                <span>Menu Configurações > Configurações Gerais > Entrada > CFOPs</span>
            </div>
            <div class="flex items-center space-x-2">
                <span>Menu Configurações > Configurações Gerais > Entrada > Acumuladores</span>
            </div>
        </div>
    </div>



    @endif



</div>