<div class="text-sm text-gray-500 text-right">
    @php
        $routeName = Route::current()->getName();
    @endphp

    {{ $routeName }}
</div>