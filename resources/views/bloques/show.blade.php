@extends('adminlte::page')

@section('title', 'Detalles del Bloque Horario')

@section('content_header')
    <h1 class="m-0 text-dark">Detalles del Franjeo: {{ $bloqueHorario->nombre }}</h1>
@stop

@section('content')
@php
    use Carbon\Carbon;
    $inicio = Carbon::parse($bloqueHorario->hora_inicio);
    $fin = Carbon::parse($bloqueHorario->hora_fin);
    $minutosTotales = $inicio->diffInMinutes($fin);
    $horas = floor($minutosTotales / 60);
    $mins = $minutosTotales % 60;
@endphp

<div class="row">
    {{-- Columna Izquierda: Tarjeta de Presentación UX/UI --}}
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header text-center">
                <h3 class="card-title font-weight-bold"><i class="far fa-clock mr-1"></i> Ficha Horaria</h3>
            </div>
            <div class="card-body text-center bg-light">
                <div class="mb-3">
                    <span class="fa-stack fa-3x text-primary">
                        <i class="fas fa-circle fa-stack-2x text-light border rounded-circle shadow-sm"></i>
                        <i class="fas fa-hourglass-half fa-stack-1x text-primary"></i>
                    </span>
                </div>
                <h4 class="font-weight-bold text-dark mb-1">{{ $bloqueHorario->nombre }}</h4>
                <p class="text-muted mb-2">Franja de Disponibilidad Institucional</p>
                
                @if($bloqueHorario->activo)
                    <span class="badge badge-success px-3 py-1 mb-3 text-md">
                        <i class="fas fa-check-circle mr-1"></i> Habilitado
                    </span>
                @else
                    <span class="badge badge-danger px-3 py-1 mb-3 text-md">
                        <i class="fas fa-ban mr-1"></i> Deshabilitado
                    </span>
                @endif

                <hr>

                {{-- Línea de tiempo visual compacta UX --}}
                <div class="text-left px-2">
                    <label class="text-muted small mb-2 d-block font-weight-bold"><i class="fas fa-stream mr-1"></i> Línea Temporal del Bloque:</label>
                    <div class="d-flex justify-content-between text-sm font-weight-bold text-dark mb-1">
                        <span>{{ $inicio->format('h:i A') }}</span>
                        <span class="text-muted small">({{ ($horas > 0 ? $horas . 'h ' : '') . ($mins > 0 ? $mins . 'min' : '') }})</span>
                        <span>{{ $fin->format('h:i A') }}</span>
                    </div>
                    <div class="progress progress-sm rounded bg-secondary">
                        <div class="progress-bar bg-gradient-info progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Columna Derecha: Cajas de Información Detallada --}}
    <div class="col-md-8">
        <div class="row">
            {{-- Nombre del Bloque --}}
            <div class="col-md-12">
                <x-adminlte-info-box title="Identificador del Bloque" 
                    text="{{ $bloqueHorario->nombre }}" 
                    icon="fas fa-tag" 
                    theme="navy"/>
            </div>
        </div>

        <div class="row">
            {{-- Hora de Inicio --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Apertura / Inicio" 
                    text="{{ $inicio->format('h:i:s A') }} ({{ $inicio->format('H:i') }})" 
                    icon="far fa-clock" 
                    theme="info"/>
            </div>

            {{-- Hora de Fin --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Cierre / Fin" 
                    text="{{ $fin->format('h:i:s A') }} ({{ $fin->format('H:i') }})" 
                    icon="far fa-clock" 
                    theme="danger"/>
            </div>
        </div>

        <div class="row">
            {{-- Duración Total --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Duración Efectiva" 
                    text="{{ $minutosTotales }} minutos totales" 
                    icon="fas fa-stopwatch" 
                    theme="teal"/>
            </div>

            {{-- Estado Operativo --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Estado del Sistema" 
                    text="{{ $bloqueHorario->activo ? 'Disponible para reservas' : 'Inactivo para los usuarios' }}" 
                    icon="{{ $bloqueHorario->activo ? 'fas fa-toggle-on' : 'fas fa-toggle-off' }}" 
                    theme="{{ $bloqueHorario->activo ? 'success' : 'secondary' }}"/>
            </div>
        </div>
    </div>
</div>

{{-- Botones de Acción --}}
<div class="mt-3 d-flex justify-content-between">
    <a href="{{ route('bloques-horarios.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Regresar
    </a>
</div>
@stop

@section('footer')
    <div style="max-height: 55px;" class="text-center text-muted">
        <p>
            <img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" alt="Logo UP" style="max-height: 70px; border-radius: 15px" class="mr-2"> 
            © {{ date('Y') }} S.R.C - Sistema de Reserva de Computadores - Universidad de Pamplona Sede Villa del Rosario. Todos los derechos reservados.
        </p>
    </div>
@stop