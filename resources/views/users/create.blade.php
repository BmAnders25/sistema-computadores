@extends('adminlte::page')

@section('title', 'S.R.C.')

@section('content_header')
    <h1 class="m-0 text-dark">Registrar usuario</h1>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <x-adminlte-card>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users._campos')

            <div class="row">
                <div class="form-group col-md-6">
                    <x-adminlte-button class="mr-2" type="submit" label="Guardar usuario" theme="primary" icon="fas fa-save" />
                    <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="fas fa-undo"></i> Cancelar</a>
                </div>
            </div>
        </form>
    </x-adminlte-card>
@stop

@section('footer')
    <footer>
        <p>© {{ date('Y') }} S.R.C. Sistema de Reserva de Computadores · Universidad de Pamplona. Todos los derechos reservados.</p>
    </footer>
@stop