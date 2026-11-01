@extends('adminlte::page')

@section('title', 'Registrar Computador')

@section('content_header')
    <h1 class="m-0 text-dark">Registrar Computador</h1>
@stop

@section('content')
    <x-adminlte-card shadow header-theme="primary" theme="primary">
        <form method="POST" action="{{ route('computadores.store') }}">
            @csrf

            <div class="row">
                {{-- Serial --}}
                <x-adminlte-input name="serial" label="Número de Serial" placeholder="Ej: SN-987654321"
                    fgroup-class="col-md-4" value="{{ old('serial') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-dark">
                            <i class="fas fa-barcode"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Bloque --}}
                <x-adminlte-input name="bloque" label="Bloque" placeholder="Ej: Bloque A"
                    fgroup-class="col-md-4" value="{{ old('bloque') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-navy">
                            <i class="fas fa-building"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Laboratorio o Aula --}}
                <x-adminlte-input name="laboratorio_aula" label="Laboratorio / Aula" placeholder="Ej: Lab. Sistemas 1"
                    fgroup-class="col-md-4" value="{{ old('laboratorio_aula') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-purple">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>
            </div>

            <div class="row">
                {{-- Tipo de Equipo --}}
                <x-adminlte-select name="tipo_equipo" label="Tipo de Equipo" fgroup-class="col-md-4" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-primary">
                            <i class="fas fa-laptop"></i>
                        </div>
                    </x-slot>
                    <option value="escritorio" {{ old('tipo_equipo') == 'escritorio' ? 'selected' : '' }}>Escritorio</option>
                    <option value="portatil" {{ old('tipo_equipo') == 'portatil' ? 'selected' : '' }}>Portátil</option>
                    <option value="all_in_one" {{ old('tipo_equipo') == 'all_in_one' ? 'selected' : '' }}>All-in-One</option>
                </x-adminlte-select>

                {{-- Marca --}}
                <x-adminlte-input name="marca" label="Marca" placeholder="Ej: HP, Dell, Lenovo"
                    fgroup-class="col-md-4" value="{{ old('marca') }}" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-info">
                            <i class="fas fa-tag"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Modelo --}}
                <x-adminlte-input name="modelo" label="Modelo (opcional)" placeholder="Ej: ProDesk 400 G6"
                    fgroup-class="col-md-4" value="{{ old('modelo') }}">
                    <x-slot name="prependSlot">
                        <div class="input-group-text text-secondary">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>
            </div>

            <div class="row">
                {{-- Procesador --}}
                <x-adminlte-input name="procesador" label="Procesador (opcional)" placeholder="Ej: Intel Core i5 10ma Gen"
                    fgroup-class="col-md-4" value="{{ old('procesador') }}">
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-cyan">
                            <i class="fas fa-microchip"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Memoria RAM --}}
                <x-adminlte-input name="ram_gb" label="Memoria RAM en GB (opcional)" type="number" min="1" max="512"
                    placeholder="Ej: 16" fgroup-class="col-md-4" value="{{ old('ram_gb') }}">
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-teal">
                            <i class="fas fa-memory"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Almacenamiento --}}
                <x-adminlte-input name="almacenamiento" label="Almacenamiento (opcional)" placeholder="Ej: 512 GB SSD"
                    fgroup-class="col-md-4" value="{{ old('almacenamiento') }}">
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-olive">
                            <i class="fas fa-hdd"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>
            </div>

            <div class="row">
                {{-- Sistema Operativo --}}
                <x-adminlte-input name="sistema_operativo" label="Sistema Operativo (opcional)" placeholder="Ej: Windows 11 Pro / Ubuntu 22.04"
                    fgroup-class="col-md-4" value="{{ old('sistema_operativo') }}">
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-indigo">
                            <i class="fab fa-windows"></i>
                        </div>
                    </x-slot>
                </x-adminlte-input>

                {{-- Estado --}}
                <x-adminlte-select name="estado" label="Estado" fgroup-class="col-md-4" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-success">
                            <i class="fas fa-toggle-on"></i>
                        </div>
                    </x-slot>
                    <option value="disponible" {{ old('estado', 'disponible') == 'disponible' ? 'selected' : '' }}>Disponible</option>
                    <option value="prestado" {{ old('estado') == 'prestado' ? 'selected' : '' }}>Prestado</option>
                    <option value="mantenimiento" {{ old('estado') == 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                    <option value="inactivo" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </x-adminlte-select>

                {{-- Condición --}}
                <x-adminlte-select name="condicion" label="Condición Física" fgroup-class="col-md-4" required>
                    <x-slot name="prependSlot">
                        <div class="input-group-text bg-gradient-warning">
                            <i class="fas fa-star"></i>
                        </div>
                    </x-slot>
                    <option value="excelente" {{ old('condicion') == 'excelente' ? 'selected' : '' }}>Excelente</option>
                    <option value="bueno" {{ old('condicion', 'bueno') == 'bueno' ? 'selected' : '' }}>Bueno</option>
                    <option value="regular" {{ old('condicion') == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="defectuoso" {{ old('condicion') == 'defectuoso' ? 'selected' : '' }}>Defectuoso</option>
                </x-adminlte-select>
            </div>

            <div class="row">
                {{-- Observaciones --}}
                <x-adminlte-textarea name="observaciones" label="Observaciones (opcional)" rows="3"
                    placeholder="Ej: Incluye mouse y teclado institucional, torre presenta leves rayones laterales..."
                    fgroup-class="col-md-12">{{ old('observaciones') }}</x-adminlte-textarea>
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <x-adminlte-button class="btn btn-primary mr-2" type="submit" label="Guardar Computador" theme="primary"
                        icon="fas fa-save" />
                    <a href="{{ route('computadores.index') }}" class="btn btn-secondary">
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