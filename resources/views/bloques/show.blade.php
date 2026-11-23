@extends('adminlte::page')

@section('title', 'Detalles del Bloque Horario')

@section('content_header')
    <section class="src-catalog-detail-heading">
        <nav aria-label="Migas de pan">
            <a href="{{ route('home') }}">S.R.C</a><span aria-hidden="true">/</span>
            <a href="{{ route('bloques-horarios.index') }}">Configuración</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $bloqueHorario->nombre }}</span>
        </nav>
        <div class="src-catalog-detail-title-row">
            <h1>{{ $bloqueHorario->nombre }}</h1>
            <div class="src-catalog-detail-actions">
                @can('editar-bloques-horarios')
                    <a class="src-catalog-primary-action" href="{{ route('bloques-horarios.edit', $bloqueHorario->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar</a>
                @endcan
                <a class="src-catalog-secondary-action" href="{{ route('bloques-horarios.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </div>
    </section>
@stop

@section('content')
    @php
        $inicio = \Illuminate\Support\Carbon::parse($bloqueHorario->hora_inicio);
        $fin = \Illuminate\Support\Carbon::parse($bloqueHorario->hora_fin);
        $minutos = $inicio->diffInMinutes($fin);
        $duracion = $minutos % 60 === 0
            ? (int) ($minutos / 60) . ' horas'
            : floor($minutos / 60) . ' h ' . ($minutos % 60) . ' min';
    @endphp

    <div class="src-catalog-detail-layout">
        <aside class="src-catalog-summary-card">
            <span class="src-catalog-summary-icon"><i class="far fa-clock" aria-hidden="true"></i></span>
            <h2>{{ $bloqueHorario->nombre }}</h2>
            <p>Franja de disponibilidad institucional</p>
            <div class="src-computer-summary-badges">
                <span class="src-catalog-status src-catalog-status-{{ $bloqueHorario->activo ? 'active' : 'inactive' }}"><span aria-hidden="true"></span>{{ $bloqueHorario->activo ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <div class="src-block-detail-time">
                <strong>{{ $inicio->format('H:i') }}</strong>
                <span aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                <strong>{{ $fin->format('H:i') }}</strong>
            </div>
            <p class="src-catalog-summary-caption">Duración: {{ $duracion }}</p>
        </aside>

        <div class="src-catalog-detail-main">
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-info-circle" aria-hidden="true"></i></span>
                    <h2>Información del horario</h2>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Nombre</span><strong>{{ $bloqueHorario->nombre }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Hora de inicio</span><strong>{{ $inicio->format('H:i') }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Hora de fin</span><strong>{{ $fin->format('H:i') }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Duración total</span><strong>{{ $duracion }} ({{ $minutos }} minutos)</strong></div>
                    <div class="src-catalog-detail-row"><span>Disponibilidad</span><strong>{{ $bloqueHorario->activo ? 'Disponible para reservas' : 'Inactivo para reservas' }}</strong></div>
                </div>
            </section>
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                    <h2>Uso del bloque horario</h2>
                </header>
                <p class="src-catalog-card-note mb-0">Esta franja puede seleccionarse al crear una reserva cuando está activa.</p>
            </section>
        </div>
    </div>
@stop
