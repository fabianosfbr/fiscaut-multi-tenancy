<div class="flex flex-row gap-x-1">
    @foreach ($record->tagged->take(2) as $tagged)
        @php
            $tooltip = $tagged->tag_name . ' ' . money_formatter($tagged->value);
        @endphp
        <div x-data=" { tooltip: @js($tooltip), bcolor: @js($tagged->tag->category->color) }" class="relative inline-block">
            <span x-tooltip="tooltip" :style="`background-color: ${bcolor};`"
                class="inline-block filament-badgeable-badge px-2 text-xs font-medium rounded-full py-0.5"
                style="font-size: .75em;">
                @if ($showTagCode)
                    {{ $tagged->tag->code }}
                @else
                    {{ getLabelTag($tagged->tag_name) }}
                @endif
            </span>
        </div>
    @endforeach
    @if (count($record->tagged) - 2 > 0)
        @php
            $tagsValues = [];
            $values = [];
        @endphp

        @foreach ($record->tagged as $tagged)
            @php
                $values['code'] = $tagged->tag->code;
                $values['name'] = $tagged->tag_name;
                $values['value'] = money_formatter($tagged->value);
                array_push($tagsValues, $values);
            @endphp
        @endforeach

        <div x-data="{ message: '', items: {{ @json_encode($tagsValues) }} }">
            <template x-ref="template">
                <div class="p-3">
                    <template x-for="item in items">
                        <p class="text-sm font-normal text-left text-gray-900"
                            x-text="`${item.code} - ${item.name}  ${item.value}`"></p>
                    </template>
                </div>
            </template>
            <span
                x-tooltip="{
                            content: () => $refs.template.innerHTML,
                            allowHTML: true,
                            maxWidth: 350,
                            theme: 'light',
                            interactive: true,
                            animation: 'shift-away-subtle',
                            delay: [200, 50],
                            classList: 'popover',
                            appendTo: $root
                        }"
                class="mt-2 text-xs font-medium">
                <span class="mt-2 text-xs font-medium">mais </span>
            </span>
        </div>
    @endif
</div>
