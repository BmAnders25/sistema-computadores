@extends('adminlte::page')

@section('title', 'Detalles del Computador')

@section('content_header')
    <h1 class="m-0 text-dark">Detalles del Computador: {{ $computador->serial }}</h1>
@stop

@section('content')
<div class="row">
    {{-- Columna de Resumen General del Equipo --}}
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header text-center">
                <h3 class="card-title font-weight-bold"><i class="fas fa-desktop mr-1"></i> Ficha Técnica</h3>
            </div>
            <div class="card-body text-center bg-light">
                <div class="mb-3">
                    <i class="fas fa-laptop-code fa-5x text-primary"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-1">{{ $computador->marca }}</h4>
                <p class="text-muted mb-2">{{ $computador->modelo ?? 'Sin Modelo Esp.' }}</p>
                <span class="badge badge-secondary text-uppercase px-3 py-1 mb-3">
                    {{ str_replace('_', ' ', $computador->tipo_equipo) }}
                </span>

                <hr>

                {{-- Código UUID Auto-generado --}}
                <div class="text-left">
                    <label class="text-muted small mb-1 d-block"><i class="fas fa-qrcode mr-1"></i> Código Único (UUID):</label>
                    <code class="text-dark bg-white p-2 rounded border d-block text-break font-weight-bold small">
                        {{ $computador->codigo }}
                    </code>
                </div>
            </div>
        </div>
    </div>

    {{-- Columna de Bloques de Información (Info Boxes) --}}
    <div class="col-md-8">
        <div class="row">
            {{-- Número de Serial --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Número de Serial" 
                    text="{{ $computador->serial }}" 
                    icon="fas fa-barcode" 
                    theme="dark"/>
            </div>

            {{-- Estado del Equipo (Lógica Dinámica) --}}
            <div class="col-md-6">
                @php
                    $estadoTexto = ucfirst($computador->estado);
                    switch ($computador->estado) {
                        case 'disponible':
                            $iconEstado = 'fas fa-check-circle';
                            $themeEstado = 'success';
                            break;
                        case 'prestado':
                            $iconEstado = 'fas fa-hand-holding';
                            $themeEstado = 'primary';
                            break;
                        case 'reservado':
                            $iconEstado = 'fas fa-bookmark';
                            $themeEstado = 'info';
                            break;
                        case 'mantenimiento':
                            $iconEstado = 'fas fa-tools';
                            $themeEstado = 'warning';
                            break;
                        default:
                            $iconEstado = 'fas fa-ban';
                            $themeEstado = 'danger';
                            break;
                    }
                @endphp

                <x-adminlte-info-box 
                    title="Estado de Disponibilidad" 
                    text="{{ $estadoTexto }}"
                    icon="{{ $iconEstado }}" 
                    theme="{{ $themeEstado }}" 
                />
            </div>
        </div>

        <div class="row">
            {{-- Ubicación: Bloque y Laboratorio/Aula --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Bloque" 
                    text="{{ $computador->bloque }}" 
                    icon="fas fa-building" 
                    theme="navy"/>
            </div>

            <div class="col-md-6">
                <x-adminlte-info-box title="Laboratorio / Aula" 
                    text="{{ $computador->laboratorio_aula }}" 
                    icon="fas fa-door-open" 
                    theme="purple"/>
            </div>

            {{-- Componentes Hardware --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Procesador" 
                    text="{{ $computador->procesador ?? 'No especificado' }}" 
                    icon="fas fa-microchip" 
                    theme="cyan"/>
            </div>

            <div class="col-md-6">
                <x-adminlte-info-box title="Memoria RAM" 
                    text="{{ $computador->ram_gb ? $computador->ram_gb . ' GB RAM' : 'No especificada' }}" 
                    icon="fas fa-memory" 
                    theme="teal"/>
            </div>

            <div class="col-md-6">
                <x-adminlte-info-box title="Almacenamiento" 
                    text="{{ $computador->almacenamiento ?? 'No especificado' }}" 
                    icon="fas fa-hdd" 
                    theme="olive"/>
            </div>

            <div class="col-md-6">
                <x-adminlte-info-box title="Sistema Operativo" 
                    text="{{ $computador->sistema_operativo ?? 'No especificado' }}" 
                    icon="fab fa-windows" 
                    theme="indigo"/>
            </div>

            {{-- Condición Física (Lógica Dinámica) --}}
            <div class="col-md-6">
                @php
                    $condicionTexto = ucfirst($computador->condicion);
                    switch ($computador->condicion) {
                        case 'excelente':
                            $themeCondicion = 'success';
                            break;
                        case 'bueno':
                            $themeCondicion = 'info';
                            break;
                        case 'regular':
                            $themeCondicion = 'warning';
                            break;
                        default:
                            $themeCondicion = 'danger';
                            break;
                    }
                @endphp

                <x-adminlte-info-box title="Condición Física" 
                    text="{{ $condicionTexto }}" 
                    icon="fas fa-star" 
                    theme="{{ $themeCondicion }}"/>
            </div>

            {{-- Observaciones --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Observaciones" 
                    text="{{ $computador->observaciones ?? 'Sin observaciones adicionales' }}" 
                    icon="fas fa-comment-alt" 
                    theme="lightblue"/>
            </div>
        </div>
    </div>
</div>

{{-- Botones de Acción --}}
<div class="mt-3 d-flex justify-content-between">
    <a href="{{ route('computadores.index') }}" class="btn btn-secondary">
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