@extends('adminlte::page')

@section('title', 'Editar Permiso')

@section('content_header')
    <x-src-page-hero
        eyebrow="Seguridad · Permisos"
        title="Editar permiso"
        description="Actualiza el nombre de la capacidad asignada a los roles del sistema."
        icon="fas fa-key" />
@stop

@section('content')
    <div class="src-form-page">
        <form method="POST" action="{{ route('permissions.update', $permission->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <x-src-validation-errors />

            <section class="src-form-section">
                <header class="src-form-section-heading">
                    <span><i class="fas fa-key" aria-hidden="true"></i></span>
                    <div><h2>Identificación del permiso</h2><p>El nombre debe continuar siendo único en el sistema.</p></div>
                </header>
                <div class="src-form-grid">
                    <div class="src-form-field src-form-field-full">
                        <label for="name">Nombre del permiso <span aria-hidden="true">*</span></label>
                        <input class="src-form-control" id="name" name="name" type="text" value="{{ old('name', $permission->name) }}" autocomplete="name" required>
                        @error('name')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <div class="src-form-actions">
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Actualizar permiso</button>
                <a class="src-catalog-secondary-action" href="{{ route('permissions.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </form>
    </div>
@stop
