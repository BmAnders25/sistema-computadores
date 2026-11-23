@props([
    'items',
    'sizes' => [12, 24, 48],
])

<section class="src-pagination" aria-label="Paginación de registros">
    <p class="src-pagination-summary">
        Mostrando del {{ $items->firstItem() ?? 0 }} al {{ $items->lastItem() ?? 0 }} de {{ $items->total() }} registros
    </p>

    <form class="src-pagination-size" method="GET" action="{{ url()->current() }}">
        @foreach (request()->except(['per_page', 'page']) as $key => $value)
            @if (is_array($value))
                @foreach ($value as $nestedKey => $nestedValue)
                    <input type="hidden" name="{{ $key }}[{{ $nestedKey }}]" value="{{ $nestedValue }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="src-per-page">Ver</label>
        <select id="src-per-page" name="per_page" onchange="this.form.submit()">
            @foreach ($sizes as $pageSize)
                <option value="{{ $pageSize }}" @selected($items->perPage() === $pageSize)>{{ $pageSize }}</option>
            @endforeach
        </select>
        <span>por página</span>
    </form>

    <div class="src-pagination-links">
        {{ $items->onEachSide(1)->links('vendor.pagination.src') }}
    </div>
</section>
