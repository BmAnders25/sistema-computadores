@extends('adminlte::page')

@section('title', 'Registrar Bloque Horario')

@section('content_header')
    <h1 class="m-0 text-dark">Registrar Bloque Horario</h1>
@stop

@section('content')
    <x-adminlte-card shadow header-theme="primary" theme="primary">
        <form method="POST" action="{{ route('bloques-horarios.store') }}">
            @csrf

            <div class="row">
                {{-- Nombre --}}
                <x-adminlte-input name="nombre" label="Nombre del Bloque" placeholder="Ej: Bloque 1 (7:00 AM - 9:00 AM)"
                    fgroup-class="col-md-12" value="{{ old('nombre') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-navy">
                            <i class="fas fa-tag"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>
            </div>

            <div class="row">
                {{-- Hora Inicio --}}
                <x-adminlte-input name="hora_inicio" label="Hora de Inicio" type="time"
                    fgroup-class="col-md-6" value="{{ old('hora_inicio') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-info text-white">
                            <i class="far fa-clock"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Hora Fin --}}
                <x-adminlte-input name="hora_fin" label="Hora de Fin" type="time"
                    fgroup-class="col-md-6" value="{{ old('hora_fin') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-danger text-white">
                            <i class="far fa-clock"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>
            </div>

            <div class="row">
                {{-- Activo (Checkbox) --}}
                <div class="form-group col-md-6">
                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success mt-4">
                        <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="activo">Bloque Horario Activo para Reservas</label>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="form-group col-md-6">
                    <x-adminlte-button class="btn btn-primary mr-2" type="submit" label="Guardar Bloque" theme="primary"
                        icon="fas fa-save" />
                    <a href="{{ route('bloques-horarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Regresar
                    </a>
                </div>
            </div>
        </form>
    </x-adminlte-card>
@stop

@section('footer')
    <div class="text-center text-muted">
        <p>
            <img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" alt="Logo UP" style="max-height: 70px; border-radius: 15px" class="mr-2"> 
            © {{ date('Y') }} S.R.C - Sistema de Reserva de Computadores - Universidad de Pamplona Sede Villa del Rosario. Todos los derechos reservados.
        </p>
    </div>
@stop