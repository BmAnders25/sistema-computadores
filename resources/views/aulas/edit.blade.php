@extends('adminlte::page')

@section('title', 'Editar Aula / Laboratorio')

@section('content_header')
    <h1 class="m-0 text-dark">Editar Aula o Laboratorio: {{ $aula->bloque }} - {{ $aula->nombre }}</h1>
@stop

@section('content')
    <x-adminlte-card shadow header-theme="warning" theme="warning">
        <form method="POST" action="{{ route('aulas.update', $aula->id) }}">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Bloque --}}
                <x-adminlte-input name="bloque" label="Bloque" placeholder="Ej: Bloque A, Bloque Ing."
                    fgroup-class="col-md-6" value="{{ old('bloque', $aula->bloque) }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-navy">
                            <i class="fas fa-building"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Nombre del Aula / Laboratorio --}}
                <x-adminlte-input name="nombre" label="Nombre del Laboratorio / Aula" placeholder="Ej: Lab. Sistemas 1, Aula 204"
                    fgroup-class="col-md-6" value="{{ old('nombre', $aula->nombre) }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-purple">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>
            </div>

            <div class="row">
                {{-- Capacidad --}}
                <x-adminlte-input name="capacidad" label="Capacidad (Número de estudiantes)" type="number" min="1" max="200"
                    placeholder="Ej: 30" fgroup-class="col-md-6" value="{{ old('capacidad', $aula->capacidad) }}">
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-teal">
                            <i class="fas fa-users"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Estado --}}
                <x-adminlte-select name="estado" label="Estado" fgroup-class="col-md-6" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-success">
                            <i class="fas fa-toggle-on"></i>
                        </div>
                    </x-slot>
                    <option value="activo" {{ old('estado', $aula->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="mantenimiento" {{ old('estado', $aula->estado) == 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                    <option value="inactivo" {{ old('estado', $aula->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </x-adminlte-select>
            </div>

            <div class="row mt-3">
                <div class="form-group col-md-6">
                    <x-adminlte-button class="btn btn-warning text-white mr-2" type="submit" label="Actualizar Aula" theme="warning"
                        icon="fas fa-sync-alt" />
                    <a href="{{ route('aulas.index') }}" class="btn btn-secondary">
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