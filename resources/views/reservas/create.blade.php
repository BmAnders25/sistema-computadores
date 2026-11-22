@extends('adminlte::page')

@section('title', 'Nueva Reserva')

@section('content_header')
    <h1 class="m-0 text-dark">Realizar una Nueva Reserva</h1>
@stop

@section('content')
    <x-adminlte-card shadow header-theme="primary" theme="primary">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reservas.store') }}" id="reservaForm">
            @csrf

            <div class="row">
                {{-- Selector de Tipo de Reserva --}}
                <div class="form-group col-md-12">
                    <label for="tipo" class="font-weight-bold">¿Qué deseas reservar?</label>
                    <select name="tipo" id="tipoSelect" class="form-control" required onchange="toggleTipoReserva()">
                        <option value="equipo" {{ old('tipo', $tipo) == 'equipo' ? 'selected' : '' }}>Computador Individual</option>
                        @can('reservar-aulas')
                            <option value="aula" {{ old('tipo', $tipo) == 'aula' ? 'selected' : '' }}>Aula / Laboratorio Completo</option>
                        @endcan
                    </select>
                </div>
            </div>

            <div class="row">
                {{-- Fecha de Reserva --}}
                <x-adminlte-input name="fecha" label="Fecha de la Reserva" type="date"
                    fgroup-class="col-md-6" value="{{ old('fecha', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-info text-white">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Bloque Horario --}}
                <div class="form-group col-md-6">
                    <label for="bloque_horario_id" class="font-weight-bold">Bloque Horario</label>
                    <select name="bloque_horario_id" id="bloque_horario_id" class="form-control" required>
                        <option value="">Seleccione un bloque...</option>
                        @foreach($bloques as $bloque)
                            <option value="{{ $bloque->id }}" {{ old('bloque_horario_id') == $bloque->id ? 'selected' : '' }}>
                                {{ $bloque->nombre }} ({{ $bloque->hora_inicio }} - {{ $bloque->hora_fin }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row" id="seccionComputador">
                {{-- Selección de Computador --}}
                <div class="form-group col-md-12">
                    <label for="computador_id" class="font-weight-bold">Seleccionar Computador Disponible</label>
                    <select name="computador_id" id="computador_id" class="form-control select2">
                        <option value="">Seleccione un equipo...</option>
                        @foreach($computadores as $comp)
                            <option value="{{ $comp->id }}" {{ old('computador_id') == $comp->id ? 'selected' : '' }}>
                                ID: {{ $comp->id }} | Serial: {{ $comp->serial }} - Aula: {{ $comp->aula->nombre ?? 'Sin aula' }} ({{ $comp->marca }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row" id="seccionAula" style="display: none;">
                {{-- Selección de Aula --}}
                <div class="form-group col-md-12">
                    <label for="aula_id" class="font-weight-bold">Seleccionar Aula / Laboratorio</label>
                    <select name="aula_id" id="aula_id" class="form-control select2">
                        <option value="">Seleccione un aula...</option>
                        @foreach($aulas as $aula)
                            <option value="{{ $aula->id }}" {{ old('aula_id') == $aula->id ? 'selected' : '' }}>
                                {{ $aula->bloque }} - {{ $aula->nombre }} (Capacidad: {{ $aula->capacidad }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <div class="form-group col-md-6">
                    <x-adminlte-button class="btn btn-primary mr-2" type="submit" label="Confirmar Reserva" theme="primary"
                        icon="fas fa-check" />
                    <a href="{{ route('reservas.index') }}" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Regresar
                    </a>
                </div>
            </div>
        </form>
    </x-adminlte-card>
@stop

@section('js')
<script>
    function toggleTipoReserva() {
        var tipo = document.getElementById('tipoSelect').value;
        var seccionComp = document.getElementById('seccionComputador');
        var seccionAula = document.getElementById('seccionAula');
        
        if (tipo === 'aula') {
            seccionComp.style.display = 'none';
            seccionAula.style.display = 'block';
            document.getElementById('computador_id').value = '';
        } else {
            seccionComp.style.display = 'block';
            seccionAula.style.display = 'none';
            document.getElementById('aula_id').value = '';
        }
    }

    // Ejecutar al cargar por si hay un old('tipo') previo
    document.addEventListener("DOMContentLoaded", function() {
        toggleTipoReserva();
    });
</script>
@stop