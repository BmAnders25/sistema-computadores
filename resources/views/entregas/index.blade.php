@extends('adminlte::page')

@section('title', 'Entregas y devoluciones')

@section('content_header')
    <x-src-page-hero
        eyebrow="Control de equipos"
        title="Entregas y devoluciones"
        description="Registra la salida y el regreso de cada computador reservado."
        icon="fas fa-hand-holding" />
@stop

@section('content')
    @php
        $selectedEstado = request()->query('estado', '');
    @endphp

    <div class="src-deliveries-page">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="src-delivery-kpis" aria-label="Resumen de entregas">
            <article class="src-delivery-kpi src-delivery-kpi-ready">
                <div class="src-delivery-kpi-label"><span></span>Por entregar</div>
                <strong>{{ $porEntregar }}</strong>
                <small>Equipos listos para salida</small>
            </article>
            <article class="src-delivery-kpi src-delivery-kpi-in-use">
                <div class="src-delivery-kpi-label"><span></span>En uso actualmente</div>
                <strong>{{ $enUso }}</strong>
                <small>Equipos en manos de usuarios</small>
            </article>
            <article class="src-delivery-kpi src-delivery-kpi-finished">
                <div class="src-delivery-kpi-label"><span></span>Devueltos / Finalizados</div>
                <strong>{{ $finalizadas }}</strong>
                <small>Entregas completadas</small>
            </article>
        </section>

        <form class="src-delivery-toolbar" aria-label="Filtros de entregas" action="{{ url()->current() }}" method="GET" data-delivery-server-form>
            @foreach (request()->except(['estado', 'search', 'page']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $nestedKey => $nestedValue)
                        <input type="hidden" name="{{ $key }}[{{ $nestedKey }}]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="estado" value="{{ $selectedEstado }}" data-delivery-state>
            <div class="src-delivery-tabs" role="group" aria-label="Filtrar por estado">
                <button type="button" class="src-delivery-tab {{ $selectedEstado === '' ? 'is-active' : '' }}" data-delivery-filter="all" data-delivery-query-value="" aria-pressed="{{ $selectedEstado === '' ? 'true' : 'false' }}">
                    Todas <span>{{ $totalReservas }}</span>
                </button>
                <button type="button" class="src-delivery-tab {{ $selectedEstado === 'confirmada' ? 'is-active' : '' }}" data-delivery-filter="confirmed" data-delivery-query-value="confirmada" aria-pressed="{{ $selectedEstado === 'confirmada' ? 'true' : 'false' }}">
                    Por entregar <span>{{ $porEntregar }}</span>
                </button>
                <button type="button" class="src-delivery-tab {{ $selectedEstado === 'entregada' ? 'is-active' : '' }}" data-delivery-filter="in-use" data-delivery-query-value="entregada" aria-pressed="{{ $selectedEstado === 'entregada' ? 'true' : 'false' }}">
                    Por devolver (En uso) <span>{{ $enUso }}</span>
                </button>
                <button type="button" class="src-delivery-tab {{ $selectedEstado === 'finalizadas' ? 'is-active' : '' }}" data-delivery-filter="finished" data-delivery-query-value="finalizadas" aria-pressed="{{ $selectedEstado === 'finalizadas' ? 'true' : 'false' }}">
                    Finalizadas <span>{{ $finalizadas }}</span>
                </button>
            </div>

            <label class="src-delivery-search" for="deliverySearch">
                <i class="fas fa-search" aria-hidden="true"></i>
                <span class="sr-only">Buscar por usuario, serial o laboratorio</span>
                <input type="search" id="deliverySearch" name="search" value="{{ request()->query('search', '') }}" placeholder="Buscar por usuario, serial, aula..." autocomplete="off">
            </label>
        </form>

        <section class="src-delivery-list" id="deliveryList" aria-label="Reservas por atender">
            @forelse ($reservas as $reserva)
                @php
                    $nombreUsuario = $reserva->user->name ?? 'Usuario no disponible';
                    $correoUsuario = $reserva->user->email ?? '';
                    $nombreEquipo = trim(($reserva->computador->marca ?? 'Computador') . ' ' . ($reserva->computador->modelo ?? ''));
                    $serial = $reserva->computador->serial ?? 'Sin serial';
                    $bloqueUbicacion = $reserva->computador->aula->bloque ?? 'Sin bloque';
                    $aulaUbicacion = $reserva->computador->aula->nombre ?? 'Sin laboratorio';
                    $iniciales = collect(preg_split('/\s+/', trim($nombreUsuario)) ?: [])
                        ->filter()
                        ->take(2)
                        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8'))
                        ->implode('');
                    $fechaLabel = \Illuminate\Support\Carbon::parse($reserva->fecha)
                        ->locale('es')
                        ->translatedFormat('d M Y');
                    $horaInicio = $reserva->bloque
                        ? \Illuminate\Support\Carbon::parse($reserva->bloque->hora_inicio)->format('H:i')
                        : '';
                    $horaFin = $reserva->bloque
                        ? \Illuminate\Support\Carbon::parse($reserva->bloque->hora_fin)->format('H:i')
                        : '';
                    $duracionHoras = $reserva->bloque
                        ? (int) round(\Illuminate\Support\Carbon::parse($reserva->bloque->hora_inicio)
                            ->diffInMinutes(\Illuminate\Support\Carbon::parse($reserva->bloque->hora_fin)) / 60)
                        : 0;
                    $filtroEstado = match ($reserva->estado) {
                        'confirmada' => 'confirmed',
                        'entregada' => 'in-use',
                        default => 'finished',
                    };
                    $textoBusqueda = \Illuminate\Support\Str::lower(implode(' ', [
                        $nombreUsuario,
                        $correoUsuario,
                        $nombreEquipo,
                        $serial,
                        $bloqueUbicacion,
                        $aulaUbicacion,
                    ]));
                @endphp

                <article
                    class="src-delivery-card"
                    data-delivery-status="{{ $filtroEstado }}"
                    data-delivery-search="{{ $textoBusqueda }}"
                >
                    <div class="src-delivery-details">
                        <div class="src-delivery-user">
                            <span class="src-delivery-avatar" aria-hidden="true">{{ $iniciales }}</span>
                            <div>
                                <strong>{{ $nombreUsuario }}</strong>
                                <span>{{ $correoUsuario }}</span>
                            </div>
                        </div>

                        <div class="src-delivery-equipment">
                            <span class="src-delivery-equipment-icon" aria-hidden="true">
                                <i class="fas fa-desktop"></i>
                            </span>
                            <div>
                                <strong>{{ $nombreEquipo }}</strong>
                                <div class="src-delivery-equipment-meta">
                                    <span class="src-delivery-serial">Serial: {{ $serial }}</span>
                                    <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ $bloqueUbicacion }} · {{ $aulaUbicacion }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="src-delivery-schedule">
                        <span class="src-delivery-clock" aria-hidden="true"><i class="far fa-clock"></i></span>
                        <div>
                            <strong>{{ ucwords($fechaLabel) }}</strong>
                            <span>{{ $horaInicio }} - {{ $horaFin }}@if ($duracionHoras) <small>({{ $duracionHoras }} horas)</small>@endif</span>
                            @if ($reserva->entregado_at)
                                <small>Entrega registrada: {{ $reserva->entregado_at->locale('es')->translatedFormat('d M · H:i') }}</small>
                            @endif
                            @if ($reserva->devuelto_at)
                                <small>Devolución registrada: {{ $reserva->devuelto_at->locale('es')->translatedFormat('d M · H:i') }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="src-delivery-actions">
                        @if ($reserva->estado === 'confirmada')
                            <span class="src-delivery-badge src-delivery-badge-{{ $reserva->estado_color }}">
                                <span aria-hidden="true"></span>{{ $reserva->estado_etiqueta }}
                            </span>
                            @can('registrar-entregas')
                                <form method="POST" action="{{ route('entregas.entregar', $reserva) }}">
                                    @csrf
                                    <button type="submit" class="src-delivery-action src-delivery-action-handover">
                                        Registrar entrega <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endcan
                        @elseif ($reserva->estado === 'entregada')
                            <span class="src-delivery-badge src-delivery-badge-in-use">
                                <span aria-hidden="true"></span>{{ $reserva->estado_etiqueta }}
                            </span>
                            @can('registrar-devoluciones')
                                <form method="POST" action="{{ route('entregas.devolver', $reserva) }}">
                                    @csrf
                                    <button type="submit" class="src-delivery-action src-delivery-action-return">
                                        <i class="fas fa-undo-alt" aria-hidden="true"></i> Registrar devolución
                                    </button>
                                </form>
                            @endcan
                        @else
                            <span class="src-delivery-badge src-delivery-badge-{{ $reserva->estado_color }}">
                                <span aria-hidden="true"></span>{{ $reserva->estado_etiqueta }}
                            </span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="src-delivery-empty">
                    <i class="fas fa-hand-holding" aria-hidden="true"></i>
                    <strong>No hay reservas de computadores</strong>
                    <span>Las reservas confirmadas, en uso y finalizadas aparecerán aquí.</span>
                </div>
            @endforelse

            @if ($reservas->isNotEmpty())
                <div class="src-delivery-empty src-delivery-filter-empty" id="deliveryFilterEmpty" hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos registros</strong>
                    <span>Prueba con otro estado, nombre, serial o laboratorio.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$reservas" />
    </div>
@stop

@section('js')
    <script>
        (function () {
            var list = document.getElementById('deliveryList');
            var search = document.getElementById('deliverySearch');
            if (!list || !search) return;

            var form = document.querySelector('[data-delivery-server-form]');
            var stateInput = document.querySelector('[data-delivery-state]');
            var tabs = document.querySelectorAll('[data-delivery-filter]');

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    stateInput.value = tab.dataset.deliveryQueryValue || '';
                    tabs.forEach(function (item) {
                        var selected = item === tab;
                        item.classList.toggle('is-active', selected);
                        item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    });
                    form.submit();
                });
            });

            var searchTimeout;
            search.addEventListener('input', function () {
                window.clearTimeout(searchTimeout);
                searchTimeout = window.setTimeout(function () {
                    form.submit();
                }, 350);
            });
        })();
    </script>
@stop
