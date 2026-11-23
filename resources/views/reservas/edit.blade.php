@extends('adminlte::page')

@section('title', 'Editar Reserva')

@section('content_header')
    <x-src-page-hero
        eyebrow="Reservas"
        title="Actualizar reserva"
        description="Revisa los datos de la solicitud y actualiza su estado."
        icon="fas fa-calendar-check" />
@stop

@section('content')
    <div class="src-form-page">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reservas.update', $reserva) }}">
            @csrf
            @method('PUT')

            <section class="src-form-section">
                <header class="src-form-section-heading">
                    <span><i class="fas fa-receipt" aria-hidden="true"></i></span>
                    <div><h2>Datos de la reserva #{{ $reserva->id }}</h2><p>Información asociada a esta solicitud.</p></div>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Usuario solicitante</span><strong>{{ $reserva->user->name ?? 'N/A' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Fecha programada</span><strong>{{ \Illuminate\Support\Carbon::parse($reserva->fecha)->format('d/m/Y') }}</strong></div>
                    <div class="src-catalog-detail-row src-catalog-field-full"><span>Recurso</span><strong>{{ $reserva->computador_id ? 'Computador ID: ' . $reserva->computador_id : 'Aula completa' }}</strong></div>
                </div>
            </section>

            <section class="src-form-section">
                <header class="src-form-section-heading">
                    <span><i class="fas fa-exchange-alt" aria-hidden="true"></i></span>
                    <div><h2>Estado de la reserva</h2><p>Selecciona el estado correspondiente al flujo de atención.</p></div>
                </header>
                <div class="src-form-grid">
                    <div class="src-form-field src-form-field-full">
                        <label for="estado">Estado de la reserva <span aria-hidden="true">*</span></label>
                        <select name="estado" id="estado" class="src-form-control" required>
                            <option value="pendiente" {{ old('estado', $reserva->estado) == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="confirmada" {{ old('estado', $reserva->estado) == 'confirmada' ? 'selected' : '' }}>Confirmada</option>
                            <option value="entregada" {{ old('estado', $reserva->estado) == 'entregada' ? 'selected' : '' }}>Entregada</option>
                            <option value="devuelta" {{ old('estado', $reserva->estado) == 'devuelta' ? 'selected' : '' }}>Devuelta</option>
                            <option value="cancelada" {{ old('estado', $reserva->estado) == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                            <option value="no_show" {{ old('estado', $reserva->estado) == 'no_show' ? 'selected' : '' }}>No se presentó</option>
                        </select>
                        @error('estado')<span class="src-form-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <div class="src-form-actions">
                <button class="src-catalog-primary-action" type="submit"><i class="fas fa-check" aria-hidden="true"></i> Actualizar reserva</button>
                <a class="src-catalog-secondary-action" href="{{ route('reservas.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </form>
    </div>
@stop
