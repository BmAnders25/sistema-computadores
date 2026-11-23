@extends('adminlte::page')

@section('title', 'Computadores')

@section('content_header')
    <x-src-page-hero
        eyebrow="Inventario"
        title="Administración de computadores"
        description="Consulta y administra los equipos, sus especificaciones y su ubicación institucional."
        icon="fas fa-desktop" />
@stop

@section('content')
    @php
        $selectedEstado = request()->query('estado', '');
    @endphp

    <div class="src-computers-page">
        @if ($message = Session::get('success'))
            <div class="alert alert-success" role="status">{{ $message }}</div>
        @endif
        @if ($message = Session::get('error'))
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @endif

        <div class="src-computers-create-row">
            @can('crear-computadores')
                <a class="src-computers-create-button" href="{{ route('computadores.create') }}">
                    <i class="fas fa-plus" aria-hidden="true"></i> Nuevo Computador
                </a>
            @endcan
        </div>

        <section class="src-computers-kpis" aria-label="Resumen del inventario">
            <article class="src-computers-kpi">
                <span>Total de equipos</span>
                <strong>{{ $totalEquipos }}</strong>
                <small>Equipos registrados</small>
            </article>
            <article class="src-computers-kpi src-computers-kpi-available">
                <span>Disponibles</span>
                <strong>{{ $disponibles }}</strong>
                <small><i aria-hidden="true"></i> Listos para reserva</small>
            </article>
            <article class="src-computers-kpi src-computers-kpi-in-use">
                <span>En uso / Prestados</span>
                <strong>{{ $enUso }}</strong>
                <small><i aria-hidden="true"></i> Actualmente en aula</small>
            </article>
            <article class="src-computers-kpi src-computers-kpi-maintenance">
                <span>En mantenimiento / Dañados</span>
                <strong>{{ $mantenimiento }}</strong>
                <small><i aria-hidden="true"></i> No disponibles</small>
            </article>
        </section>

        <form class="src-computers-toolbar" aria-label="Filtros y controles del inventario" action="{{ url()->current() }}" method="GET" data-computer-server-form>
            @foreach (request()->except(['estado', 'search', 'aula_id', 'page']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $nestedKey => $nestedValue)
                        <input type="hidden" name="{{ $key }}[{{ $nestedKey }}]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="estado" value="{{ $selectedEstado }}" data-computer-state>
            <div class="src-computers-tabs" role="group" aria-label="Filtrar por estado">
                <button type="button" class="src-computers-tab {{ $selectedEstado === '' ? 'is-active' : '' }}" data-computer-filter="all" data-computer-query-value="" aria-pressed="{{ $selectedEstado === '' ? 'true' : 'false' }}">
                    Todos <span>{{ $totalEquipos }}</span>
                </button>
                <button type="button" class="src-computers-tab {{ $selectedEstado === 'disponible' ? 'is-active' : '' }}" data-computer-filter="available" data-computer-query-value="disponible" aria-pressed="{{ $selectedEstado === 'disponible' ? 'true' : 'false' }}">
                    Disponibles <span>{{ $disponibles }}</span>
                </button>
                <button type="button" class="src-computers-tab {{ $selectedEstado === 'en_uso' ? 'is-active' : '' }}" data-computer-filter="in-use" data-computer-query-value="en_uso" aria-pressed="{{ $selectedEstado === 'en_uso' ? 'true' : 'false' }}">
                    En uso <span>{{ $enUso }}</span>
                </button>
                <button type="button" class="src-computers-tab {{ $selectedEstado === 'mantenimiento' ? 'is-active' : '' }}" data-computer-filter="maintenance" data-computer-query-value="mantenimiento" aria-pressed="{{ $selectedEstado === 'mantenimiento' ? 'true' : 'false' }}">
                    Mantenimiento <span>{{ $mantenimiento }}</span>
                </button>
            </div>

            <div class="src-computers-controls">
                <label class="src-computers-search" for="computerSearch">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span class="sr-only">Buscar por marca, modelo, serial o laboratorio</span>
                    <input type="search" id="computerSearch" name="search" value="{{ request()->query('search', '') }}" placeholder="Buscar por marca, serial, aula..." autocomplete="off">
                </label>

                <label class="src-computers-location">
                    <span class="sr-only">Filtrar por ubicación</span>
                    <select id="computerLocation" name="aula_id">
                        <option value="">Todos los laboratorios</option>
                        @foreach ($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id }}" @selected((string) request()->query('aula_id') === (string) $ubicacion->id)>{{ $ubicacion->bloque }} · {{ $ubicacion->nombre }}</option>
                        @endforeach
                    </select>
                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                </label>

                <div class="src-computers-export">
                    <button type="button" class="src-computers-export-toggle" id="computerExportToggle" aria-expanded="false" aria-controls="computerExportMenu">
                        <i class="fas fa-download" aria-hidden="true"></i> Exportar
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="src-computers-export-menu" id="computerExportMenu" role="menu" hidden>
                        <button type="button" role="menuitem" data-computer-export="excel">
                            <i class="far fa-file-excel" aria-hidden="true"></i> Excel
                        </button>
                        <button type="button" role="menuitem" data-computer-export="pdf">
                            <i class="far fa-file-pdf" aria-hidden="true"></i> PDF
                        </button>
                        <button type="button" role="menuitem" data-computer-export="csv">
                            <i class="fas fa-file-csv" aria-hidden="true"></i> CSV
                        </button>
                        <button type="button" role="menuitem" data-computer-export="print">
                            <i class="fas fa-print" aria-hidden="true"></i> Imprimir
                        </button>
                    </div>
                </div>

                <div class="src-computers-view-toggle" role="group" aria-label="Tipo de vista">
                    <button type="button" class="is-active" data-computer-view="grid" aria-label="Vista en tarjetas" aria-pressed="true">
                        <i class="fas fa-th-large" aria-hidden="true"></i>
                    </button>
                    <button type="button" data-computer-view="list" aria-label="Vista en lista" aria-pressed="false">
                        <i class="fas fa-list" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </form>

        <section class="src-computer-grid" id="computerGrid" aria-label="Equipos registrados">
            @forelse ($computadores as $computador)
                @php
                    $nombreEquipo = trim(($computador->marca ?? 'Computador') . ' ' . ($computador->modelo ?? ''));
                    $nombreAula = $computador->aula->nombre ?? $computador->laboratorio_aula ?? 'Sin laboratorio';
                    $bloqueAula = $computador->aula->bloque ?? $computador->bloque ?? 'Sin bloque';
                    $ubicacionLabel = $bloqueAula . ' · ' . $nombreAula;
                    $tipoEquipo = ucfirst(str_replace('_', ' ', $computador->tipo_equipo ?? 'equipo'));
                    $estadoFiltro = match ($computador->estado) {
                        'disponible' => 'available',
                        'prestado', 'reservado' => 'in-use',
                        'mantenimiento' => 'maintenance',
                        default => 'other',
                    };
                    if ($computador->condicion === 'defectuoso') {
                        $estadoFiltro = 'maintenance';
                    }
                    $estadoLabel = match ($computador->estado) {
                        'disponible' => 'Disponible',
                        'prestado' => 'En uso',
                        'reservado' => 'Reservado',
                        'mantenimiento' => 'Mantenimiento',
                        default => 'Inactivo',
                    };
                    $estadoClass = match ($computador->estado) {
                        'disponible' => 'available',
                        'prestado', 'reservado' => 'in-use',
                        'mantenimiento' => 'maintenance',
                        default => 'inactive',
                    };
                    $condicionLabel = ucfirst($computador->condicion ?? 'Sin dato');
                    $textoBusqueda = \Illuminate\Support\Str::lower(implode(' ', [
                        $computador->marca,
                        $computador->modelo,
                        $computador->serial,
                        $nombreAula,
                        $bloqueAula,
                        $computador->procesador,
                        $computador->sistema_operativo,
                    ]));
                @endphp

                <article
                    class="src-computer-card"
                    data-computer-status="{{ $estadoFiltro }}"
                    data-computer-location="{{ $computador->aula_id ?? '' }}"
                    data-computer-search="{{ $textoBusqueda }}"
                    data-export-brand="{{ $computador->marca }}"
                    data-export-model="{{ $computador->modelo ?? '' }}"
                    data-export-category="{{ $tipoEquipo }}"
                    data-export-status="{{ $estadoLabel }}"
                    data-export-serial="{{ $computador->serial }}"
                    data-export-uuid="{{ $computador->codigo }}"
                    data-export-location="{{ $ubicacionLabel }}"
                    data-export-processor="{{ $computador->procesador ?? '' }}"
                    data-export-ram="{{ $computador->ram_gb ? $computador->ram_gb . ' GB' : '' }}"
                    data-export-storage="{{ $computador->almacenamiento ?? '' }}"
                    data-export-os="{{ $computador->sistema_operativo ?? '' }}"
                    data-export-condition="{{ $condicionLabel }}"
                >
                    <header class="src-computer-card-header">
                        <div>
                            <h2>{{ $nombreEquipo }}</h2>
                            <span class="src-computer-category">{{ $tipoEquipo }}</span>
                        </div>
                        <span class="src-computer-status src-computer-status-{{ $estadoClass }}">
                            <span aria-hidden="true"></span>{{ $estadoLabel }}
                        </span>
                    </header>

                    <div class="src-computer-identification">
                        <div class="src-computer-id-row">
                            <span class="src-computer-serial">Serial: {{ $computador->serial }}</span>
                            <button
                                type="button"
                                class="src-computer-copy-button"
                                data-copy-uuid="{{ $computador->codigo }}"
                                title="Copiar UUID"
                                aria-label="Copiar UUID del computador {{ $computador->serial }}"
                            >
                                <i class="far fa-copy" aria-hidden="true"></i> Copiar UUID
                            </button>
                        </div>
                        <span class="src-computer-location">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            {{ $ubicacionLabel }}
                        </span>
                    </div>

                    <div class="src-computer-specs">
                        <span><i class="fas fa-microchip" aria-hidden="true"></i>{{ $computador->procesador ?? 'Procesador no especificado' }}</span>
                        <span><i class="fas fa-memory" aria-hidden="true"></i>{{ $computador->ram_gb ? $computador->ram_gb . ' GB RAM' : 'RAM no especificada' }}</span>
                        <span><i class="fas fa-hdd" aria-hidden="true"></i>{{ $computador->almacenamiento ?? 'Almacenamiento no especificado' }}</span>
                        <span><i class="fab fa-windows" aria-hidden="true"></i>{{ $computador->sistema_operativo ?? 'Sistema operativo no especificado' }}</span>
                        <span class="src-computer-condition src-computer-condition-{{ $computador->condicion ?? 'unknown' }}">
                            Condición: {{ $condicionLabel }}
                        </span>
                    </div>

                    <footer class="src-computer-card-actions">
                        <a href="{{ route('computadores.show', $computador->id) }}">
                            <i class="far fa-eye" aria-hidden="true"></i> Ver
                        </a>
                        @can('editar-computadores')
                            <a class="src-computer-edit-action" href="{{ route('computadores.edit', $computador->id) }}">
                                <i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar
                            </a>
                        @endcan
                        @can('borrar-computadores')
                            <form method="POST" action="{{ route('computadores.destroy', $computador->id) }}" class="src-computer-delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="src-computer-delete-action" data-delete-computer>
                                    <i class="far fa-trash-alt" aria-hidden="true"></i> Eliminar
                                </button>
                            </form>
                        @endcan
                    </footer>
                </article>
            @empty
                <div class="src-computer-empty">
                    <i class="fas fa-desktop" aria-hidden="true"></i>
                    <strong>Aún no hay computadores registrados</strong>
                    <span>Los equipos agregados al inventario aparecerán aquí.</span>
                    @can('crear-computadores')
                        <a href="{{ route('computadores.create') }}">Registrar un computador</a>
                    @endcan
                </div>
            @endforelse

            @if ($computadores->isNotEmpty())
                <div class="src-computer-empty src-computer-filter-empty" id="computerFilterEmpty" hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos computadores</strong>
                    <span>Ajusta los filtros o la búsqueda e inténtalo de nuevo.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$computadores" />
    </div>
@stop

@section('js')
    <script>
        (function () {
            var grid = document.getElementById('computerGrid');
            var search = document.getElementById('computerSearch');
            var location = document.getElementById('computerLocation');
            var serverForm = document.querySelector('[data-computer-server-form]');
            var stateInput = document.querySelector('[data-computer-state]');
            var tabs = document.querySelectorAll('[data-computer-filter]');
            var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.src-computer-card')) : [];

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    stateInput.value = tab.dataset.computerQueryValue || '';
                    tabs.forEach(function (item) {
                        var selected = item === tab;
                        item.classList.toggle('is-active', selected);
                        item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    });
                    serverForm.submit();
                });
            });

            var searchTimeout;
            search.addEventListener('input', function () {
                window.clearTimeout(searchTimeout);
                searchTimeout = window.setTimeout(function () {
                    serverForm.submit();
                }, 350);
            });
            location.addEventListener('change', function () {
                serverForm.submit();
            });

            document.querySelectorAll('[data-computer-view]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var selectedView = button.dataset.computerView;
                    grid.classList.toggle('is-list', selectedView === 'list');
                    document.querySelectorAll('[data-computer-view]').forEach(function (item) {
                        var selected = item === button;
                        item.classList.toggle('is-active', selected);
                        item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    });
                });
            });

            var exportToggle = document.getElementById('computerExportToggle');
            var exportMenu = document.getElementById('computerExportMenu');

            function closeExportMenu() {
                exportMenu.hidden = true;
                exportToggle.setAttribute('aria-expanded', 'false');
            }

            exportToggle.addEventListener('click', function () {
                exportMenu.hidden = !exportMenu.hidden;
                exportToggle.setAttribute('aria-expanded', exportMenu.hidden ? 'false' : 'true');
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.src-computers-export')) closeExportMenu();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeExportMenu();
            });

            var exportHeaders = ['Marca', 'Modelo', 'Categoría', 'Estado', 'Serial', 'UUID', 'Ubicación', 'Procesador', 'RAM', 'Almacenamiento', 'Sistema operativo', 'Condición'];
            var exportKeys = ['brand', 'model', 'category', 'status', 'serial', 'uuid', 'location', 'processor', 'ram', 'storage', 'os', 'condition'];

            function getVisibleRows() {
                return cards.filter(function (card) {
                    return !card.hidden;
                }).map(function (card) {
                    return exportKeys.map(function (key) {
                        return card.dataset['export' + key.charAt(0).toUpperCase() + key.slice(1)] || '';
                    });
                });
            }

            function downloadFile(content, type, filename) {
                var blob = new Blob([content], { type: type });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(function () {
                    URL.revokeObjectURL(url);
                }, 1000);
            }

            function escapeHtml(value) {
                var element = document.createElement('span');
                element.textContent = value;
                return element.innerHTML;
            }

            function spreadsheetValue(value) {
                var text = String(value || '');
                return /^[\t\r\n ]*[=+\-@]/.test(text) ? "'" + text : text;
            }

            function buildExportTable(rows, forSpreadsheet) {
                var headers = '<tr>' + exportHeaders.map(function (header) {
                    return '<th>' + escapeHtml(header) + '</th>';
                }).join('') + '</tr>';
                var body = rows.map(function (row) {
                    return '<tr>' + row.map(function (value) {
                        return '<td>' + escapeHtml(forSpreadsheet ? spreadsheetValue(value) : value) + '</td>';
                    }).join('') + '</tr>';
                }).join('');

                return '<table><thead>' + headers + '</thead><tbody>' + body + '</tbody></table>';
            }

            function openPrintWindow(rows) {
                var printWindow = window.open('', '_blank');
                if (!printWindow) {
                    Swal.fire({
                        title: 'Ventana bloqueada',
                        text: 'Permite las ventanas emergentes para imprimir o guardar el inventario como PDF.',
                        icon: 'warning',
                        confirmButtonColor: '#a6262e'
                    });
                    return;
                }

                printWindow.document.open();
                printWindow.onload = function () {
                    printWindow.focus();
                    printWindow.print();
                };
                printWindow.document.write(
                    '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Inventario de computadores</title>' +
                    '<style>body{font:13px Arial,sans-serif;color:#202b3b;padding:24px}h1{font-size:20px;margin:0 0 16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d5dce5;padding:7px;text-align:left}th{background:#7b1e24;color:#fff}tr:nth-child(even){background:#f5f7f9}@media print{body{padding:0}}</style>' +
                    '</head><body><h1>Inventario de computadores · S.R.C</h1>' + buildExportTable(rows, false) + '</body></html>'
                );
                printWindow.document.close();
            }

            document.querySelectorAll('[data-computer-export]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var rows = getVisibleRows();
                    closeExportMenu();
                    if (!rows.length) {
                        Swal.fire({
                            title: 'Sin equipos para exportar',
                            text: 'No hay computadores que coincidan con los filtros actuales.',
                            icon: 'info',
                            confirmButtonColor: '#a6262e'
                        });
                        return;
                    }

                    var format = button.dataset.computerExport;
                    if (format === 'csv') {
                        var csv = [exportHeaders].concat(rows).map(function (row) {
                            return row.map(function (value) {
                                return '"' + spreadsheetValue(value).replace(/"/g, '""') + '"';
                            }).join(',');
                        }).join('\r\n');
                        downloadFile('\uFEFF' + csv, 'text/csv;charset=utf-8', 'inventario-computadores.csv');
                    } else if (format === 'excel') {
                        downloadFile(
                            '<!doctype html><html><head><meta charset="utf-8"></head><body>' + buildExportTable(rows, true) + '</body></html>',
                            'application/vnd.ms-excel;charset=utf-8',
                            'inventario-computadores.xls'
                        );
                    } else {
                        openPrintWindow(rows);
                    }
                });
            });

            function showCopied(button) {
                var original = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Copiado';
                button.classList.add('is-copied');
                window.setTimeout(function () {
                    button.innerHTML = original;
                    button.classList.remove('is-copied');
                }, 1500);
            }

            function showCopyError() {
                Swal.fire({
                    title: 'No se pudo copiar',
                    text: 'Verifica los permisos del portapapeles e inténtalo de nuevo.',
                    icon: 'error',
                    confirmButtonColor: '#a6262e'
                });
            }

            document.querySelectorAll('[data-copy-uuid]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var uuid = button.dataset.copyUuid;
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(uuid).then(function () {
                            showCopied(button);
                        }).catch(showCopyError);
                        return;
                    }

                    var temporaryInput = document.createElement('textarea');
                    temporaryInput.value = uuid;
                    temporaryInput.setAttribute('readonly', '');
                    temporaryInput.style.position = 'absolute';
                    temporaryInput.style.left = '-9999px';
                    document.body.appendChild(temporaryInput);
                    temporaryInput.select();
                    var copied = document.execCommand('copy');
                    temporaryInput.remove();
                    if (copied) showCopied(button);
                    else showCopyError();
                });
            });

            document.querySelectorAll('[data-delete-computer]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var form = button.closest('form');
                    Swal.fire({
                        title: '¿Inactivar equipo de cómputo?',
                        text: 'El equipo cambiará su estado a inactivo y no estará disponible para préstamos.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#a6262e',
                        cancelButtonColor: '#778087',
                        confirmButtonText: 'Sí, inactivar',
                        cancelButtonText: 'Cancelar'
                    }).then(function (result) {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });

            @if ($message = Session::get('success'))
                Swal.fire({
                    title: '¡Operación exitosa!',
                    text: @json($message),
                    timer: 2500,
                    icon: 'success',
                    confirmButtonColor: '#a6262e'
                });
            @endif

            @if ($message = Session::get('error'))
                Swal.fire({
                    title: 'Acción no permitida',
                    text: @json($message),
                    icon: 'error',
                    confirmButtonColor: '#a6262e'
                });
            @endif
        })();
    </script>
@stop
