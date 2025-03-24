<x-filament-panels::page>
    @if($processando ?? false)
        <div class="mb-4">
            <div class="border border-warning-500 bg-warning-50 dark:bg-warning-950 dark:border-warning-700 text-warning-700 dark:text-warning-300 p-4 rounded-lg">
                <div class="flex items-center">
                    <x-heroicon-o-clock class="w-5 h-5 mr-3" />
                    <div>
                        <span class="font-medium">Importação em andamento.</span> 
                        A página será atualizada automaticamente a cada 15 segundos.
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Script para atualizar a página automaticamente --}}
        <script>
            setTimeout(function() {
                window.location.reload();
            }, 15000); // 15 segundos
        </script>
    @endif

    <x-filament::section>
        <div class="space-y-6">
            <div class="flex justify-between">
                <div>
                    <h2 class="text-xl font-bold tracking-tight">
                        Importação SIEG
                    </h2>

                    <p class="mt-1 text-gray-500 dark:text-gray-400">
                        Selecione o período, tipo de documento e outras opções para iniciar a importação.
                    </p>
                </div>
            </div>

            {{ $this->form }}
            
            <x-filament-panels::form.actions :actions="$this->getFormActions()" />
        </div>
    </x-filament::section>

    <div class="mt-8">
        <x-filament::section>
            <x-slot name="heading">Histórico de Importações</x-slot>            
            {!! $historicoImportacoes !!}
        </x-filament::section>
    </div>
</x-filament-panels::page>
