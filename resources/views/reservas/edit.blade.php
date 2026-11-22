@extends('adminlte::page')

@section('title', 'Editar Reserva')

@section('content_header')
    <h1 class="m-0 text-dark">Modificar Estado de la Reserva #{{ $reserva->id }}</h1>
@stop

@section('content')
    <x-adminlte-card shadow header-theme="warning" theme="warning">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reservas.update', $reserva) }}">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Información de lectura rápida --}}
                <div class="col-md-12 mb-3">
                    <div class="p-3 bg-light border rounded">
                        <p class="mb-1"><strong>Usuario Solicitante:</strong> {{ $reserva->user->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Fecha Programada:</strong> {{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}</p>
                        <p class="mb-0"><strong>Recurso:</strong> {{ $reserva->computador_id ? 'Computador ID: ' . $reserva->computador_id : 'Aula Completa' }}</p>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Estado de la Reserva --}}
                <div class="form-group col-md-6">
                    <label for="estado" class="font-weight-bold">Estado de la Reserva</label>
                    <select name="estado" id="estado" class="form-control" required>
                        <option value="pendiente" {{ old('estado', $reserva->estado) == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="entregada" {{ old('estado', $reserva->estado) == 'entregada' ? 'selected' : '' }}>Entregada</option>
                        <option value="devuelta" {{ old('estado', $reserva->estado) == 'devuelta' ? 'selected' : '' }}>Devuelta</option>
                        <option value="cancelada" {{ old('estado', $reserva->estado) == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                        <option value="no_show" {{ old('estado', $reserva->estado) == 'no_show' ? 'selected' : '' }}>No se presentó</option>
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <div class="form-group col-md-6">
                    <x-adminlte-button class="btn btn-warning text-white mr-2" type="submit" label="Actualizar Reserva" theme="warning"
                        icon="fas fa-sync-alt" />
                    <a href="{{ route('reservas.index') }}" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Regresar
                    </a>
                </div>
            </div>
        </form>
    </x-adminlte-card>
@stop

@section('footer')
    <div style="max-height: 55px;" class="text-center text-muted">
        <p>
            <img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" alt="Logo UP" style="max-height: 70px; border-radius: 15px" class="mr-2"> 
            © {{ date('Y') }} S.R.C - Sistema de Reserva de Computadores - Universidad de Pamplona Sede Villa del Rosario. Todos los derechos reservados.
        </p>
    </div>
@stop