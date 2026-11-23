@extends('adminlte::page')

@section('title', 'Reportes de ocupación')

@section('content_header')
    <x-src-page-hero
        eyebrow="Reportes"
        title="Reporte de ocupación"
        description="Consulta los slots reservados, disponibles y el detalle de ocupación por aula y franja."
        icon="fas fa-chart-bar" />
@stop

@section('content')
    <form class="src-report-filters" method="GET" action="{{ route('reportes.ocupacion') }}">
        <label>
            <span>Desde</span>
            <input type="date" name="desde" value="{{ old('desde', $desde) }}" required>
        </label>
        <label>
            <span>Hasta</span>
            <input type="date" name="hasta" value="{{ old('hasta', $hasta) }}" required>
        </label>
        <label>
            <span>Aula / Laboratorio</span>
            <select name="aula_id">
                <option value="">Todas las aulas</option>
                @foreach ($aulas as $aula)
                    <option value="{{ $aula->id }}" @selected((string) old('aula_id', $aulaId) === (string) $aula->id)>
                        {{ $aula->bloque }} · {{ $aula->nombre }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            <span>Estado</span>
            <select name="estado">
                <option value="">Todos</option>
                @foreach ([
                    'pendiente' => 'Pendiente',
                    'confirmada' => 'Confirmada',
                    'entregada' => 'Entregada',
                    'devuelta' => 'Devuelta',
                    'cancelada' => 'Cancelada',
                    'no_show' => 'No se presentó',
                ] as $valor => $texto)
                    <option value="{{ $valor }}" @selected(old('estado', $estado) === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="src-report-filter-button">
            <i class="fas fa-filter" aria-hidden="true"></i> Aplicar
        </button>
    </form>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="src-report-kpis" aria-label="Resumen de ocupación">
        <article>
            <span><i class="fas fa-chart-pie" aria-hidden="true"></i></span>
            <div>
                <small>% ocupación</small>
                <strong>{{ number_format($kpis['ocupacion'], 2, ',', '.') }}%</strong>
                <p>{{ $kpis['slots_reservados'] }} de {{ $kpis['slots_disponibles'] }} slots</p>
            </div>
        </article>
        <article>
            <span><i class="far fa-calendar-check" aria-hidden="true"></i></span>
            <div>
                <small>Reservas del mes</small>
                <strong>{{ $kpis['reservas_mes']['total'] }}</strong>
                <p>{{ $kpis['reservas_mes']['variacion_porcentaje'] >= 0 ? '+' : '' }}{{ number_format($kpis['reservas_mes']['variacion_porcentaje'], 1, ',', '.') }}% vs mes anterior</p>
            </div>
        </article>
        <article>
            <span><i class="fas fa-desktop" aria-hidden="true"></i></span>
            <div>
                <small>Equipos hoy</small>
                <strong>{{ $kpis['equipos_hoy']['disponibles'] }} / {{ $kpis['equipos_hoy']['en_uso'] }}</strong>
                <p>Disponibles / En uso</p>
            </div>
        </article>
        <article>
            <span><i class="fas fa-ban" aria-hidden="true"></i></span>
            <div>
                <small>Tasa de cancelación</small>
                <strong>{{ number_format($kpis['cancelaciones']['tasa_cancelacion'], 2, ',', '.') }}%</strong>
                <p>No show: {{ number_format($kpis['cancelaciones']['tasa_no_show'], 2, ',', '.') }}%</p>
            </div>
        </article>
    </section>

    <section class="src-report-table-card">
        <header>
            <div>
                <span>Detalle de ocupación</span>
                <h2>{{ $detalle->total() }} registros encontrados</h2>
            </div>
            <div class="src-report-actions">
                <a class="src-report-excel" href="{{ route('reportes.ocupacion.excel', request()->only(['desde', 'hasta', 'aula_id', 'estado'])) }}">
                    <i class="far fa-file-excel" aria-hidden="true"></i> Exportar Excel
                </a>
                <a class="src-report-pdf" href="{{ route('reportes.ocupacion.pdf', request()->only(['desde', 'hasta', 'aula_id', 'estado'])) }}">
                    <i class="far fa-file-pdf" aria-hidden="true"></i> Exportar PDF
                </a>
            </div>
        </header>

        <div class="table-responsive">
            <table class="src-report-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Bloque horario</th>
                        <th>Aula</th>
                        <th>Slots reservados</th>
                        <th>Slots disponibles</th>
                        <th>Ocupación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detalle as $fila)
                        @php
                            $porcentaje = $fila['slots_disponibles'] > 0
                                ? round(($fila['slots_reservados'] / $fila['slots_disponibles']) * 100, 2)
                                : 0;
                        @endphp
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($fila['fecha'])->translatedFormat('d/m/Y') }}</td>
                            <td><strong>{{ $fila['bloque'] }}</strong></td>
                            <td>{{ $fila['aula'] }}</td>
                            <td>{{ $fila['slots_reservados'] }}</td>
                            <td>{{ $fila['slots_disponibles'] }}</td>
                            <td>
                                <span class="src-report-percent">{{ number_format($porcentaje, 2, ',', '.') }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="src-report-empty">
                                <i class="fas fa-chart-bar" aria-hidden="true"></i>
                                <strong>Sin datos</strong>
                                <span>No hay detalle de ocupación para los filtros seleccionados.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($detalle->total() > 0)
            <x-src-pagination :items="$detalle" :sizes="[15]" />
        @endif
    </section>
@stop
