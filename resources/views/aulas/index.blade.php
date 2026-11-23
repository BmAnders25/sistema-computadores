@extends('adminlte::page')

@section('title', 'Aulas y Laboratorios')

@section('content_header')
    <x-src-page-hero
        eyebrow="Inventario"
        title="Aulas y laboratorios"
        description="Administra los espacios institucionales, su capacidad y los equipos asociados."
        icon="fas fa-building" />
@stop

@section('content')
    @php
        $catalogHeaders = ['Bloque', 'Laboratorio / Aula', 'Capacidad', 'Equipos', 'Estado'];
    @endphp

    <div
        class="src-catalog-page"
        data-catalog-page
        data-catalog-title="Aulas y laboratorios · S.R.C"
        data-catalog-filename="aulas-laboratorios"
    >
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        <div class="src-catalog-create-row">
            @can('crear-aulas')
                <a class="src-catalog-create-button" href="{{ route('aulas.create') }}">
                    <i class="fas fa-plus" aria-hidden="true"></i> Nueva Aula / Laboratorio
                </a>
            @endcan
        </div>

        <x-src-catalog-toolbar :filters="[
            ['label' => 'Todas', 'value' => 'all', 'query' => '', 'count' => $totalAulas],
            ['label' => 'Activas', 'value' => 'active', 'query' => 'activo', 'count' => $activas],
            ['label' => 'Mantenimiento', 'value' => 'maintenance', 'query' => 'mantenimiento', 'count' => $mantenimiento],
            ['label' => 'Inactivas', 'value' => 'inactive', 'query' => 'inactivo', 'count' => $inactivas],
        ]" :selected-filter="request()->query('estado', '')" search-placeholder="Buscar por bloque, laboratorio...">
        </x-src-catalog-toolbar>

        <section
            class="src-catalog-grid"
            data-catalog-grid
            data-catalog-headers="{{ json_encode($catalogHeaders) }}"
            aria-label="Aulas y laboratorios registrados"
        >
            @forelse ($aulas as $aula)
                @php
                    $estadoFiltro = in_array($aula->estado, ['activo', 'disponible'], true)
                        ? 'active'
                        : ($aula->estado === 'mantenimiento' ? 'maintenance' : 'inactive');
                    $estadoLabel = in_array($aula->estado, ['activo', 'disponible'], true)
                        ? 'Activa'
                        : ($aula->estado === 'mantenimiento' ? 'Mantenimiento' : 'Inactiva');
                    $estadoClass = $estadoFiltro === 'active' ? 'active' : ($estadoFiltro === 'maintenance' ? 'pending' : 'inactive');
                    $equipoCount = $aula->computadores_count ?? 0;
                    $capacidadLabel = $aula->capacidad ? $aula->capacidad . ' estudiantes' : 'No especificada';
                    $searchText = \Illuminate\Support\Str::lower($aula->bloque . ' ' . $aula->nombre);
                    $catalogRecord = [$aula->bloque, $aula->nombre, $capacidadLabel, $equipoCount, $estadoLabel];
                @endphp
                <article
                    class="src-catalog-card"
                    data-catalog-card
                    data-catalog-status="{{ $estadoFiltro }}"
                    data-catalog-search="{{ $searchText }}"
                    data-catalog-record="{{ json_encode($catalogRecord, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                >
                    <header class="src-catalog-card-header">
                        <div class="src-catalog-card-heading">
                            <span class="src-catalog-icon"><i class="fas fa-door-open" aria-hidden="true"></i></span>
                            <div>
                                <h2>{{ $aula->nombre }}</h2>
                                <p>Espacio académico</p>
                            </div>
                        </div>
                        <span class="src-catalog-status src-catalog-status-{{ $estadoClass }}">
                            <span aria-hidden="true"></span>{{ $estadoLabel }}
                        </span>
                    </header>

                    <div class="src-catalog-card-body">
                        <div class="src-catalog-facts">
                            <div class="src-catalog-fact">
                                <span>Bloque</span>
                                <strong>{{ $aula->bloque }}</strong>
                            </div>
                            <div class="src-catalog-fact">
                                <span>Capacidad</span>
                                <strong>{{ $capacidadLabel }}</strong>
                            </div>
                            <div class="src-catalog-fact">
                                <span>Equipos asociados</span>
                                <strong>{{ $equipoCount }} computadores</strong>
                            </div>
                            <div class="src-catalog-fact">
                                <span>Estado del espacio</span>
                                <strong>{{ $estadoLabel }}</strong>
                            </div>
                        </div>
                    </div>

                    <footer class="src-catalog-card-footer">
                        <span class="src-catalog-card-note"><i class="fas fa-building mr-1" aria-hidden="true"></i> {{ $aula->bloque }} · {{ $aula->nombre }}</span>
                        <div class="src-catalog-actions">
                            <a href="{{ route('aulas.show', $aula->id) }}"><i class="far fa-eye" aria-hidden="true"></i> Ver</a>
                            @can('editar-aulas')
                                <a class="src-catalog-edit" href="{{ route('aulas.edit', $aula->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar</a>
                            @endcan
                            @can('borrar-aulas')
                                <form method="POST" action="{{ route('aulas.destroy', $aula->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        class="src-catalog-delete"
                                        data-catalog-delete
                                        data-confirm-title="¿Eliminar aula o laboratorio?"
                                        data-confirm-text="No se puede eliminar si tiene equipos asociados."
                                        data-confirm-button="Sí, eliminar"
                                    ><i class="far fa-trash-alt" aria-hidden="true"></i> Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </footer>
                </article>
            @empty
                <div class="src-catalog-empty">
                    <i class="fas fa-building" aria-hidden="true"></i>
                    <strong>No hay aulas o laboratorios registrados</strong>
                    <span>Los espacios académicos que agregues aparecerán aquí.</span>
                </div>
            @endforelse

            @if ($aulas->isNotEmpty())
                <div class="src-catalog-empty" data-catalog-filter-empty hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos espacios</strong>
                    <span>Prueba con otros filtros o términos de búsqueda.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$aulas" />
    </div>
@stop

@section('js')
    <script src="{{ asset('js/src-catalog.js') }}"></script>
@stop
