@extends('adminlte::page')

@section('title', 'Crear Usuario')

@section('content_header')
    <x-src-page-hero
        eyebrow="Administración · Usuarios"
        title="Crear usuario"
        description="Registra una cuenta y configura su rol de acceso al sistema."
        icon="fas fa-user-plus" />
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="src-form-page">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <x-src-validation-errors />
            @include('users._campos')

            <div class="src-form-actions">
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Guardar usuario</button>
                <a class="src-catalog-secondary-action" href="{{ route('users.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </form>
    </div>
@stop
