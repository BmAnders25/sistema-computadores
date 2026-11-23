@extends('adminlte::page')

@section('title', 'Disponibilidad de computadores')

@section('content')
    <div class="src-availability-page">
        <section class="src-availability-hero">
            <div>
                <span class="src-availability-eyebrow">TU PRÓXIMO EQUIPO</span>
                <h2>Encuentra el computador ideal</h2>
                <p>Elige cuándo lo necesitas y reserva un equipo disponible.</p>
            </div>
            <div class="src-availability-hero-icon" aria-hidden="true">
                <i class="fas fa-laptop"></i>
            </div>
        </section>

        <section class="src-availability-filter">
            <div class="src-availability-filter-title">
                <span class="src-availability-icon" aria-hidden="true">
                    <i class="far fa-calendar-alt"></i>
                </span>
                <div>
                    <h2>Selecciona tu horario</h2>
                    <p>Los horarios se muestran en hora local.</p>
                    <p class="src-availability-hint">El rango debe coincidir con un bloque horario activo.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('disponibilidad.index') }}" class="src-availability-form">
                <div class="src-availability-field">
                    <label for="fecha_inicio">Fecha y hora de inicio</label>
                    <input
                        type="datetime-local"
                        id="fecha_inicio"
                        name="fecha_inicio"
                        value="{{ $fechaInicio }}"
                        required
                    >
                </div>
                <div class="src-availability-field">
                    <label for="fecha_fin">Fecha y hora de finalización</label>
                    <input
                        type="datetime-local"
                        id="fecha_fin"
                        name="fecha_fin"
                        value="{{ $fechaFin }}"
                        required
                    >
                </div>
                <button type="submit" class="btn btn-primary src-availability-submit">
                    <i class="fas fa-search mr-1" aria-hidden="true"></i>
                    Ver disponibilidad
                </button>
            </form>

            @if ($validationErrors->isNotEmpty())
                <div class="alert alert-danger src-availability-errors" role="alert">
                    <ul class="mb-0">
                        @foreach ($validationErrors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="src-availability-results">
            <div class="src-availability-results-heading">
                <div>
                    <span class="src-availability-eyebrow src-availability-eyebrow-muted">HORARIO SELECCIONADO</span>
                    <h2>Equipos disponibles <span class="src-availability-count">{{ $computadores->count() }}</span></h2>
                    @if ($fechaInicio && $fechaFin)
                        <p>
                            <i class="far fa-clock" aria-hidden="true"></i>
                            {{ \Illuminate\Support\Carbon::parse($fechaInicio)->format('d/m/Y H:i') }}
                            a {{ \Illuminate\Support\Carbon::parse($fechaFin)->format('d/m/Y H:i') }}
                        </p>
                    @endif
                </div>
                @can('ver-reservas')
                    <a href="{{ route('reservas.index') }}" class="btn btn-outline-secondary src-availability-reservations">
                        <i class="fas fa-calendar-check mr-1" aria-hidden="true"></i>
                        Mis reservas
                    </a>
                @endcan
            </div>

            @if ($bloques->isEmpty())
                <div class="src-availability-empty">
                    <i class="far fa-calendar-times" aria-hidden="true"></i>
                    <p>No hay bloques horarios activos para consultar disponibilidad.</p>
                </div>
            @elseif ($validationErrors->isNotEmpty())
                <div class="src-availability-empty">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <p>Corrige el horario para consultar los equipos disponibles.</p>
                </div>
            @elseif ($computadores->isEmpty())
                <div class="src-availability-empty">
                    <i class="fas fa-desktop" aria-hidden="true"></i>
                    <p>No hay computadores disponibles para este horario.</p>
                </div>
            @else
                <div class="src-computer-grid">
                    @foreach ($computadores as $computador)
                        <article class="src-computer-card">
                            <div class="src-computer-card-top">
                                <span class="src-availability-icon" aria-hidden="true">
                                    <i class="fas fa-desktop"></i>
                                </span>
                                <span class="src-availability-status">
                                    <span aria-hidden="true"></span>
                                    Disponible
                                </span>
                            </div>

                            <div class="src-computer-name">
                                <span>{{ strtoupper(str_replace('_', ' ', $computador->tipo_equipo)) }}</span>
                                <h3>{{ $computador->marca }} {{ $computador->modelo }}</h3>
                                <p>Serial {{ $computador->serial }}</p>
                            </div>

                            <div class="src-computer-location">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <div>
                                    <strong>{{ $computador->aula->bloque }}</strong>
                                    <span>{{ $computador->aula->nombre }}</span>
                                </div>
                            </div>

                            <ul class="src-computer-specs">
                                <li><i class="fas fa-microchip" aria-hidden="true"></i>{{ $computador->procesador ?: 'Procesador no especificado' }}</li>
                                <li><i class="fas fa-memory" aria-hidden="true"></i>{{ $computador->ram_gb ? $computador->ram_gb . ' GB RAM' : 'RAM no especificada' }}</li>
                                <li><i class="fas fa-hdd" aria-hidden="true"></i>{{ $computador->almacenamiento ?: 'Almacenamiento no especificado' }}</li>
                            </ul>

                            @can('crear-reservas')
                                <a
                                    class="btn btn-primary src-computer-reserve"
                                    href="{{ route('reservas.create', [
                                        'tipo' => 'equipo',
                                        'computador_id' => $computador->id,
                                        'fecha' => \Illuminate\Support\Carbon::parse($fechaInicio)->toDateString(),
                                        'bloque_horario_id' => $bloqueSeleccionado->id,
                                        'fecha_inicio' => $fechaInicio,
                                        'fecha_fin' => $fechaFin,
                                    ]) }}"
                                >
                                    Reservar equipo <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i>
                                </a>
                            @endcan
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@stop
