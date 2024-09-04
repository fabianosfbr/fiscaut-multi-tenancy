<div>
    <div class="flex justify-start">
        <form class="w-1/2 pt-4" wire:submit.prevent="submit">
            {{ $this->form }}

        </form>



        @if (auth()->user()->hasRole('admin'))
            <x-filament::dropdown class="pt-6 px-2">
                <x-slot name="trigger">
                    {{-- <x-filament::button icon="heroicon-o-ellipsis-vertical" outlined color="gray">
                </x-filament::button> --}}
                    <x-filament::icon-button icon="heroicon-m-adjustments-horizontal" label="Editar empresa" />
                </x-slot>

                <x-filament::dropdown.list>
                    <x-filament::dropdown.list.item href="{{ route('filament.client.pages.edit-organization') }}"
                        tag="a">
                        Editar empresa
                    </x-filament::dropdown.list.item>
                </x-filament::dropdown.list>

                <x-filament::dropdown.list>
                    <x-filament::dropdown.list.item href="{{ route('filament.client.pages.new-organization') }}"
                        tag="a">
                        Cadastrar nova empresa
                    </x-filament::dropdown.list.item>

                </x-filament::dropdown.list>
            </x-filament::dropdown>
        @endif

    </div>

</div>
