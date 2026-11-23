@extends('adminlte::page')

@section('title', 'Crear Permiso')

@section('content_header')
    <x-src-page-hero
        eyebrow="Seguridad · Permisos"
        title="Crear permiso"
        description="Registra una capacidad que después podrás asignar a uno o más roles."
        icon="fas fa-key" />
@stop

@section('content')
    <div class="src-form-page">
        <form method="POST" action="{{ route('permissions.store') }}" enctype="multipart/form-data">
            @csrf
            <x-src-validation-errors />

            <section class="src-form-section">
                <header class="src-form-section-heading">
                    <span><i class="fas fa-key" aria-hidden="true"></i></span>
                    <div><h2>Identificación del permiso</h2><p>Utiliza un nombre claro y único para esta capacidad.</p></div>
                </header>
                <div class="src-form-grid">
                    <div class="src-form-field src-form-field-full">
                        <label for="name">Nombre del permiso <span aria-hidden="true">*</span></label>
                        <input class="src-form-control" id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Ej. ver-reportes" autocomplete="name" required>
                        @error('name')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <div class="src-form-actions">
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Guardar permiso</button>
                <a class="src-catalog-secondary-action" href="{{ route('permissions.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </form>
    </div>
@stop
