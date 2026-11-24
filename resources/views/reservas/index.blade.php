@extends('adminlte::page')

@section('title', 'Mis reservas')

@section('content_header')
    <section class="src-reservations-hero">
        <div>
            <span class="src-availability-eyebrow">TU ACTIVIDAD</span>
            <h2>Tus reservas, en un solo lugar</h2>
            <p>Revisa el estado, el horario y la ubicación de tus equipos.</p>
        </div>
        <div class="src-reservations-hero-actions">
            @can('crear-reservas')
                <a class="src-reservation-new-button" href="{{ route('reservas.create') }}">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Nueva reserva
                </a>
            @endcan
            <div class="src-availability-hero-icon" aria-hidden="true">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </section>
@stop

@section('content')
    @php
        $selectedEstado = request()->query('estado', '');
    @endphp

    <div class="src-reservations-page">
        @if ($message = Session::get('success'))
            <div class="alert alert-success" role="status">{{ $message }}</div>
        @endif
        @if ($message = Session::get('error'))
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @endif
        @if ($message = Session::get('warning'))
            <div class="alert alert-warning" role="alert">{{ $message }}</div>
        @endif

        <div class="src-reservations-heading">
            <h3>Historial de reservas</h3>
        </div>

        <form class="src-reservations-toolbar" aria-label="Controles del historial" action="{{ url()->current() }}" method="GET" data-reservation-server-form>
            @foreach (request()->except(['estado', 'search', 'page']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $nestedKey => $nestedValue)
                        <input type="hidden" name="{{ $key }}[{{ $nestedKey }}]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="estado" value="{{ $selectedEstado }}" data-reservation-state>
            <div class="src-reservation-tabs" role="group" aria-label="Filtrar por estado">
                <button type="button" class="src-reservation-tab {{ $selectedEstado === '' ? 'is-active' : '' }}" data-reservation-filter="all" data-reservation-query-value="" aria-pressed="{{ $selectedEstado === '' ? 'true' : 'false' }}">
                    Todas <span>{{ $totalReservas }}</span>
                </button>
                <button type="button" class="src-reservation-tab {{ $selectedEstado === 'pendiente' ? 'is-active' : '' }}" data-reservation-filter="pending" data-reservation-query-value="pendiente" aria-pressed="{{ $selectedEstado === 'pendiente' ? 'true' : 'false' }}">
                    Reservadas <span class="src-reservation-tab-count-pending">{{ $pendientesCount }}</span>
                </button>
                <button type="button" class="src-reservation-tab {{ $selectedEstado === 'confirmada' ? 'is-active' : '' }}" data-reservation-filter="confirmed" data-reservation-query-value="confirmada" aria-pressed="{{ $selectedEstado === 'confirmada' ? 'true' : 'false' }}">
                    Confirmadas (legado) <span>{{ $confirmadasCount }}</span>
                </button>
                <button type="button" class="src-reservation-tab {{ $selectedEstado === 'entregada' ? 'is-active' : '' }}" data-reservation-filter="in-use" data-reservation-query-value="entregada" aria-pressed="{{ $selectedEstado === 'entregada' ? 'true' : 'false' }}">
                    En uso <span>{{ $enUsoCount }}</span>
                </button>
                <button type="button" class="src-reservation-tab {{ $selectedEstado === 'historial' ? 'is-active' : '' }}" data-reservation-filter="history" data-reservation-query-value="historial" aria-pressed="{{ $selectedEstado === 'historial' ? 'true' : 'false' }}">
                    Historial <span>{{ $historialCount }}</span>
                </button>
            </div>

            <div class="src-reservation-tools">
                <label class="src-reservation-search" for="reservationSearch">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span class="sr-only">Buscar por usuario, equipo, serial o ubicación</span>
                    <input type="search" id="reservationSearch" name="search" value="{{ request()->query('search', '') }}" placeholder="Buscar por usuario, equipo..." autocomplete="off">
                </label>

                <div class="src-reservation-view-toggle" role="group" aria-label="Tipo de vista">
                    <button type="button" class="is-active" data-reservation-view="cards" aria-label="Vista en tarjetas" aria-pressed="true">
                        <i class="fas fa-list" aria-hidden="true"></i>
                    </button>
                    <button type="button" data-reservation-view="compact" aria-label="Vista compacta" aria-pressed="false">
                        <i class="fas fa-th-large" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </form>

        <section class="src-reservations-list" id="reservationsList" aria-label="Reservas">
            @forelse ($reservas as $reserva)
                @php
                    $nombreSolicitante = $reserva->user->name ?? 'Usuario';
                    $correoSolicitante = $reserva->user->email ?? '';
                    $rolSolicitante = optional($reserva->user->roles->first())->name ?? 'Usuario';
                    $rolSolicitante = $rolSolicitante === 'Administracion' ? 'Administrador' : $rolSolicitante;
                    $iniciales = collect(preg_split('/\s+/', trim($nombreSolicitante)) ?: [])
                        ->filter()
                        ->take(2)
                        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8'))
                        ->implode('');

                    if ($reserva->computador_id) {
                        $nombreRecurso = trim(($reserva->computador->marca ?? 'Computador') . ' ' . ($reserva->computador->modelo ?? ''));
                        $serialRecurso = $reserva->computador->serial ?? 'Sin serial';
                        $bloqueUbicacion = $reserva->computador->aula->bloque
                            ?? $reserva->computador->bloque
                            ?? 'Sin bloque';
                        $aulaUbicacion = $reserva->computador->aula->nombre
                            ?? $reserva->computador->laboratorio_aula
                            ?? 'Sin aula';
                    } else {
                        $nombreRecurso = $reserva->aula->nombre ?? 'Aula completa';
                        $serialRecurso = 'Reserva de espacio';
                        $bloqueUbicacion = $reserva->aula->bloque ?? 'Sin bloque';
                        $aulaUbicacion = $reserva->aula->nombre ?? 'Aula';
                    }

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
                    $duracionLabel = $duracionHoras === 1 ? '1 hora' : $duracionHoras . ' horas';

                    $filtroEstado = match ($reserva->estado) {
                        'pendiente' => 'pending',
                        'confirmada' => 'confirmed',
                        'entregada' => 'in-use',
                        default => 'history',
                    };
                    $estadoLabel = $reserva->estado_etiqueta;
                    $estadoClass = $reserva->estado_color;
                    $textoBusqueda = \Illuminate\Support\Str::lower(implode(' ', [
                        $nombreSolicitante,
                        $correoSolicitante,
                        $rolSolicitante,
                        $nombreRecurso,
                        $serialRecurso,
                        $bloqueUbicacion,
                        $aulaUbicacion,
                        $fechaLabel,
                        $horaInicio,
                        $horaFin,
                    ]));
                @endphp

                <article
                    class="src-reservation-item"
                    data-reservation-status="{{ $filtroEstado }}"
                    data-search="{{ $textoBusqueda }}"
                >
                    <div class="src-reservation-details">
                        <div class="src-reservation-person">
                            <span class="src-reservation-avatar" aria-hidden="true">{{ $iniciales }}</span>
                            <div>
                                <strong>{{ $nombreSolicitante }}</strong>
                                <span>{{ $rolSolicitante }}@if ($correoSolicitante) · {{ $correoSolicitante }}@endif</span>
                            </div>
                        </div>

                        <div class="src-reservation-resource">
                            <span class="src-reservation-resource-icon" aria-hidden="true">
                                <i class="{{ $reserva->computador_id ? 'fas fa-desktop' : 'fas fa-door-open' }}"></i>
                            </span>
                            <div>
                                <strong>{{ $nombreRecurso }}</strong>
                                <span class="src-reservation-resource-meta">
                                    <span class="src-reservation-serial">Serial: {{ $serialRecurso }}</span>
                                    <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ $bloqueUbicacion }} · {{ $aulaUbicacion }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="src-reservation-schedule">
                        <span class="src-reservation-schedule-icon" aria-hidden="true"><i class="far fa-clock"></i></span>
                        <div>
                            <strong>{{ ucfirst($fechaLabel) }}</strong>
                            <span>{{ $horaInicio }} - {{ $horaFin }} <small>({{ $duracionLabel }})</small></span>
                        </div>
                    </div>

                    <div class="src-reservation-actions-panel">
                        <span class="src-reservation-badge src-reservation-badge-{{ $estadoClass }}">
                            <span aria-hidden="true"></span>{{ $estadoLabel }}
                        </span>

                        <div class="src-reservation-action-buttons">
                            <a class="src-reservation-detail-button" href="{{ route('reservas.show', $reserva->id) }}">
                                <i class="far fa-eye" aria-hidden="true"></i>
                                Ver detalle
                            </a>

                            @if ($reserva->aula_id)
                                @can('editar-reservas')
                                    <a class="src-reservation-detail-button" href="{{ route('reservas.edit', $reserva->id) }}">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                        Cambiar estado
                                    </a>
                                @endcan
                            @endif

                            @if ($reserva->estado === 'pendiente')
                                @can('aprobar-reservas')
                                    <form action="{{ route('reservas.confirmar', $reserva->id) }}" method="POST" class="js-reservation-confirmation" data-confirm-kind="confirm">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="src-reservation-confirm-button">
                                            <i class="fas fa-check" aria-hidden="true"></i> Confirmar
                                        </button>
                                    </form>

                                    <form action="{{ route('reservas.destroy', $reserva->id) }}" method="POST" class="js-reservation-confirmation" data-confirm-kind="reject">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="src-reservation-reject-button">
                                            <i class="fas fa-times" aria-hidden="true"></i> Rechazar
                                        </button>
                                    </form>
                                @endcan

                                @can('registrar-entregas')
                                    @php
                                        $esAdministracion = auth()->user()->hasRole('Administracion');
                                        $bloqueIniciado = $reserva->bloque
                                            && now(config('app.timezone'))->greaterThan(
                                                $reserva->bloque->inicioEn($reserva->fecha->toDateString())
                                            );
                                    @endphp
                                    @if ($esAdministracion || (auth()->user()->hasRole('Celador') && $bloqueIniciado))
                                        <form action="{{ route('reservas.no-presentada', $reserva->id) }}" method="POST" class="js-reservation-confirmation" data-confirm-kind="no-show">
                                            @csrf
                                            <button type="submit" class="src-reservation-reject-button">
                                                <i class="fas fa-user-times" aria-hidden="true"></i> No se presentó
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                                @if (! auth()->user()->can('aprobar-reservas'))
                                    @can('cancelar-reservas')
                                        @if ($reserva->user_id === auth()->id())
                                            <form action="{{ route('reservas.destroy', $reserva->id) }}" method="POST" class="js-reservation-confirmation" data-confirm-kind="cancel">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="src-reservation-cancel-button">
                                                    <i class="fas fa-times" aria-hidden="true"></i> Cancelar
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                @endif
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="src-reservations-empty">
                    <i class="far fa-calendar" aria-hidden="true"></i>
                    <strong>Aún no tienes reservas</strong>
                    <span>Cuando realices una reserva, podrás consultar aquí su estado y horario.</span>
                    @can('crear-reservas')
                        <a href="{{ route('reservas.create') }}">Crear una reserva</a>
                    @endcan
                </div>
            @endforelse

            @if ($reservas->isNotEmpty())
                <div class="src-reservations-empty src-reservations-filter-empty" id="reservationsFilterEmpty" hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos reservas</strong>
                    <span>Ajusta el filtro o la búsqueda e inténtalo de nuevo.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$reservas" />
    </div>
@stop

@section('js')
    <script>
        @if ($message = Session::get('success'))
            Swal.fire({
                title: '¡Éxito!',
                text: @json($message),
                timer: 2500,
                icon: 'success',
                confirmButtonColor: '#a6262e'
            });
        @endif

        @if ($message = Session::get('error'))
            Swal.fire({
                title: 'Atención',
                text: @json($message),
                icon: 'error',
                confirmButtonColor: '#a6262e'
            });
        @endif

        (function () {
            var list = document.getElementById('reservationsList');
            if (!list) return;

            var form = document.querySelector('[data-reservation-server-form]');
            var stateInput = document.querySelector('[data-reservation-state]');
            var tabs = document.querySelectorAll('[data-reservation-filter]');
            var search = document.getElementById('reservationSearch');

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    stateInput.value = tab.dataset.reservationQueryValue || '';
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

            document.querySelectorAll('[data-reservation-view]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var compact = button.dataset.reservationView === 'compact';
                    list.classList.toggle('is-compact', compact);
                    document.querySelectorAll('[data-reservation-view]').forEach(function (item) {
                        var selected = item === button;
                        item.classList.toggle('is-active', selected);
                        item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    });
                });
            });

            document.querySelectorAll('.js-reservation-confirmation').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    var kind = form.dataset.confirmKind;
                    var messages = {
                        confirm: {
                            title: '¿Confirmar esta reserva?',
                            text: 'La solicitud será aprobada y quedará pendiente de entrega.',
                            confirmButtonText: 'Sí, confirmar'
                        },
                        reject: {
                            title: '¿Rechazar esta reserva?',
                            text: 'La solicitud será cancelada y el horario quedará liberado.',
                            confirmButtonText: 'Sí, rechazar'
                        },
                        'no-show': {
                            title: '¿Marcar como No se presentó?',
                            text: 'El cupo quedará liberado y se notificará a los celadores.',
                            confirmButtonText: 'Sí, marcar'
                        },
                        cancel: {
                            title: '¿Cancelar tu reserva?',
                            text: 'Esta acción liberará el equipo o aula seleccionada.',
                            confirmButtonText: 'Sí, cancelar'
                        }
                    };
                    var message = messages[kind];

                    Swal.fire({
                        title: message.title,
                        text: message.text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#a6262e',
                        cancelButtonColor: '#778087',
                        confirmButtonText: message.confirmButtonText,
                        cancelButtonText: 'Volver'
                    }).then(function (result) {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });
        })();
    </script>
@stop
