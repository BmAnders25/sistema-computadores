@extends('adminlte::page')

@section('title', 'Panel de control')

@section('content_header')
    <x-src-page-hero
        eyebrow="Administración"
        title="Panel de control"
        description="Estadísticas operativas de reservas, disponibilidad y uso de equipos."
        icon="fas fa-chart-line" />
@stop

@section('content')
    @php
        $desdeInicial = now()->subDays(29)->toDateString();
        $hastaInicial = now()->toDateString();
    @endphp

    <section class="src-dashboard-filters" aria-label="Filtros del dashboard">
        <div class="src-dashboard-presets" role="group" aria-label="Rango rapido">
            <button type="button" class="src-dashboard-preset is-active" data-dashboard-preset="30">30 días</button>
            <button type="button" class="src-dashboard-preset" data-dashboard-preset="today">Hoy</button>
            <button type="button" class="src-dashboard-preset" data-dashboard-preset="7">7 días</button>
            <button type="button" class="src-dashboard-preset" data-dashboard-preset="month">Este mes</button>
        </div>

        <div class="src-dashboard-filter-fields">
            <label>
                <span>Desde</span>
                <input type="date" id="dashboardDesde" value="{{ $desdeInicial }}">
            </label>
            <label>
                <span>Hasta</span>
                <input type="date" id="dashboardHasta" value="{{ $hastaInicial }}">
            </label>
            <label>
                <span>Aula / Laboratorio</span>
                <select id="dashboardAula">
                    <option value="">Todas las aulas</option>
                    @foreach ($aulas as $aula)
                        <option value="{{ $aula->id }}">{{ $aula->bloque }} · {{ $aula->nombre }}</option>
                    @endforeach
                </select>
            </label>
            <button type="button" id="dashboardAplicar" class="src-dashboard-apply">
                <i class="fas fa-filter" aria-hidden="true"></i> Aplicar
            </button>
        </div>
    </section>

    <div id="dashboardEstado" class="src-dashboard-status" role="status" aria-live="polite">
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando estadísticas...
    </div>

    <section class="src-dashboard-kpis" aria-label="Indicadores principales">
        <article class="src-dashboard-kpi">
            <span class="src-dashboard-kpi-icon"><i class="fas fa-chart-pie" aria-hidden="true"></i></span>
            <div>
                <small>% ocupación</small>
                <strong id="kpiOcupacion">--</strong>
                <span id="kpiOcupacionDetalle">Calculando slots...</span>
            </div>
        </article>
        <article class="src-dashboard-kpi">
            <span class="src-dashboard-kpi-icon"><i class="far fa-calendar-check" aria-hidden="true"></i></span>
            <div>
                <small>Reservas del mes</small>
                <strong id="kpiReservasMes">--</strong>
                <span id="kpiReservasVariacion">Comparando con mes anterior...</span>
            </div>
        </article>
        <article class="src-dashboard-kpi">
            <span class="src-dashboard-kpi-icon"><i class="fas fa-desktop" aria-hidden="true"></i></span>
            <div>
                <small>Equipos disponibles vs en uso</small>
                <strong id="kpiEquipos">--</strong>
                <span id="kpiEquiposDetalle">Disponible / En uso</span>
            </div>
        </article>
        <article class="src-dashboard-kpi">
            <span class="src-dashboard-kpi-icon"><i class="fas fa-ban" aria-hidden="true"></i></span>
            <div>
                <small>Tasa de cancelación</small>
                <strong id="kpiCancelacion">--</strong>
                <span id="kpiNoShow">No show: --</span>
            </div>
        </article>
    </section>

    <section class="src-dashboard-charts" aria-label="Gráficos estadísticos">
        <article class="src-dashboard-chart-card src-dashboard-chart-wide">
            <header>
                <div>
                    <span>Tendencia</span>
                    <h2>Reservas por periodo</h2>
                </div>
            </header>
            <div class="src-dashboard-chart-body">
                <canvas id="dashboardTrendChart" aria-label="Gráfico de tendencia de reservas"></canvas>
                <div class="src-dashboard-chart-empty" data-chart-empty="dashboardTrendChart" hidden>
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <strong>Sin datos</strong>
                    <span>No hay reservas para el rango seleccionado.</span>
                </div>
            </div>
        </article>
        <article class="src-dashboard-chart-card">
            <header>
                <div>
                    <span>Uso</span>
                    <h2>Reservas por aula</h2>
                </div>
            </header>
            <div class="src-dashboard-chart-body">
                <canvas id="dashboardAulasChart" aria-label="Gráfico de reservas por aula"></canvas>
                <div class="src-dashboard-chart-empty" data-chart-empty="dashboardAulasChart" hidden>
                    <i class="fas fa-building" aria-hidden="true"></i>
                    <strong>Sin datos</strong>
                    <span>No hay uso de aulas para el rango seleccionado.</span>
                </div>
            </div>
        </article>
        <article class="src-dashboard-chart-card">
            <header>
                <div>
                    <span>Ocupación</span>
                    <h2>Reservas por franja horaria</h2>
                </div>
            </header>
            <div class="src-dashboard-chart-body">
                <canvas id="dashboardBloquesChart" aria-label="Gráfico de ocupación por franja horaria"></canvas>
                <div class="src-dashboard-chart-empty" data-chart-empty="dashboardBloquesChart" hidden>
                    <i class="far fa-clock" aria-hidden="true"></i>
                    <strong>Sin datos</strong>
                    <span>No hay ocupación por franja horaria en este rango.</span>
                </div>
            </div>
        </article>
    </section>

    <section class="src-dashboard-shortcuts" aria-label="Accesos a módulos">
        @can('ver-disponibilidad')
            <a class="src-quick-link" href="{{ route('disponibilidad.index') }}">
                <span class="src-quick-link-icon"><i class="fas fa-calendar-alt"></i></span>
                <span><strong>Disponibilidad</strong><small>Consulta equipos disponibles para reservar.</small></span>
                <i class="fas fa-arrow-right src-quick-link-arrow" aria-hidden="true"></i>
            </a>
        @endcan
        @can('ver-reservas')
            <a class="src-quick-link" href="{{ route('reservas.index') }}">
                <span class="src-quick-link-icon"><i class="fas fa-calendar-check"></i></span>
                <span><strong>Mis reservas</strong><small>Revisa el historial y estado de las reservas.</small></span>
                <i class="fas fa-arrow-right src-quick-link-arrow" aria-hidden="true"></i>
            </a>
        @endcan
        @can('ver-computadores')
            <a class="src-quick-link" href="{{ route('computadores.index') }}">
                <span class="src-quick-link-icon"><i class="fas fa-desktop"></i></span>
                <span><strong>Computadores</strong><small>Administra el inventario de equipos.</small></span>
                <i class="fas fa-arrow-right src-quick-link-arrow" aria-hidden="true"></i>
            </a>
        @endcan
        @can('ver-aulas')
            <a class="src-quick-link" href="{{ route('aulas.index') }}">
                <span class="src-quick-link-icon"><i class="fas fa-building"></i></span>
                <span><strong>Aulas y laboratorios</strong><small>Consulta los espacios y equipos asociados.</small></span>
                <i class="fas fa-arrow-right src-quick-link-arrow" aria-hidden="true"></i>
            </a>
        @endcan
        @can('ver-bloques-horarios')
            <a class="src-quick-link" href="{{ route('bloques-horarios.index') }}">
                <span class="src-quick-link-icon"><i class="far fa-clock"></i></span>
                <span><strong>Bloques horarios</strong><small>Consulta las franjas disponibles para reservas.</small></span>
                <i class="fas fa-arrow-right src-quick-link-arrow" aria-hidden="true"></i>
            </a>
        @endcan
        @can('ver-usuarios')
            <a class="src-quick-link" href="{{ route('users.index') }}">
                <span class="src-quick-link-icon"><i class="fas fa-users"></i></span>
                <span><strong>Usuarios</strong><small>Administra cuentas y roles de acceso.</small></span>
                <i class="fas fa-arrow-right src-quick-link-arrow" aria-hidden="true"></i>
            </a>
        @endcan
    </section>
@stop

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const desdeInput = document.getElementById('dashboardDesde');
            const hastaInput = document.getElementById('dashboardHasta');
            const aulaInput = document.getElementById('dashboardAula');
            const aplicar = document.getElementById('dashboardAplicar');
            const estado = document.getElementById('dashboardEstado');
            const endpoint = @json(route('dashboard.datos'));
            let trendChart;
            let aulasChart;
            let bloquesChart;

            function setPreset(type) {
                const hoy = new Date();
                let desde = new Date(hoy);
                let hasta = new Date(hoy);

                if (type === 'today') {
                    desde = new Date(hoy);
                } else if (type === '7') {
                    desde.setDate(hoy.getDate() - 6);
                } else if (type === '30') {
                    desde.setDate(hoy.getDate() - 29);
                } else if (type === 'month') {
                    desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                }

                desdeInput.value = desde.toISOString().slice(0, 10);
                hastaInput.value = hasta.toISOString().slice(0, 10);
            }

            document.querySelectorAll('[data-dashboard-preset]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.querySelectorAll('[data-dashboard-preset]').forEach(function (item) {
                        item.classList.toggle('is-active', item === button);
                    });
                    setPreset(button.dataset.dashboardPreset);
                    cargar();
                });
            });

            aplicar.addEventListener('click', cargar);
            aulaInput.addEventListener('change', cargar);

            function destroyChart(chart) {
                if (chart) chart.destroy();
            }

            function chartEmpty(canvasId) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                canvas.style.display = 'none';
                const empty = document.querySelector('[data-chart-empty="' + canvasId + '"]');
                if (empty) empty.hidden = false;
            }

            function chartShow(canvasId) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                canvas.style.display = 'block';
                const empty = document.querySelector('[data-chart-empty="' + canvasId + '"]');
                if (empty) empty.hidden = true;
            }

            async function cargar() {
                estado.hidden = false;
                estado.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando estadísticas...';

                const params = new URLSearchParams({
                    desde: desdeInput.value,
                    hasta: hastaInput.value
                });
                if (aulaInput.value) params.set('aula_id', aulaInput.value);

                try {
                    const response = await fetch(endpoint + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(payload.message || 'No fue posible cargar las estadísticas.');
                    }

                    render(payload);
                } catch (error) {
                    estado.hidden = false;
                    estado.innerHTML = '<i class="fas fa-exclamation-circle" aria-hidden="true"></i> ' +
                        (error.message || 'No fue posible cargar las estadísticas.');
                }
            }

            function hasValues(rows) {
                return Array.isArray(rows) && rows.some(function (row) {
                    return Number(row.total || 0) > 0;
                });
            }

            function render(payload) {
                const kpis = payload.kpis;
                const sinDatos =
                    !payload.tendencia.some(function (row) { return Number(row.total || 0) > 0; }) &&
                    !payload.uso_por_aula.some(function (row) { return Number(row.total || 0) > 0; }) &&
                    !payload.ocupacion_por_bloque.some(function (row) { return Number(row.total || 0) > 0; });
                if (sinDatos) {
                    estado.hidden = false;
                    estado.innerHTML = '<i class="fas fa-info-circle" aria-hidden="true"></i> Sin datos para el rango y filtros seleccionados.';
                } else {
                    estado.hidden = true;
                }
                document.getElementById('kpiOcupacion').textContent =
                    kpis.ocupacion.toFixed(2).replace('.00', '') + '%';
                document.getElementById('kpiOcupacionDetalle').textContent =
                    kpis.slots_reservados + ' de ' + kpis.slots_disponibles + ' slots';

                document.getElementById('kpiReservasMes').textContent = kpis.reservas_mes.total;
                const variacion = kpis.reservas_mes.variacion_porcentaje;
                document.getElementById('kpiReservasVariacion').textContent =
                    (variacion >= 0 ? '+' : '') + variacion.toFixed(1).replace('.0', '') +
                    '% vs mes anterior';

                document.getElementById('kpiEquipos').textContent =
                    kpis.equipos_hoy.disponibles + ' / ' + kpis.equipos_hoy.en_uso;
                document.getElementById('kpiEquiposDetalle').textContent =
                    kpis.equipos_hoy.mantenimiento + ' mantenimiento · ' +
                    kpis.equipos_hoy.inactivos + ' inactivos';

                document.getElementById('kpiCancelacion').textContent =
                    kpis.cancelaciones.tasa_cancelacion.toFixed(1).replace('.0', '') + '%';
                document.getElementById('kpiNoShow').textContent =
                    'No show: ' + kpis.cancelaciones.tasa_no_show.toFixed(1).replace('.0', '') + '%';

                destroyChart(trendChart);
                destroyChart(aulasChart);
                destroyChart(bloquesChart);

                const trendValues = payload.tendencia.map(function (row) {
                    return Number(row.total || 0);
                });
                const trendLabels = payload.tendencia.map(function (row) {
                    return row.fecha || ((row.fecha_inicio || '').slice(5) + ' / ' + (row.fecha_fin || '').slice(5));
                });
                chartShow('dashboardTrendChart');
                if (!trendValues.some(function (value) { return value > 0; })) {
                    chartEmpty('dashboardTrendChart');
                } else {
                    trendChart = new Chart(document.getElementById('dashboardTrendChart'), {
                        type: 'line',
                        data: {
                            labels: trendLabels,
                            datasets: [{
                                label: 'Reservas',
                                data: trendValues,
                                borderColor: '#A6262E',
                                backgroundColor: 'rgba(166, 38, 46, 0.10)',
                                fill: true,
                                tension: 0.32,
                                pointBackgroundColor: '#FFFFFF',
                                pointBorderColor: '#7B1E24',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EDF1F5' } }
                            }
                        }
                    });
                }

                chartShow('dashboardAulasChart');
                if (!hasValues(payload.uso_por_aula)) {
                    chartEmpty('dashboardAulasChart');
                } else {
                    aulasChart = new Chart(document.getElementById('dashboardAulasChart'), {
                        type: 'bar',
                        data: {
                            labels: payload.uso_por_aula.map(function (row) { return row.aula; }),
                            datasets: [{
                                label: 'Reservas',
                                data: payload.uso_por_aula.map(function (row) { return row.total; }),
                                backgroundColor: '#7B1E24',
                                borderRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EDF1F5' } },
                                y: { grid: { display: false } }
                            }
                        }
                    });
                }

                chartShow('dashboardBloquesChart');
                if (!hasValues(payload.ocupacion_por_bloque)) {
                    chartEmpty('dashboardBloquesChart');
                } else {
                    bloquesChart = new Chart(document.getElementById('dashboardBloquesChart'), {
                        type: 'bar',
                        data: {
                            labels: payload.ocupacion_por_bloque.map(function (row) { return row.bloque; }),
                            datasets: [{
                                label: 'Slots reservados',
                                data: payload.ocupacion_por_bloque.map(function (row) { return row.total; }),
                                backgroundColor: '#A6262E',
                                borderRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EDF1F5' } },
                                y: { grid: { display: false } }
                            }
                        }
                    });
                }
            }

            cargar();
        })();
    </script>
@endpush
