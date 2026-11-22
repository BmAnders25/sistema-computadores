@extends('adminlte::page')

@section('title', 'Detalles del Aula / Laboratorio')

@section('content_header')
    <h1 class="m-0 text-dark">Detalles del Aula: {{ $aula->bloque }} - {{ $aula->nombre }}</h1>
@stop

@section('content')
<div class="row">
    {{-- Columna de Resumen General del Aula --}}
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header text-center">
                <h3 class="card-title font-weight-bold"><i class="fas fa-door-open mr-1"></i> Ficha del Espacio</h3>
            </div>
            <div class="card-body text-center bg-light">
                <div class="mb-3">
                    <i class="fas fa-building fa-5x text-primary"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-1">{{ $aula->nombre }}</h4>
                <p class="text-muted mb-2">{{ $aula->bloque }}</p>
                <span class="badge badge-secondary text-uppercase px-3 py-1 mb-3">
                    Capacidad: {{ $aula->capacidad ?? 'N/A' }} personas
                </span>

                <hr>

                <div class="text-left">
                    <label class="text-muted small mb-1 d-block"><i class="fas fa-desktop mr-1"></i> Total Equipos Vinculados:</label>
                    <span class="badge badge-info p-2 d-block font-weight-bold">
                        {{ $aula->computadores->count() }} computadores registrados
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Columna de Bloques de Información (Info Boxes) --}}
    <div class="col-md-8">
        <div class="row">
            {{-- Bloque --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Bloque" 
                    text="{{ $aula->bloque }}" 
                    icon="fas fa-building" 
                    theme="navy"/>
            </div>

            {{-- Nombre del Laboratorio --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Nombre / Aula" 
                    text="{{ $aula->nombre }}" 
                    icon="fas fa-door-open" 
                    theme="purple"/>
            </div>
        </div>

        <div class="row">
            {{-- Capacidad --}}
            <div class="col-md-6">
                <x-adminlte-info-box title="Capacidad Estudiantil" 
                    text="{{ $aula->capacidad ? $aula->capacidad . ' estudiantes' : 'No especificada' }}" 
                    icon="fas fa-users" 
                    theme="teal"/>
            </div>

            {{-- Estado (Dinámico) --}}
            <div class="col-md-6">
                @php
                    $estadoTexto = ucfirst($aula->estado);
                    switch ($aula->estado) {
                        case 'activo':
                        case 'disponible':
                            $iconEstado = 'fas fa-check-circle';
                            $themeEstado = 'success';
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
                    title="Estado del Espacio" 
                    text="{{ $estadoTexto }}"
                    icon="{{ $iconEstado }}" 
                    theme="{{ $themeEstado }}" 
                />
            </div>
        </div>

        {{-- Listado rápido de equipos vinculados a este laboratorio --}}
        <div class="row mt-2">
            <div class="col-md-12">
                <div class="card card-outline card-secondary shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-laptop mr-1"></i> Computadores en este Laboratorio</h3>
                    </div>
                    <div class="card-body p-0">
                        @if($aula->computadores->count() > 0)
                            <table class="table table-striped mb-0">
                               <thead>
                                   <tr>
                                       <th>Serial</th>
                                       <th>Marca / Modelo</th>
                                       <th>Tipo</th>
                                       <th>Estado</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   @foreach($aula->computadores as $comp)
                                   <tr>
                                       <td><strong>{{ $comp->serial }}</strong></td>
                                       <td>{{ $comp->marca }} {{ $comp->modelo }}</td>
                                       <td><span class="badge bg-secondary">{{ ucfirst($comp->tipo_equipo) }}</span></td>
                                       <td>
                                           @if($comp->estado == 'disponible')
                                               <span class="badge bg-success">Disponible</span>
                                           @else
                                               <span class="badge bg-warning">{{ ucfirst($comp->estado) }}</span>
                                           @endif
                                       </td>
                                   </tr>
                                   @endforeach
                               </tbody>
                            </table>
                        @else
                            <p class="text-center text-muted p-3 mb-0">No hay equipos de cómputo vinculados a esta aula actualmente.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Botones de Acción --}}
<div class="mt-3 d-flex justify-content-between">
    <a href="{{ route('aulas.index') }}" class="btn btn-secondary">
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