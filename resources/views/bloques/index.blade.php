@extends('adminlte::page')

@section('title', 'Bloques Horarios')

@section('content_header')
    <x-src-page-hero
        eyebrow="Configuración"
        title="Bloques horarios"
        description="Define las franjas horarias disponibles para la programación de reservas."
        icon="far fa-clock" />
@stop

@section('content')
    @php
        $catalogHeaders = ['Bloque', 'Hora de inicio', 'Hora de fin', 'Duración', 'Estado'];
    @endphp

    <div class="src-catalog-page" data-catalog-page data-catalog-title="Bloques horarios · S.R.C" data-catalog-filename="bloques-horarios">
        <div class="src-catalog-create-row">
            @can('crear-bloques-horarios')
                <a class="src-catalog-create-button" href="{{ route('bloques-horarios.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo Bloque Horario</a>
            @endcan
        </div>

        <x-src-catalog-toolbar :filters="[
            ['label' => 'Todos', 'value' => 'all', 'query' => '', 'count' => $totalBloques],
            ['label' => 'Activos', 'value' => 'active', 'query' => 'activo', 'count' => $activos],
            ['label' => 'Inactivos', 'value' => 'inactive', 'query' => 'inactivo', 'count' => $inactivos],
        ]" :selected-filter="request()->query('estado', '')" search-placeholder="Buscar por nombre u horario...">
        </x-src-catalog-toolbar>

        <section class="src-catalog-grid" data-catalog-grid data-catalog-headers="{{ json_encode($catalogHeaders) }}" aria-label="Bloques horarios registrados">
            @forelse ($bloques as $bloque)
                @php
                    $inicio = \Illuminate\Support\Carbon::parse($bloque->hora_inicio);
                    $fin = \Illuminate\Support\Carbon::parse($bloque->hora_fin);
                    $minutos = $inicio->diffInMinutes($fin);
                    $duracion = $minutos % 60 === 0
                        ? (int) ($minutos / 60) . ' horas'
                        : floor($minutos / 60) . ' h ' . ($minutos % 60) . ' min';
                    $horaInicio = $inicio->format('H:i');
                    $horaFin = $fin->format('H:i');
                    $estadoLabel = $bloque->activo ? 'Activo' : 'Inactivo';
                    $estadoFiltro = $bloque->activo ? 'active' : 'inactive';
                    $searchText = \Illuminate\Support\Str::lower($bloque->nombre . ' ' . $horaInicio . ' ' . $horaFin);
                    $catalogRecord = [$bloque->nombre, $horaInicio, $horaFin, $duracion, $estadoLabel];
                @endphp
                <article
                    class="src-catalog-card src-block-card"
                    data-catalog-card
                    data-catalog-status="{{ $estadoFiltro }}"
                    data-catalog-search="{{ $searchText }}"
                    data-catalog-record="{{ json_encode($catalogRecord, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                >
                    <header class="src-catalog-card-header">
                        <div class="src-catalog-card-heading">
                            <span class="src-catalog-icon"><i class="far fa-clock" aria-hidden="true"></i></span>
                            <div>
                                <h2>{{ $bloque->nombre }}</h2>
                                <p>Franja de reserva</p>
                            </div>
                        </div>
                        <span class="src-catalog-status src-catalog-status-{{ $bloque->activo ? 'active' : 'inactive' }}">
                            <span aria-hidden="true"></span>{{ $estadoLabel }}
                        </span>
                    </header>

                    <div class="src-catalog-card-body">
                        <div class="src-block-time-range">
                            <div><small>Inicio</small><strong>{{ $horaInicio }}</strong></div>
                            <span aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            <div><small>Fin</small><strong>{{ $horaFin }}</strong></div>
                        </div>
                        <div class="src-catalog-facts">
                            <div class="src-catalog-fact"><span>Duración</span><strong>{{ $duracion }}</strong></div>
                            <div class="src-catalog-fact"><span>Estado para reservas</span><strong>{{ $estadoLabel }}</strong></div>
                        </div>
                    </div>

                    <footer class="src-catalog-card-footer">
                        <span class="src-catalog-card-note"><i class="far fa-clock mr-1" aria-hidden="true"></i> {{ $horaInicio }} - {{ $horaFin }}</span>
                        <div class="src-catalog-actions">
                            <a href="{{ route('bloques-horarios.show', $bloque->id) }}"><i class="far fa-eye" aria-hidden="true"></i> Ver</a>
                            @can('editar-bloques-horarios')
                                <a class="src-catalog-edit" href="{{ route('bloques-horarios.edit', $bloque->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar</a>
                            @endcan
                            @can('borrar-bloques-horarios')
                                <form method="POST" action="{{ route('bloques-horarios.destroy', $bloque->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        class="src-catalog-delete"
                                        data-catalog-delete
                                        data-confirm-title="¿Eliminar bloque horario?"
                                        data-confirm-text="Eliminar el bloque puede afectar la programación de reservas."
                                        data-confirm-button="Sí, eliminar"
                                    ><i class="far fa-trash-alt" aria-hidden="true"></i> Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </footer>
                </article>
            @empty
                <div class="src-catalog-empty">
                    <i class="far fa-clock" aria-hidden="true"></i>
                    <strong>No hay bloques horarios registrados</strong>
                    <span>Las franjas agregadas aparecerán aquí.</span>
                </div>
            @endforelse

            @if ($bloques->isNotEmpty())
                <div class="src-catalog-empty" data-catalog-filter-empty hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos bloques</strong>
                    <span>Ajusta el filtro o la búsqueda e inténtalo de nuevo.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$bloques" />
    </div>
@stop

@section('js')
    <script src="{{ asset('js/src-catalog.js') }}"></script>
@stop
