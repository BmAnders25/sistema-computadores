@extends('adminlte::page')

@section('title', 'Nueva Reserva')

@php
    $selectedType = old('tipo', $tipo) === 'aula' && auth()->user()->can('reservar-aulas') ? 'aula' : 'equipo';
    $selectedDate = old('fecha', $fecha ?: ($fechaInicio ? substr($fechaInicio, 0, 10) : today()->toDateString()));
    $selectedBlockId = old('bloque_horario_id', $bloqueHorarioId);
    $selectedComputerId = old('computador_id', $computadorId);
    $selectedClassroomId = old('aula_id');

    if (!$selectedBlockId && $fechaInicio && $fechaFin) {
        $startTime = substr($fechaInicio, 11, 5);
        $endTime = substr($fechaFin, 11, 5);
        $matchingBlock = $bloques->first(function ($bloque) use ($startTime, $endTime) {
            return substr($bloque->hora_inicio, 0, 5) === $startTime
                && substr($bloque->hora_fin, 0, 5) === $endTime;
        });
        $selectedBlockId = optional($matchingBlock)->id;
    }
@endphp

@section('content_header')
    <div class="src-new-reservation-hero">
        <x-src-page-hero
            eyebrow="Nueva reserva"
            title="Reserva tu computador"
            description="Selecciona el horario y el equipo que necesitas en 3 simples pasos."
            icon="far fa-calendar-check"
        />
    </div>
@stop

@section('content')
    <div class="src-new-reservation">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Revisa los datos de tu reserva:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('reservas.store') }}" id="reservaForm">
            @csrf

            <input type="hidden" name="tipo" id="tipoSelect" value="{{ $selectedType }}">
            <input type="hidden" name="bloque_horario_id" id="bloque_horario_id" value="{{ $selectedBlockId }}">
            <input type="hidden" name="computador_id" id="computador_id" value="{{ $selectedComputerId }}">
            <input type="hidden" name="aula_id" id="aula_id" value="{{ $selectedClassroomId }}">

            <div class="src-reservation-layout">
                <div class="src-reservation-steps">
                    <section class="src-booking-step" aria-labelledby="booking-step-type">
                        <div class="src-booking-step-body">
                            <div class="src-booking-step-heading">
                                <span class="src-booking-step-number">1</span>
                                <div>
                                    <h2 id="booking-step-type">Modalidad de reserva</h2>
                                    <p>Elige el recurso que necesitas.</p>
                                </div>
                            </div>

                            <div class="src-booking-type-grid" role="group" aria-label="Modalidad de reserva">
                                <button
                                    type="button"
                                    class="src-booking-option {{ $selectedType === 'equipo' ? 'is-selected' : '' }}"
                                    data-reservation-type="equipo"
                                    aria-pressed="{{ $selectedType === 'equipo' ? 'true' : 'false' }}"
                                >
                                    <span class="src-booking-option-icon" aria-hidden="true"><i class="fas fa-desktop"></i></span>
                                    <span class="src-booking-option-copy">
                                        <strong>Computador Individual</strong>
                                        <small>Reserva un equipo para tu horario.</small>
                                    </span>
                                    <span class="src-booking-option-check" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                                </button>

                                @can('reservar-aulas')
                                    <button
                                        type="button"
                                        class="src-booking-option {{ $selectedType === 'aula' ? 'is-selected' : '' }}"
                                        data-reservation-type="aula"
                                        aria-pressed="{{ $selectedType === 'aula' ? 'true' : 'false' }}"
                                    >
                                        <span class="src-booking-option-icon" aria-hidden="true"><i class="fas fa-door-open"></i></span>
                                        <span class="src-booking-option-copy">
                                            <strong>Sala Completa / Laboratorio</strong>
                                            <small>Solicita un espacio institucional.</small>
                                        </span>
                                        <span class="src-booking-option-check" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </section>

                    <section class="src-booking-step" aria-labelledby="booking-step-schedule">
                        <div class="src-booking-step-body">
                            <div class="src-booking-step-heading">
                                <span class="src-booking-step-number">2</span>
                                <div>
                                    <h2 id="booking-step-schedule">Fecha y bloque horario</h2>
                                    <p>Selecciona un horario disponible para tu reserva.</p>
                                </div>
                            </div>

                            <div class="src-booking-schedule-grid">
                                <div class="src-booking-date-field">
                                    <label for="fecha">Fecha de la reserva</label>
                                    <div class="src-booking-date-control">
                                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                                        <input
                                            type="date"
                                            name="fecha"
                                            id="fecha"
                                            value="{{ $selectedDate }}"
                                            min="{{ today()->toDateString() }}"
                                            required
                                        >
                                    </div>
                                    @error('fecha')
                                        <span class="src-booking-field-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="src-booking-block-field">
                                    <span class="src-booking-field-label">Bloque horario</span>
                                    <div class="src-booking-block-grid" role="group" aria-label="Bloques horarios">
                                        @foreach ($bloques as $bloque)
                                            @php
                                                $horaInicio = \Illuminate\Support\Carbon::parse($bloque->hora_inicio)->format('H:i');
                                                $horaFin = \Illuminate\Support\Carbon::parse($bloque->hora_fin)->format('H:i');
                                                $bloqueSeleccionado = (string) $selectedBlockId === (string) $bloque->id;
                                            @endphp
                                            <button
                                                type="button"
                                                class="src-booking-chip {{ $bloqueSeleccionado ? 'is-selected' : '' }}"
                                                data-block-id="{{ $bloque->id }}"
                                                data-start="{{ $horaInicio }}"
                                                data-end="{{ $horaFin }}"
                                                aria-pressed="{{ $bloqueSeleccionado ? 'true' : 'false' }}"
                                            >
                                                <i class="far fa-clock" aria-hidden="true"></i>
                                                <span>{{ $horaInicio }} - {{ $horaFin }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                    @error('bloque_horario_id')
                                        <span class="src-booking-field-error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="src-booking-step" aria-labelledby="booking-step-resource">
                        <div class="src-booking-step-body">
                            <div class="src-booking-step-heading">
                                <span class="src-booking-step-number">3</span>
                                <div>
                                    <h2 id="booking-step-resource">Elige tu recurso</h2>
                                    <p id="srcResourceHint">Selecciona un computador disponible.</p>
                                </div>
                            </div>

                            <div id="seccionComputador" class="src-booking-resource-grid">
                                @forelse ($computadores as $comp)
                                    @php
                                        $computadorSeleccionado = $selectedType === 'equipo'
                                            && (string) $selectedComputerId === (string) $comp->id;
                                        $ubicacionComputador = trim(($comp->bloque ?? optional($comp->aula)->bloque ?? '') . ', '
                                            . ($comp->laboratorio_aula ?? optional($comp->aula)->nombre ?? 'Aula sin asignar'), ', ');
                                    @endphp
                                    <button
                                        type="button"
                                        class="src-booking-resource {{ $computadorSeleccionado ? 'is-selected' : '' }}"
                                        data-computer-id="{{ $comp->id }}"
                                        data-resource-name="{{ trim($comp->marca . ' ' . $comp->modelo) }}"
                                        data-resource-serial="{{ $comp->serial }}"
                                        data-resource-location="{{ $ubicacionComputador }}"
                                        aria-pressed="{{ $computadorSeleccionado ? 'true' : 'false' }}"
                                        aria-label="Seleccionar {{ $comp->marca }} {{ $comp->modelo }} serial {{ $comp->serial }}"
                                    >
                                        <span class="src-booking-resource-icon" aria-hidden="true"><i class="fas fa-desktop"></i></span>
                                        <span class="src-booking-resource-content">
                                            <span class="src-booking-resource-title">{{ $comp->marca }} {{ $comp->modelo }}</span>
                                            <span class="src-booking-resource-details">
                                                <span class="src-booking-serial">{{ $comp->serial }}</span>
                                                <span class="src-booking-location">
                                                    Aula: {{ $comp->laboratorio_aula ?? optional($comp->aula)->nombre ?? 'Sin aula' }}
                                                    @if ($comp->bloque ?? optional($comp->aula)->bloque)
                                                        · {{ $comp->bloque ?? optional($comp->aula)->bloque }}
                                                    @endif
                                                </span>
                                            </span>
                                        </span>
                                        <span class="src-booking-resource-status"><span></span>Disponible</span>
                                        <span class="src-booking-resource-check" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                                    </button>
                                @empty
                                    <p class="src-booking-empty">No hay computadores disponibles para seleccionar.</p>
                                @endforelse
                            </div>

                            <div id="seccionAula" class="src-booking-resource-grid" hidden>
                                @forelse ($aulas as $aula)
                                    @php
                                        $aulaSeleccionada = $selectedType === 'aula'
                                            && (string) $selectedClassroomId === (string) $aula->id;
                                    @endphp
                                    <button
                                        type="button"
                                        class="src-booking-resource src-booking-classroom {{ $aulaSeleccionada ? 'is-selected' : '' }}"
                                        data-classroom-id="{{ $aula->id }}"
                                        data-resource-name="{{ $aula->nombre }}"
                                        data-resource-serial="Capacidad {{ $aula->capacidad }}"
                                        data-resource-location="{{ $aula->bloque }}"
                                        aria-pressed="{{ $aulaSeleccionada ? 'true' : 'false' }}"
                                        aria-label="Seleccionar {{ $aula->bloque }} {{ $aula->nombre }}"
                                    >
                                        <span class="src-booking-resource-icon" aria-hidden="true"><i class="fas fa-door-open"></i></span>
                                        <span class="src-booking-resource-content">
                                            <span class="src-booking-resource-title">{{ $aula->nombre }}</span>
                                            <span class="src-booking-resource-details">
                                                <span class="src-booking-serial">{{ $aula->bloque }}</span>
                                                <span class="src-booking-location">Capacidad: {{ $aula->capacidad }} personas</span>
                                            </span>
                                        </span>
                                        <span class="src-booking-resource-status src-booking-resource-status-room"><span></span>Habilitada</span>
                                        <span class="src-booking-resource-check" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                                    </button>
                                @empty
                                    <p class="src-booking-empty">No hay aulas habilitadas para seleccionar.</p>
                                @endforelse
                            </div>

                            @error('computador_id')
                                <span class="src-booking-field-error">{{ $message }}</span>
                            @enderror
                            @error('aula_id')
                                <span class="src-booking-field-error">{{ $message }}</span>
                            @enderror
                            <p id="srcSelectionError" class="src-booking-field-error" role="alert" hidden></p>
                        </div>
                    </section>
                </div>

                <aside class="src-booking-summary" aria-labelledby="srcSummaryHeading">
                    <div class="src-booking-summary-header">
                        <i class="far fa-clipboard" aria-hidden="true"></i>
                        <h2 id="srcSummaryHeading">Resumen de tu reserva</h2>
                    </div>
                    <div class="src-booking-summary-body">
                        <dl class="src-booking-summary-list">
                            <div class="src-booking-summary-row">
                                <dt><i class="fas fa-desktop" aria-hidden="true"></i> Modalidad</dt>
                                <dd id="srcSummaryType">Computador Individual</dd>
                            </div>
                            <div class="src-booking-summary-row">
                                <dt><i class="far fa-calendar-alt" aria-hidden="true"></i> Fecha</dt>
                                <dd id="srcSummaryDate">Selecciona una fecha</dd>
                            </div>
                            <div class="src-booking-summary-row">
                                <dt><i class="far fa-clock" aria-hidden="true"></i> Horario</dt>
                                <dd id="srcSummaryBlock">Selecciona un bloque</dd>
                            </div>
                            <div class="src-booking-summary-row">
                                <dt><i class="fas fa-laptop" aria-hidden="true"></i> Recurso</dt>
                                <dd>
                                    <span id="srcSummaryResource">Selecciona un recurso</span>
                                    <small id="srcSummaryLocation">Computador o aula</small>
                                </dd>
                            </div>
                        </dl>

                        <button type="submit" class="src-booking-submit">
                            <i class="fas fa-check" aria-hidden="true"></i>
                            Confirmar Reserva
                        </button>
                        <a href="{{ route('reservas.index') }}" class="src-booking-back">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            Volver a mis reservas
                        </a>
                    </div>
                </aside>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script>
        (function () {
            var form = document.getElementById('reservaForm');
            if (!form) return;

            var typeInput = document.getElementById('tipoSelect');
            var dateInput = document.getElementById('fecha');
            var blockInput = document.getElementById('bloque_horario_id');
            var computerInput = document.getElementById('computador_id');
            var classroomInput = document.getElementById('aula_id');
            var computerSection = document.getElementById('seccionComputador');
            var classroomSection = document.getElementById('seccionAula');
            var resourceHint = document.getElementById('srcResourceHint');
            var selectionError = document.getElementById('srcSelectionError');

            var summaryType = document.getElementById('srcSummaryType');
            var summaryDate = document.getElementById('srcSummaryDate');
            var summaryBlock = document.getElementById('srcSummaryBlock');
            var summaryResource = document.getElementById('srcSummaryResource');
            var summaryLocation = document.getElementById('srcSummaryLocation');

            function setPressed(buttons, selectedButton) {
                buttons.forEach(function (button) {
                    var selected = button === selectedButton;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            }

            function selectedResource() {
                var selector = typeInput.value === 'aula'
                    ? '[data-classroom-id]'
                    : '[data-computer-id]';
                var resourceId = typeInput.value === 'aula' ? classroomInput.value : computerInput.value;
                return Array.prototype.find.call(
                    document.querySelectorAll(selector),
                    function (card) {
                        return card.dataset.classroomId === resourceId || card.dataset.computerId === resourceId;
                    }
                );
            }

            function updateSummary() {
                var selectedTypeButton = document.querySelector('[data-reservation-type="' + typeInput.value + '"]');
                summaryType.textContent = selectedTypeButton
                    ? selectedTypeButton.querySelector('strong').textContent
                    : 'Computador Individual';

                if (dateInput.value) {
                    var date = new Date(dateInput.value + 'T12:00:00');
                    summaryDate.textContent = Number.isNaN(date.getTime())
                        ? dateInput.value
                        : new Intl.DateTimeFormat('es-CO', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        }).format(date);
                } else {
                    summaryDate.textContent = 'Selecciona una fecha';
                }

                var selectedBlock = document.querySelector('.src-booking-chip.is-selected');
                if (selectedBlock) {
                    var startParts = selectedBlock.dataset.start.split(':');
                    var endParts = selectedBlock.dataset.end.split(':');
                    var minutes = (Number(endParts[0]) * 60 + Number(endParts[1]))
                        - (Number(startParts[0]) * 60 + Number(startParts[1]));
                    var duration = minutes >= 60 && minutes % 60 === 0
                        ? ' (' + (minutes / 60) + ' horas)'
                        : '';
                    summaryBlock.textContent = selectedBlock.dataset.start + ' - '
                        + selectedBlock.dataset.end + duration;
                } else {
                    summaryBlock.textContent = 'Selecciona un bloque';
                }

                var resource = selectedResource();
                if (resource) {
                    summaryResource.textContent = resource.dataset.resourceName
                        + (resource.dataset.resourceSerial ? ' ' + resource.dataset.resourceSerial : '');
                    summaryLocation.textContent = resource.dataset.resourceLocation || '';
                } else {
                    summaryResource.textContent = 'Selecciona un recurso';
                    summaryLocation.textContent = typeInput.value === 'aula'
                        ? 'Sala o laboratorio'
                        : 'Computador disponible';
                }
            }

            function selectType(button) {
                typeInput.value = button.dataset.reservationType;
                setPressed(document.querySelectorAll('[data-reservation-type]'), button);

                if (typeInput.value === 'aula') {
                    computerInput.value = '';
                    computerSection.hidden = true;
                    if (classroomSection) classroomSection.hidden = false;
                    resourceHint.textContent = 'Selecciona un aula o laboratorio habilitado.';
                } else {
                    classroomInput.value = '';
                    if (classroomSection) classroomSection.hidden = true;
                    computerSection.hidden = false;
                    resourceHint.textContent = 'Selecciona un computador disponible.';
                }

                updateSummary();
            }

            document.querySelectorAll('[data-reservation-type]').forEach(function (button) {
                button.addEventListener('click', function () {
                    selectType(button);
                });
            });

            document.querySelectorAll('.src-booking-chip').forEach(function (button) {
                button.addEventListener('click', function () {
                    blockInput.value = button.dataset.blockId;
                    setPressed(document.querySelectorAll('.src-booking-chip'), button);
                    updateSummary();
                });
            });

            document.querySelectorAll('[data-computer-id]').forEach(function (card) {
                card.addEventListener('click', function () {
                    computerInput.value = card.dataset.computerId;
                    setPressed(document.querySelectorAll('[data-computer-id]'), card);
                    updateSummary();
                });
            });

            document.querySelectorAll('[data-classroom-id]').forEach(function (card) {
                card.addEventListener('click', function () {
                    classroomInput.value = card.dataset.classroomId;
                    setPressed(document.querySelectorAll('[data-classroom-id]'), card);
                    updateSummary();
                });
            });

            dateInput.addEventListener('change', updateSummary);

            form.addEventListener('submit', function (event) {
                var hasResource = typeInput.value === 'aula' ? classroomInput.value : computerInput.value;
                var missing = !dateInput.value || !blockInput.value || !hasResource;

                if (missing) {
                    event.preventDefault();
                    selectionError.textContent = !dateInput.value
                        ? 'Selecciona una fecha para continuar.'
                        : !blockInput.value
                            ? 'Selecciona un bloque horario para continuar.'
                            : 'Selecciona el recurso que deseas reservar.';
                    selectionError.hidden = false;
                    selectionError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                selectionError.hidden = true;
            });

            document.querySelectorAll('[data-reservation-type]').forEach(function (button) {
                if (button.dataset.reservationType === typeInput.value) {
                    setPressed(document.querySelectorAll('[data-reservation-type]'), button);
                }
            });
            document.querySelectorAll('.src-booking-chip').forEach(function (button) {
                if (button.dataset.blockId === blockInput.value) {
                    button.classList.add('is-selected');
                    button.setAttribute('aria-pressed', 'true');
                }
            });

            if (typeInput.value === 'aula') {
                computerSection.hidden = true;
                if (classroomSection) classroomSection.hidden = false;
                resourceHint.textContent = 'Selecciona un aula o laboratorio habilitado.';
            } else if (classroomSection) {
                classroomSection.hidden = true;
            }

            updateSummary();
        })();
    </script>
@stop
