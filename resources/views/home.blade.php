@extends('adminlte::page')

@section('title', 'S.R.C')

@section('content_header')
<h1 class="m-0 text-dark">S.R.C - Sistema de Reserva de Computadores.</h1>
@stop

@section('content')
<div class="row">
    <div class="col text-right">
        <h4>Día Actual : {{ now()->format('d/m/Y') }}</h4>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@stop

@section('footer')
    <div class="text-center text-muted">
        <p>
            <img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" alt="Logo UP" style=" max-height: 70px; border-radius: 15px" class="mr-2"> 
            © {{ date('Y') }} S.R.C - Sistema de Reserva de Computadores - Universidad de Pamplona Sede Villa del Rosario. Todos los derechos reservados.
        </p>
    </div>
@stop

@section('js')
@if ($message = Session::get('success'))
<script>
Swal.fire({
    title: "Operación Exitosa!",
    text: "{{ $message }}",
    timer: 2000,
    icon: "success"
});
</script>
@endif
@stop
