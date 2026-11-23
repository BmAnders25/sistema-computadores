@extends('adminlte::page')

@section('title', 'Detalles del Computador')

@section('content_header')
    @php
        $nombreEquipo = trim(($computador->marca ?? 'Computador') . ' ' . ($computador->modelo ?? ''));
        $bloqueAula = $computador->aula->bloque ?? $computador->bloque ?? 'Sin bloque';
        $nombreAula = $computador->aula->nombre ?? $computador->laboratorio_aula ?? 'Sin laboratorio';
        $tipoEquipo = ucfirst(str_replace('_', ' ', $computador->tipo_equipo ?? 'equipo'));
        $estadoLabel = match ($computador->estado) {
            'disponible' => 'Disponible',
            'prestado' => 'En uso / Prestado',
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
    @endphp

    <section class="src-computer-detail-heading">
        <nav class="src-computer-breadcrumb" aria-label="Migas de pan">
            <a href="{{ route('home') }}">S.R.C</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('computadores.index') }}">Inventario</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $computador->serial }}</span>
        </nav>
        <div class="src-computer-detail-title-row">
            <div class="src-computer-detail-title">
                <h1>{{ $nombreEquipo }}</h1>
                <span>Serial: {{ $computador->serial }}</span>
            </div>
            <div class="src-computer-detail-actions">
                @can('editar-computadores')
                    <a class="src-computer-primary-button" href="{{ route('computadores.edit', $computador->id) }}">
                        <i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar equipo
                    </a>
                @endcan
                <a class="src-computer-secondary-button" href="{{ route('computadores.index') }}">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver al inventario
                </a>
            </div>
        </div>
    </section>
@stop

@section('content')
    <div class="src-computer-detail-layout">
        <aside class="src-computer-summary-card">
            <div class="src-computer-summary-icon" aria-hidden="true">
                <i class="fas fa-desktop"></i>
            </div>
            <h2>{{ $nombreEquipo }}</h2>
            <span class="src-computer-summary-category">{{ $tipoEquipo }}</span>

            <div class="src-computer-summary-badges">
                <span class="src-computer-status src-computer-status-{{ $estadoClass }}">
                    <span aria-hidden="true"></span>{{ $estadoLabel }}
                </span>
                <span class="src-computer-condition src-computer-condition-{{ $computador->condicion ?? 'unknown' }}">
                    Condición: {{ $condicionLabel }}
                </span>
            </div>

            <div class="src-computer-summary-location">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                {{ $bloqueAula }} · {{ $nombreAula }}
            </div>

            <div class="src-computer-uuid">
                <label for="computerUuid">Código Único (UUID)</label>
                <div>
                    <code id="computerUuid">{{ $computador->codigo }}</code>
                    <button
                        type="button"
                        data-copy-computer-uuid="{{ $computador->codigo }}"
                        aria-label="Copiar UUID"
                        title="Copiar UUID"
                    >
                        <i class="far fa-copy" aria-hidden="true"></i> Copiar
                    </button>
                </div>
            </div>

            <div class="src-computer-audit">
                <span>Registrado: {{ optional($computador->created_at)->format('d/m/Y') ?? 'Sin fecha' }}</span>
                <span>Actualizado: {{ optional($computador->updated_at)->format('d/m/Y') ?? 'Sin fecha' }}</span>
            </div>
        </aside>

        <div class="src-computer-detail-main">
            <section class="src-computer-detail-card">
                <header>
                    <span class="src-computer-detail-card-icon"><i class="fas fa-microchip" aria-hidden="true"></i></span>
                    <h2>Ficha técnica y especificaciones</h2>
                </header>
                <div class="src-computer-specification-grid">
                    <article>
                        <span class="src-computer-specification-icon"><i class="fas fa-barcode" aria-hidden="true"></i></span>
                        <div><small>Número de Serial</small><strong>{{ $computador->serial }}</strong></div>
                    </article>
                    <article>
                        <span class="src-computer-specification-icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
                        <div><small>Ubicación</small><strong>{{ $bloqueAula }} · {{ $nombreAula }}</strong></div>
                    </article>
                    <article>
                        <span class="src-computer-specification-icon"><i class="fas fa-microchip" aria-hidden="true"></i></span>
                        <div><small>Procesador</small><strong>{{ $computador->procesador ?? 'No especificado' }}</strong></div>
                    </article>
                    <article>
                        <span class="src-computer-specification-icon"><i class="fas fa-memory" aria-hidden="true"></i></span>
                        <div><small>Memoria RAM</small><strong>{{ $computador->ram_gb ? $computador->ram_gb . ' GB RAM' : 'No especificada' }}</strong></div>
                    </article>
                    <article>
                        <span class="src-computer-specification-icon"><i class="fas fa-hdd" aria-hidden="true"></i></span>
                        <div><small>Almacenamiento</small><strong>{{ $computador->almacenamiento ?? 'No especificado' }}</strong></div>
                    </article>
                    <article>
                        <span class="src-computer-specification-icon"><i class="fab fa-windows" aria-hidden="true"></i></span>
                        <div><small>Sistema Operativo</small><strong>{{ $computador->sistema_operativo ?? 'No especificado' }}</strong></div>
                    </article>
                    <article>
                        <span class="src-computer-specification-icon"><i class="fas fa-desktop" aria-hidden="true"></i></span>
                        <div><small>Tipo de Equipo</small><strong>Computador {{ $tipoEquipo }}</strong></div>
                    </article>
                    <article>
                        <span class="src-computer-specification-icon"><i class="fas fa-award" aria-hidden="true"></i></span>
                        <div><small>Condición Física</small><strong>{{ $condicionLabel }}</strong></div>
                    </article>
                </div>
            </section>

            <section class="src-computer-detail-card src-computer-observations-card">
                <header>
                    <span class="src-computer-detail-card-icon"><i class="far fa-comment-alt" aria-hidden="true"></i></span>
                    <h2>Observaciones y notas</h2>
                </header>
                <div class="src-computer-observations">
                    <span><i class="far fa-comment-alt" aria-hidden="true"></i> Observaciones</span>
                    <p>{{ $computador->observaciones ?: 'Sin observaciones adicionales.' }}</p>
                </div>
            </section>
        </div>
    </div>
@stop

@section('js')
    <script>
        document.querySelectorAll('[data-copy-computer-uuid]').forEach(function (button) {
            button.addEventListener('click', function () {
                var uuid = button.dataset.copyComputerUuid;
                var copied = function () {
                    button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Copiado';
                    window.setTimeout(function () {
                        button.innerHTML = '<i class="far fa-copy" aria-hidden="true"></i> Copiar';
                    }, 1500);
                };
                var failed = function () {
                    Swal.fire({
                        title: 'No se pudo copiar',
                        text: 'Verifica los permisos del portapapeles e inténtalo de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#a6262e'
                    });
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(uuid).then(copied).catch(failed);
                    return;
                }

                var input = document.createElement('textarea');
                input.value = uuid;
                input.setAttribute('readonly', '');
                input.style.position = 'absolute';
                input.style.left = '-9999px';
                document.body.appendChild(input);
                input.select();
                var success = document.execCommand('copy');
                input.remove();
                if (success) copied();
                else failed();
            });
        });
    </script>
@stop


