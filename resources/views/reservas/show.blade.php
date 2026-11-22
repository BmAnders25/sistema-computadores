@extends('adminlte::page')

@section('title', 'Detalle de Reserva')

@section('content_header')
    <h1 class="m-0 text-dark">Detalles de la Reserva #{{ $reserva->id }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header text-center">
                <h3 class="card-title font-weight-bold"><i class="fas fa-receipt mr-1"></i> Comprobante</h3>
            </div>
            <div class="card-body text-center bg-light">
                <div class="mb-3">
                    <span class="fa-stack fa-3x text-primary">
                        <i class="fas fa-circle fa-stack-2x text-light border rounded-circle shadow-sm"></i>
                        <i class="fas fa-bookmark fa-stack-1x text-primary"></i>
                    </span>
                </div>
                <h5 class="font-weight-bold text-dark">Clave Única de Verificación</h5>
                <div class="p-2 bg-white border rounded font-weight-bold text-monospace text-lg text-primary mb-3">
                    {{ $reserva->clave_unica }}
                </div>
                
                @if ($reserva->estado == 'pendiente')
                    <span class="badge badge-warning px-3 py-1 text-md">Pendiente de Uso</span>
                @elseif ($reserva->estado == 'entregada')
                    <span class="badge badge-success px-3 py-1 text-md">Entregada</span>
                @elseif ($reserva->estado == 'devuelta')
                    <span class="badge badge-info px-3 py-1 text-md">Devuelta</span>
                @elseif ($reserva->estado == 'cancelada')
                    <span class="badge badge-danger px-3 py-1 text-md">Cancelada</span>
                @elseif ($reserva->estado == 'no_show')
                    <span class="badge badge-dark px-3 py-1 text-md">No se presentó</span>
                @else
                    <span class="badge badge-secondary px-3 py-1 text-md">{{ ucfirst($reserva->estado) }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <x-adminlte-info-box title="Usuario Solicitante" 
                    text="{{ $reserva->user->name ?? 'N/A' }}" 
                    icon="fas fa-user" 
                    theme="navy"/>
            </div>
            <div class="col-md-6">
                <x-adminlte-info-box title="Fecha Programada" 
                    text="{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}" 
                    icon="far fa-calendar-alt" 
                    theme="info"/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <x-adminlte-info-box title="Bloque Horario" 
                    text="{{ $reserva->bloque->nombre ?? 'N/A' }} ({{ $reserva->bloque->hora_inicio ?? '' }} - {{ $reserva->bloque->hora_fin ?? '' }})" 
                    icon="far fa-clock" 
                    theme="teal"/>
            </div>
            <div class="col-md-6">
                <x-adminlte-info-box title="Espacio / Recurso" 
                    text="{{ $reserva->computador_id ? 'Computador Serial: ' . optional($reserva->computador)->serial : 'Aula Completa: ' . optional($reserva->aula)->nombre }}" 
                    icon="fas fa-laptop" 
                    theme="purple"/>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('reservas.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Regresar al Listado
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