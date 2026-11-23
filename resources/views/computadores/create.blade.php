@extends('adminlte::page')

@section('title', 'Registrar Computador')

@section('content_header')
    <div class="src-computer-form-heading">
        <div>
            <x-src-page-heading section="Inventario" page="Nuevo">Registrar Computador</x-src-page-heading>
            <p>Ingresa los datos para registrar un nuevo equipo en el inventario.</p>
        </div>
        <a class="src-computer-secondary-button" href="{{ route('computadores.index') }}">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver al inventario
        </a>
    </div>
@stop

@section('content')
    <div class="src-computer-form-page">
        <form class="src-computer-form" method="POST" action="{{ route('computadores.store') }}">
            @csrf
            <x-src-validation-errors />

            <section class="src-computer-form-section">
                <header>
                    <span><i class="fas fa-desktop" aria-hidden="true"></i></span>
                    <h2>Identificación y ubicación</h2>
                </header>
                <div class="src-computer-form-grid src-computer-identification-grid">
                    <div class="src-computer-field">
                        <label for="serial">Número de Serial</label>
                        <input type="text" name="serial" id="serial" value="{{ old('serial') }}" placeholder="Ej: SN-987654321" required class="@error('serial') is-invalid @enderror">
                        @error('serial')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="src-computer-field">
                        <label for="aula_id">Laboratorio / Aula</label>
                        <select name="aula_id" id="aula_id" required class="@error('aula_id') is-invalid @enderror">
                            <option value="">Seleccione un laboratorio o aula</option>
                            @foreach ($aulas as $aula)
                                <option value="{{ $aula->id }}" {{ old('aula_id') == $aula->id ? 'selected' : '' }}>
                                    {{ $aula->bloque }} · {{ $aula->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('aula_id')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <fieldset class="src-computer-field src-computer-type-field">
                        <legend>Tipo de Equipo</legend>
                        <div class="src-computer-type-options">
                            <label class="src-computer-type-option">
                                <input type="radio" name="tipo_equipo" value="escritorio" {{ old('tipo_equipo') === 'escritorio' ? 'checked' : '' }} required>
                                <span><i class="fas fa-desktop" aria-hidden="true"></i> Escritorio</span>
                            </label>
                            <label class="src-computer-type-option">
                                <input type="radio" name="tipo_equipo" value="portatil" {{ old('tipo_equipo') === 'portatil' ? 'checked' : '' }}>
                                <span><i class="fas fa-laptop" aria-hidden="true"></i> Portátil</span>
                            </label>
                            <label class="src-computer-type-option">
                                <input type="radio" name="tipo_equipo" value="all_in_one" {{ old('tipo_equipo') === 'all_in_one' ? 'checked' : '' }}>
                                <span><i class="fas fa-desktop" aria-hidden="true"></i> All-in-One</span>
                            </label>
                        </div>
                        @error('tipo_equipo')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </fieldset>

                    <div class="src-computer-field">
                        <label for="marca">Marca</label>
                        <input type="text" name="marca" id="marca" value="{{ old('marca') }}" placeholder="Ej: HP, Dell, Lenovo" required class="@error('marca') is-invalid @enderror">
                        @error('marca')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="src-computer-field">
                        <label for="modelo">Modelo (opcional)</label>
                        <input type="text" name="modelo" id="modelo" value="{{ old('modelo') }}" placeholder="Ej: ProDesk 400 G9" class="@error('modelo') is-invalid @enderror">
                        @error('modelo')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <section class="src-computer-form-section">
                <header>
                    <span><i class="fas fa-microchip" aria-hidden="true"></i></span>
                    <h2>Especificaciones técnicas</h2>
                </header>
                <div class="src-computer-form-grid src-computer-specification-form-grid">
                    <div class="src-computer-field">
                        <label for="procesador">Procesador (opcional)</label>
                        <input type="text" name="procesador" id="procesador" value="{{ old('procesador') }}" placeholder="Ej: Intel Core i5-12500" class="@error('procesador') is-invalid @enderror">
                        @error('procesador')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="src-computer-field">
                        <label for="ram_gb">Memoria RAM (GB, opcional)</label>
                        <input type="number" name="ram_gb" id="ram_gb" value="{{ old('ram_gb') }}" min="1" max="512" placeholder="Ej: 16" class="@error('ram_gb') is-invalid @enderror">
                        @error('ram_gb')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="src-computer-field">
                        <label for="almacenamiento">Almacenamiento (opcional)</label>
                        <input type="text" name="almacenamiento" id="almacenamiento" value="{{ old('almacenamiento') }}" placeholder="Ej: 512 GB SSD" class="@error('almacenamiento') is-invalid @enderror">
                        @error('almacenamiento')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="src-computer-field">
                        <label for="sistema_operativo">Sistema Operativo (opcional)</label>
                        <input type="text" name="sistema_operativo" id="sistema_operativo" value="{{ old('sistema_operativo') }}" placeholder="Ej: Windows 11 Pro / Ubuntu 22.04" class="@error('sistema_operativo') is-invalid @enderror">
                        @error('sistema_operativo')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <section class="src-computer-form-section">
                <header>
                    <span><i class="fas fa-sliders-h" aria-hidden="true"></i></span>
                    <h2>Estado, condición y observaciones</h2>
                </header>
                <div class="src-computer-form-grid src-computer-specification-form-grid">
                    <div class="src-computer-field">
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado" required class="@error('estado') is-invalid @enderror">
                            <option value="disponible" {{ old('estado', 'disponible') == 'disponible' ? 'selected' : '' }}>Disponible</option>
                            <option value="prestado" {{ old('estado') == 'prestado' ? 'selected' : '' }}>En uso / Prestado</option>
                            <option value="mantenimiento" {{ old('estado') == 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                            <option value="inactivo" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="src-computer-field">
                        <label for="condicion">Condición Física</label>
                        <select name="condicion" id="condicion" required class="@error('condicion') is-invalid @enderror">
                            <option value="excelente" {{ old('condicion') == 'excelente' ? 'selected' : '' }}>Excelente</option>
                            <option value="bueno" {{ old('condicion', 'bueno') == 'bueno' ? 'selected' : '' }}>Bueno</option>
                            <option value="regular" {{ old('condicion') == 'regular' ? 'selected' : '' }}>Regular</option>
                            <option value="defectuoso" {{ old('condicion') == 'defectuoso' ? 'selected' : '' }}>Malo / Defectuoso</option>
                        </select>
                        @error('condicion')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="src-computer-field src-computer-field-full">
                        <label for="observaciones">Observaciones (opcional)</label>
                        <textarea name="observaciones" id="observaciones" rows="3" placeholder="Incluye notas relevantes sobre el equipo..." class="@error('observaciones') is-invalid @enderror">{{ old('observaciones') }}</textarea>
                        @error('observaciones')<span class="src-computer-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <div class="src-computer-form-actions">
                <a class="src-computer-secondary-button" href="{{ route('computadores.index') }}">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Regresar
                </a>
                <button class="src-computer-primary-button" type="submit">
                    <i class="fas fa-check" aria-hidden="true"></i> Guardar Computador
                </button>
            </div>
        </form>
    </div>
@stop


