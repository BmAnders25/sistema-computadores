@extends('adminlte::page')

@section('title', 'Nuevo Bloque Horario')

@section('content_header')
    <x-src-page-hero
        eyebrow="Configuración · Bloques horarios"
        title="Crear bloque horario"
        description="Define el nombre y la franja de tiempo en que se pueden reservar equipos."
        icon="far fa-clock" />
@stop

@section('content')
    <div class="src-form-page">
        <form method="POST" action="{{ route('bloques-horarios.store') }}">
            @csrf
            <x-src-validation-errors />

            <section class="src-form-section">
                <header class="src-form-section-heading">
                    <span><i class="far fa-clock" aria-hidden="true"></i></span>
                    <div><h2>Datos del bloque</h2><p>El horario de fin debe ser posterior al de inicio.</p></div>
                </header>

                <div class="src-form-grid">
                    <div class="src-form-field src-form-field-full">
                        <label for="nombre">Nombre del bloque <span aria-hidden="true">*</span></label>
                        <input class="src-form-control" id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" placeholder="Ej. Bloque 1 - Mañana" required>
                        @error('nombre')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="src-form-field">
                        <label for="hora_inicio">Hora de inicio <span aria-hidden="true">*</span></label>
                        <input class="src-form-control" id="hora_inicio" name="hora_inicio" type="time" value="{{ substr((string) old('hora_inicio'), 0, 5) }}" required>
                        @error('hora_inicio')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="src-form-field">
                        <label for="hora_fin">Hora de fin <span aria-hidden="true">*</span></label>
                        <input class="src-form-control" id="hora_fin" name="hora_fin" type="time" value="{{ substr((string) old('hora_fin'), 0, 5) }}" required>
                        @error('hora_fin')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="src-form-field src-form-field-full">
                        <label class="src-form-checkbox">
                            <input id="activo" name="activo" type="checkbox" value="1" {{ old('activo', true) ? 'checked' : '' }}>
                            <span><strong>Bloque activo</strong><small>Permitir que este horario esté disponible para nuevas reservas.</small></span>
                        </label>
                        @error('activo')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <div class="src-form-actions">
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Guardar bloque</button>
                <a class="src-catalog-secondary-action" href="{{ route('bloques-horarios.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </form>
    </div>
@stop
