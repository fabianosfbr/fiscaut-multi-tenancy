<x-filament-panels::page>
    <section class="">
        <header class="flex items-center gap-x-3 overflow-hidden py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('themes::themes.primary_color') }}
                </h3>

                <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                    {{ __('themes::themes.select_base_color') }}
                </p>
            </div>
        </header>

        <div class="flex items-center gap-4 border-t py-6">
            @if ($this->getCurrentTheme() instanceof \Hasnayeen\Themes\Contracts\HasChangeableColor)
                @foreach ($this->getColors() as $name => $color)
                    <button
                        wire:click="setColor('{{ $name }}')"
                        @class([
                            'w-4 h-4 rounded-full',
                            'ring p-1 border' => $this->getColor() === $name,
                        ])
                        style="background-color: rgb({{ $color[500] }});">
                    </button>
                @endforeach
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <input type="color" id="custom" name="custom" class="w-4 h-4" wire:change="setColor($event.target.value)" value="" />
                    <label for="custom">{{ __('themes::themes.custom') }}</label>
                </div>
            @else
                <p class="text-gray-700 dark:text-gray-400">{{ __('themes::themes.no_changing_primary_color') }}</p>
            @endif
        </div>
    </section>


</x-filament-panels::page>
