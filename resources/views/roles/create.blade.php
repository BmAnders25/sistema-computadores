@extends('adminlte::page')

@section('title', 'Crear Rol')

@section('content_header')
    <x-src-page-hero
        eyebrow="Seguridad · Roles"
        title="Crear rol"
        description="Define un perfil de acceso y selecciona los permisos que tendrá asignados."
        icon="fas fa-user-shield" />
@stop

@section('content')
    <div class="src-form-page">
        <form method="POST" action="{{ route('roles.store') }}" enctype="multipart/form-data">
            @csrf
            <x-src-validation-errors />

            <section class="src-form-section">
                <header class="src-form-section-heading">
                    <span><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                    <div><h2>Identificación del rol</h2><p>El nombre permite distinguir este perfil en la administración de usuarios.</p></div>
                </header>
                <div class="src-form-grid">
                    <div class="src-form-field src-form-field-full">
                        <label for="name">Nombre del rol <span aria-hidden="true">*</span></label>
                        <input class="src-form-control" id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Ej. Celador" required>
                        @error('name')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <section class="src-form-section">
                <header class="src-form-section-heading">
                    <span><i class="fas fa-key" aria-hidden="true"></i></span>
                    <div><h2>Permisos del rol</h2><p>Selecciona los permisos necesarios. Puedes marcar todos los de un módulo.</p></div>
                </header>
                @include('roles._permission-groups', ['readOnly' => false, 'rolePermissions' => []])
                @error('permission')<span class="src-form-error">{{ $message }}</span>@enderror
            </section>

            <div class="src-form-actions">
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Guardar rol</button>
                <a class="src-catalog-secondary-action" href="{{ route('roles.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </form>
    </div>
@stop
