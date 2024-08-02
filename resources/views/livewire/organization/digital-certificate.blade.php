<div><x-filament-panels::form wire:submit="updateOrganizationName">
        <!-- Company Owner Information -->
        <x-filament-forms::field-wrapper.label>
            Owner
        </x-filament-forms::field-wrapper.label>



        <!-- Company Name -->
        <x-filament-forms::field-wrapper id="name" statePath="name" required="required"
            label="Nome">
            <x-filament::input.wrapper class="overflow-hidden">
                <x-filament::input id="name" type="text" maxlength="255" wire:model="state.razao_social" />
            </x-filament::input.wrapper>
        </x-filament-forms::field-wrapper>

        <div class="text-left">
            <x-filament::button type="submit">
                Salvar
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</div>
