@extends('adminlte::page')

@section('title', 'Editar Aula / Laboratorio')

@section('content_header')
    <div class="src-catalog-form-heading">
        <div>
            <x-src-page-heading section="Inventario" page="Editar">Editar {{ $aula->bloque }} · {{ $aula->nombre }}</x-src-page-heading>
            <p>Actualiza la ubicación, capacidad y disponibilidad del espacio académico.</p>
        </div>
        <a class="src-catalog-secondary-action" href="{{ route('aulas.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
    </div>
@stop

@section('content')
    <div class="src-catalog-form-page">
        <form class="src-catalog-form" method="POST" action="{{ route('aulas.update', $aula->id) }}">
            @csrf
            @method('PUT')
            <x-src-validation-errors />

            <section class="src-catalog-form-section">
                <header><span><i class="fas fa-building" aria-hidden="true"></i></span><h2>Identificación del espacio</h2></header>
                <div class="src-catalog-form-grid">
                    <div class="src-catalog-field">
                        <label for="bloque">Bloque</label>
                        <input type="text" name="bloque" id="bloque" value="{{ old('bloque', $aula->bloque) }}" placeholder="Ej: Bloque A" required class="@error('bloque') is-invalid @enderror">
                        @error('bloque')<span class="src-catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="src-catalog-field">
                        <label for="nombre">Nombre del Laboratorio / Aula</label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $aula->nombre) }}" placeholder="Ej: LAB-1" required class="@error('nombre') is-invalid @enderror">
                        @error('nombre')<span class="src-catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="src-catalog-field">
                        <label for="capacidad">Capacidad (estudiantes)</label>
                        <input type="number" name="capacidad" id="capacidad" value="{{ old('capacidad', $aula->capacidad) }}" min="1" max="200" placeholder="Ej: 30" class="@error('capacidad') is-invalid @enderror">
                        @error('capacidad')<span class="src-catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="src-catalog-field">
                        <label for="estado">Estado</label>
                        <select name="estado" id="estado" required class="@error('estado') is-invalid @enderror">
                            <option value="activo" {{ old('estado', $aula->estado) == 'activo' ? 'selected' : '' }}>Activa</option>
                            <option value="mantenimiento" {{ old('estado', $aula->estado) == 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                            <option value="inactivo" {{ old('estado', $aula->estado) == 'inactivo' ? 'selected' : '' }}>Inactiva</option>
                        </select>
                        @error('estado')<span class="src-catalog-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <div class="src-catalog-form-actions">
                <a class="src-catalog-secondary-action" href="{{ route('aulas.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Actualizar Aula</button>
            </div>
        </form>
    </div>
@stop
