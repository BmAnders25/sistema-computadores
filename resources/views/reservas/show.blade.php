@extends('adminlte::page')

@section('title', 'Detalle de Reserva')

@section('content_header')
    <section class="src-catalog-detail-heading">
        <nav aria-label="Migas de pan">
            <a href="{{ route('home') }}">S.R.C</a><span aria-hidden="true">/</span>
            <a href="{{ route('reservas.index') }}">Reservas</a><span aria-hidden="true">/</span>
            <span aria-current="page">Reserva #{{ $reserva->id }}</span>
        </nav>
        <div class="src-catalog-detail-title-row">
            <h1>Detalle de la reserva #{{ $reserva->id }}</h1>
            <div class="src-catalog-detail-actions">
                <a class="src-catalog-secondary-action" href="{{ route('reservas.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a mis reservas</a>
            </div>
        </div>
    </section>
@stop

@section('content')
    @php
        $fecha = \Illuminate\Support\Carbon::parse($reserva->fecha);
        $horaInicio = $reserva->bloque ? \Illuminate\Support\Carbon::parse($reserva->bloque->hora_inicio)->format('H:i') : null;
        $horaFin = $reserva->bloque ? \Illuminate\Support\Carbon::parse($reserva->bloque->hora_fin)->format('H:i') : null;
        $estadoReserva = [
            'pendiente' => ['Pendiente de aprobación', 'pending'],
            'confirmada' => ['Confirmada · Pendiente de entrega', 'active'],
            'entregada' => ['En uso', 'active'],
            'devuelta' => ['Devuelta', 'inactive'],
            'cancelada' => ['Cancelada', 'inactive'],
            'no_show' => ['No se presentó', 'inactive'],
        ][$reserva->estado] ?? [ucfirst($reserva->estado), 'inactive'];
        $recursoNombre = $reserva->computador_id
            ? trim((optional($reserva->computador)->marca ?? '') . ' ' . (optional($reserva->computador)->modelo ?? ''))
            : (optional($reserva->aula)->nombre ?? 'Aula completa');
        $recursoSerial = optional($reserva->computador)->serial;
        $ubicacion = $reserva->computador
            ? trim((optional($reserva->computador->aula)->bloque ?? '') . ' · ' . (optional($reserva->computador->aula)->nombre ?? ''))
            : (optional($reserva->aula)->bloque ?? '');
    @endphp

    <div class="src-catalog-detail-layout">
        <aside class="src-catalog-summary-card">
            <span class="src-catalog-summary-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span>
            <h2>Reserva #{{ $reserva->id }}</h2>
            <p>{{ $fecha->format('d/m/Y') }}</p>
            <span class="src-catalog-status src-catalog-status-{{ $estadoReserva[1] }}"><span aria-hidden="true"></span>{{ $estadoReserva[0] }}</span>
            <div class="src-catalog-detail-rows">
                <div class="src-catalog-detail-row"><span>Clave única</span><strong class="text-monospace">{{ $reserva->clave_unica }}</strong></div>
                <div class="src-catalog-detail-row"><span>Solicitante</span><strong>{{ $reserva->user->name ?? 'N/A' }}</strong></div>
            </div>
        </aside>

        <div class="src-catalog-detail-main">
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                    <h2>Información de la reserva</h2>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Solicitante</span><strong>{{ $reserva->user->name ?? 'N/A' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Fecha programada</span><strong>{{ $fecha->translatedFormat('d M Y') }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Bloque horario</span><strong>{{ $reserva->bloque->nombre ?? 'N/A' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Horario</span><strong>{{ $horaInicio && $horaFin ? $horaInicio . ' - ' . $horaFin : 'No especificado' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Estado</span><strong>{{ $estadoReserva[0] }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Clave única de verificación</span><strong class="text-monospace">{{ $reserva->clave_unica }}</strong></div>
                </div>
            </section>

            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-desktop" aria-hidden="true"></i></span>
                    <h2>Equipo o espacio reservado</h2>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Recurso</span><strong>{{ $recursoNombre ?: 'Aula completa' }}</strong></div>
                    @if ($recursoSerial)
                        <div class="src-catalog-detail-row"><span>Serial</span><strong>{{ $recursoSerial }}</strong></div>
                    @endif
                    <div class="src-catalog-detail-row"><span>Ubicación</span><strong>{{ $ubicacion ?: 'No especificada' }}</strong></div>
                </div>
            </section>
        </div>
    </div>
@stop
