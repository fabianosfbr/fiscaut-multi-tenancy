<div>
    @php
        $status = $getRecord()->status_nota->value;
        $manifestacao = isset($getRecord()->status_manifestacao->value) ? $getRecord()->status_manifestacao->value : 0;
    @endphp

    @if ($status == 100 and $manifestacao == 0 || $manifestacao == 210210 || $manifestacao == 210200)
        <!-- Possui etiqueta atribuida então mostra -->
        @if (count($getRecord()->tagged) > 0)
            <x-tagged-summary :record="$getRecord()" :showTagCode="$getShowTagCode()" />
        @else
            @php
                $results = $getRecord()->tagging_summary;
            @endphp

            <x-tagging-summary :results="$results" :record="$getRecord()" />
        @endif
    @endif
</div>
