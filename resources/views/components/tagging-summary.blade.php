<div class="relative">
    <div x-data="{ open: false }">
        <div x-cloak x-show="open" @click.outside="open = !open" style="position: absolute; right: 30px;"
            class="bg-gray-200 absolute px-4 py-2 flex justify-center items-center space-x-4 shadow-2xl rounded dark:bg-gray-800">
            <div class="relative overflow-x-auto">
                <div class="pt-3 pb-3 flex items-center justify-center font-semibold uppercase">
                    Etiquetas aplicadas
                </div>

                @if (count($results) > 0)
                    <table class="w-full mb-2 text-sm text-left text-gray-500 dark:text-gray-400 ">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-600 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-2">
                                    Etiqueta
                                </th>
                                <th scope="col" class="px-6 py-2 content-center">
                                    Qtde
                                </th>
                                <th scope="col" class="px-6 py-2 content-center">
                                    Ação
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $tag)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <th scope="row"
                                        class="px-6 py-1 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $tag->code }} - {{ Str::limit($tag->tag_name, 30) }}
                                    </th>
                                    <td class="px-6 py-1 pr-3">
                                        {{ $tag->qtde }}
                                    </td>
                                    <td class="px-6 py-1 pr-3">
                                        <div x-data="{ tooltip: 'Aplicar etiqueta', tag: @js($tag->tag_id), nfe: @js($record->id) }">
                                            <x-heroicon-o-check-circle x-tooltip="tooltip" x-on:click="open = !open"
                                                @click="$dispatch('apply-tag-nfe', { tag: tag, nfe: nfe })"
                                                class="cursor-pointer w-4 h-4" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="flex items-center justify-center">
                        Não há etiquetas aplicadas
                    </div>
                @endif
            </div>
        </div>
        <x-heroicon-o-tag x-on:click="open = !open" class="cursor-pointer w-6 h-6" />
    </div>
</div>
