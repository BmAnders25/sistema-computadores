@extends('adminlte::page')

@section('title', 'S.O.A.H')

@section('content_header')
<h1 class="m-0 text-dark">Registrar Permiso</h1>
@stop

@section('content')
<x-adminlte-card>
    <form method="POST" action="{{ route('permissions.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <x-adminlte-input name="name" type="text" label="Nombre Permiso" placeholder="Nombre Permiso"
                fgroup-class="col-md-6" autocomplete="name" />
        </div>

        <div class="row">
            <div class="form-group col-md-6">
                <x-adminlte-button class="btn btn-primary mr-2" type="submit" label="Guardar Permiso" theme="primary"
                    icon="fas fa-save" />
                <a href="{{ route('permissions.index') }}" class="btn btn-secondary mr-2"><i class="fas fa-undo"></i>
                    Cancelar</a>
            </div>
        </div>
    </form>
</x-adminlte-card>

@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif
@stop

@section('footer')
    <footer>
        <p><img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" width="4%" style="border-radius: 15px" alt="Logo S.O.AH"> © {{ date('Y') }} S.O.A.H.  Sistema De Organización y Administración Hotelera . Todos los derechos reservados.</p>
    </footer>
@stop
