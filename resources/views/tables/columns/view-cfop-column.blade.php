<div>
    @if (count($getRecord()->products) > 0)
        @php
            $cfops = [];
        @endphp
        @foreach ($getRecord()->products as $product)
            @php
                if (!in_array($product->cfop, $cfops, true)) {
                    array_push($cfops, $product->cfop);
                }
            @endphp
        @endforeach

        @foreach ($cfops as $value)
            <span class="text-sm">
                {{ $value }}
            </span>
        @endforeach


    @endif

</div>
