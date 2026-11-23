@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <x-src-page-hero
        eyebrow="Administración · Usuarios"
        title="Editar usuario"
        description="Actualiza el perfil, los permisos de acceso y la seguridad de la cuenta."
        icon="fas fa-user-edit" />
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="src-form-page">
        <form method="POST" action="{{ route('users.update', $user->id) }}">
            @csrf
            @method('PUT')
            <x-src-validation-errors />
            @include('users._campos')

            <div class="src-form-actions">
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Actualizar usuario</button>
                <a class="src-catalog-secondary-action" href="{{ route('users.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </form>
    </div>
@stop
