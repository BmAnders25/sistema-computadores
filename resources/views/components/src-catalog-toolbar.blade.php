@props([
    'filters' => [],
    'searchPlaceholder' => 'Buscar...',
    'queryName' => 'estado',
    'selectedFilter' => null,
])

@php
    $selectedFilter = $selectedFilter ?? request()->query($queryName, '');
@endphp

<form class="src-catalog-toolbar" aria-label="Filtros y controles del listado" action="{{ url()->current() }}" method="GET" data-catalog-server-form data-catalog-query-name="{{ $queryName }}">
    @foreach (request()->except([$queryName, 'search', 'page']) as $key => $value)
        @if (is_array($value))
            @foreach ($value as $nestedKey => $nestedValue)
                <input type="hidden" name="{{ $key }}[{{ $nestedKey }}]" value="{{ $nestedValue }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <input type="hidden" name="{{ $queryName }}" value="{{ $selectedFilter }}" data-catalog-state>

    @if (count($filters))
        <div class="src-catalog-tabs" role="group" aria-label="Filtrar resultados">
            @foreach ($filters as $filter)
                @php
                    $queryValue = $filter['query'] ?? ($filter['value'] === 'all' ? '' : $filter['value']);
                    $selected = (string) $selectedFilter === (string) $queryValue;
                @endphp
                <button
                    type="button"
                    class="src-catalog-tab {{ $selected ? 'is-active' : '' }}"
                    data-catalog-filter="{{ $filter['value'] }}"
                    data-catalog-query-value="{{ $queryValue }}"
                    aria-pressed="{{ $selected ? 'true' : 'false' }}"
                >
                    {{ $filter['label'] }}
                    @isset($filter['count'])
                        <span>{{ $filter['count'] }}</span>
                    @endisset
                </button>
            @endforeach
        </div>
    @endif

    <div class="src-catalog-controls">
        {{ $slot }}

        <label class="src-catalog-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span class="sr-only">{{ $searchPlaceholder }}</span>
            <input type="search" name="search" data-catalog-search value="{{ request()->query('search', '') }}" placeholder="{{ $searchPlaceholder }}" autocomplete="off">
        </label>

        <div class="src-catalog-export">
            <button type="button" class="src-catalog-export-toggle" data-catalog-export-toggle aria-expanded="false">
                <i class="fas fa-download" aria-hidden="true"></i> Exportar
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="src-catalog-export-menu" data-catalog-export-menu role="menu" hidden>
                <button type="button" role="menuitem" data-catalog-export="excel">
                    <i class="far fa-file-excel" aria-hidden="true"></i> Excel
                </button>
                <button type="button" role="menuitem" data-catalog-export="pdf">
                    <i class="far fa-file-pdf" aria-hidden="true"></i> PDF
                </button>
                <button type="button" role="menuitem" data-catalog-export="csv">
                    <i class="fas fa-file-csv" aria-hidden="true"></i> CSV
                </button>
                <button type="button" role="menuitem" data-catalog-export="print">
                    <i class="fas fa-print" aria-hidden="true"></i> Imprimir
                </button>
            </div>
        </div>

        <div class="src-catalog-view-toggle" role="group" aria-label="Tipo de vista">
            <button type="button" class="is-active" data-catalog-view="grid" aria-label="Vista en tarjetas" aria-pressed="true">
                <i class="fas fa-th-large" aria-hidden="true"></i>
            </button>
            <button type="button" data-catalog-view="list" aria-label="Vista en lista" aria-pressed="false">
                <i class="fas fa-list" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</form>
