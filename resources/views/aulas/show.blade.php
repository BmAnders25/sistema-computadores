@extends('adminlte::page')

@section('title', 'Detalles del Aula / Laboratorio')

@section('content_header')
    <section class="src-catalog-detail-heading">
        <nav aria-label="Migas de pan">
            <a href="{{ route('home') }}">S.R.C</a><span aria-hidden="true">/</span>
            <a href="{{ route('aulas.index') }}">Inventario</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $aula->nombre }}</span>
        </nav>
        <div class="src-catalog-detail-title-row">
            <h1>{{ $aula->nombre }} · {{ $aula->bloque }}</h1>
            <div class="src-catalog-detail-actions">
                @can('editar-aulas')
                    <a class="src-catalog-primary-action" href="{{ route('aulas.edit', $aula->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar</a>
                @endcan
                <a class="src-catalog-secondary-action" href="{{ route('aulas.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </div>
    </section>
@stop

@section('content')
    @php
        $activa = in_array($aula->estado, ['activo', 'disponible'], true);
        $estadoLabel = $activa ? 'Activa' : ($aula->estado === 'mantenimiento' ? 'Mantenimiento' : 'Inactiva');
        $estadoClass = $activa ? 'active' : ($aula->estado === 'mantenimiento' ? 'pending' : 'inactive');
    @endphp

    <div class="src-catalog-detail-layout">
        <aside class="src-catalog-summary-card">
            <span class="src-catalog-summary-icon"><i class="fas fa-building" aria-hidden="true"></i></span>
            <h2>{{ $aula->nombre }}</h2>
            <p>{{ $aula->bloque }}</p>
            <div class="src-computer-summary-badges">
                <span class="src-catalog-status src-catalog-status-{{ $estadoClass }}"><span aria-hidden="true"></span>{{ $estadoLabel }}</span>
                <span class="src-catalog-badge">{{ $aula->capacidad ? $aula->capacidad . ' estudiantes' : 'Capacidad no especificada' }}</span>
            </div>
            <div class="src-catalog-detail-rows">
                <div class="src-catalog-detail-row"><span>Equipos asociados</span><strong>{{ $aula->computadores->count() }}</strong></div>
                <div class="src-catalog-detail-row"><span>Reservas registradas</span><strong>{{ $aula->reservas->count() }}</strong></div>
            </div>
        </aside>

        <div class="src-catalog-detail-main">
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-info-circle" aria-hidden="true"></i></span>
                    <h2>Información del espacio</h2>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Bloque</span><strong>{{ $aula->bloque }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Laboratorio / Aula</span><strong>{{ $aula->nombre }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Capacidad</span><strong>{{ $aula->capacidad ? $aula->capacidad . ' estudiantes' : 'No especificada' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Estado operativo</span><strong>{{ $estadoLabel }}</strong></div>
                </div>
            </section>

            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-desktop" aria-hidden="true"></i></span>
                    <h2>Computadores asociados ({{ $aula->computadores->count() }})</h2>
                </header>
                @if ($aula->computadores->isNotEmpty())
                    <div class="table-responsive">
                        <table class="src-catalog-detail-table">
                            <thead><tr><th>Equipo</th><th>Tipo</th><th>Estado</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($aula->computadores as $computador)
                                    <tr>
                                        <td><strong>{{ $computador->marca }} {{ $computador->modelo }}</strong><br><small class="text-muted">{{ $computador->serial }}</small></td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $computador->tipo_equipo)) }}</td>
                                        <td>{{ ucfirst($computador->estado) }}</td>
                                        <td><a href="{{ route('computadores.show', $computador->id) }}" aria-label="Ver computador {{ $computador->serial }}"><i class="far fa-eye" aria-hidden="true"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="src-catalog-card-note mb-0">No hay computadores vinculados actualmente a este espacio.</p>
                @endif
            </section>
        </div>
    </div>
@stop
