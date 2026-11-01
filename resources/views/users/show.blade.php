@extends('adminlte::page')

@section('title', 'S.O.A.H')

@section('content_header')
    <h1 class="m-0 text-dark">Detalles del Usuario</h1>
@stop

@section('content')
    <x-adminlte-card>
        <div class="row">
            <div class="col-md-6">
                <x-adminlte-info-box title="Nombre del Usuario" text="{{ $user->name }}" icon="fas fa-user" theme="info" />
            </div>
            <div class="col-md-6">
                <x-adminlte-info-box title="Email" text="{{ $user->email }}" icon="far fa-envelope" theme="info" />
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <x-adminlte-info-box title="Rol" text="{{ $user->roles->first()->name ?? 'N/A' }}" icon="fas fa-user-tag"
                    theme="info" />
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <a href="{{ route('users.index') }}" class="btn btn-secondary mr-2" role="button"><i class="fas fa-undo"></i>
                    Regresar</a>
            </div>
        </div>
    </x-adminlte-card>
@stop

@section('footer')
    <footer>
        <p><img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" width="4%" style="border-radius: 15px" alt="Logo S.O.AH"> © {{ date('Y') }} S.O.A.H.  Sistema De Organización y Administración Hotelera . Todos los derechos reservados.</p>
    </footer>
@stop

