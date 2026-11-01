@extends('adminlte::page')

@section('title', 'Computadores')

@section('content_header')
    <h1 class="m-0 text-dark">Administración de Computadores</h1>
@stop

@section('content')

    <x-adminlte-card title="Listado de Computadores" theme="primary" theme-mode="outline">
        @can('crear-computadores')
            <a class="btn btn-primary mb-3" href="{{ route('computadores.create') }}">
                <i class="fa fa-plus-circle mr-1"></i> Nuevo Computador
            </a>
        @endcan

        @php
            $heads = [
                'ID',
                'Código (UUID)',
                'Serial',
                'Ubicación',
                'Equipo / Marca',
                'Especificaciones',
                'Sistema Operativo',
                'Estado',
                'Condición',
                'Acciones'
            ];
            $config['language'] = ['url' => asset('vendor/datatables/es-CO.json')];
        @endphp

        <x-adminlte-datatable id="computadoresTable" :heads="$heads" head-theme="dark"
            :config="$config" striped hoverable with-buttons responsive>

            @foreach ($computadores as $computador)
                <tr>
                    {{-- ID --}}
                    <td>{{ $computador->id }}</td>
                    
                    {{-- Código UUID acortado con opción de copiar --}}
                    <td>
                        <span class="text-monospace text-muted" title="{{ $computador->codigo }}">
                            {{ Str::limit($computador->codigo, 13, '...') }}
                        </span>
                        <button class="btn btn-xs btn-light border ml-1" onclick="copyToClipboard('{{ $computador->codigo }}', this)" title="Copiar UUID">
                            <i class="far fa-copy text-secondary"></i>
                        </button>
                    </td>

                    {{-- Serial en Negrita --}}
                    <td><strong>{{ $computador->serial }}</strong></td>

                    {{-- Ubicación con datos bien separados --}}
                    <td>
                        <div class="py-1">
                            <span class="badge bg-purple px-2 py-1 mb-1 d-inline-block">{{ $computador->bloque }}</span>
                            <div class="text-muted small">
                                <i class="fas fa-door-open mr-1"></i>{{ $computador->laboratorio_aula }}
                            </div>
                        </div>
                    </td>

                    {{-- Equipo / Marca con datos bien separados --}}
                    <td>
                        <div class="py-1">
                            <div><strong>{{ $computador->marca }}</strong></div>
                            <span class="badge bg-secondary text-uppercase mb-1">{{ str_replace('_', ' ', $computador->tipo_equipo) }}</span>
                            @if($computador->modelo)
                                <div class="text-muted small">{{ $computador->modelo }}</div>
                            @endif
                        </div>
                    </td>

                    {{-- Especificaciones TÉCNICAS con iconos y separación limpia --}}
                    <td>
                        <div class="py-1 small">
                            <div class="mb-1 text-dark">
                                <i class="fas fa-microchip text-info mr-1"></i>{{ $computador->procesador ?? 'N/A' }}
                            </div>
                            <div class="mb-1 text-dark">
                                <i class="fas fa-memory text-warning mr-1"></i>{{ $computador->ram_gb ? $computador->ram_gb . ' GB RAM' : 'Sin RAM' }}
                            </div>
                            <div class="text-dark">
                                <i class="fas fa-hdd text-success mr-1"></i>{{ $computador->almacenamiento ?? 'N/A' }}
                            </div>
                        </div>
                    </td>

                    {{-- Sistema Operativo --}}
                    <td>
                        <span class="text-secondary font-weight-bold">
                            <i class="fab fa-windows text-primary mr-1"></i>{{ $computador->sistema_operativo ?? 'N/A' }}
                        </span>
                    </td>

                    {{-- Estado --}}
                    <td>
                        @if ($computador->estado == 'disponible')
                            <span class="badge bg-gradient-success px-2 py-1 text-md"><i class="fas fa-check-circle mr-1"></i> Disponible</span>
                        @elseif ($computador->estado == 'prestado')
                            <span class="badge bg-gradient-primary px-2 py-1 text-md"><i class="fas fa-hand-holding mr-1"></i> Prestado</span>
                        @elseif ($computador->estado == 'reservado')
                            <span class="badge bg-gradient-info px-2 py-1 text-md"><i class="fas fa-bookmark mr-1"></i> Reservado</span>
                        @elseif ($computador->estado == 'mantenimiento')
                            <span class="badge bg-gradient-warning px-2 py-1 text-md"><i class="fas fa-tools mr-1"></i> Mantenimiento</span>
                        @else
                            <span class="badge bg-gradient-danger px-2 py-1 text-md"><i class="fas fa-ban mr-1"></i> Inactivo</span>
                        @endif
                    </td>

                    {{-- Condición --}}
                    <td>
                        @if ($computador->condicion == 'excelente')
                            <span class="badge bg-success px-2 py-1 text-md">Excelente</span>
                        @elseif ($computador->condicion == 'bueno')
                            <span class="badge bg-info px-2 py-1 text-md">Bueno</span>
                        @elseif ($computador->condicion == 'regular')
                            <span class="badge bg-warning px-2 py-1 text-md">Regular</span>
                        @else
                            <span class="badge bg-danger px-2 py-1 text-md">Defectuoso</span>
                        @endif
                    </td>

                    {{-- Acciones Exactas al Módulo de Habitaciones --}}
                    <td class="text-center align-middle text-nowrap">
                        <div class="d-inline-flex align-items-center">
                        {{-- Botón Ver --}}
                            <a class="btn bg-gradient-info btn-sm mr-1" href="{{ route('computadores.show', $computador->id) }}" title="Ver detalles">
                            <i class="far fa-eye fa-fw text-white"></i></a>

                    {{-- Botón Editar --}}
        @can('editar-computadores')
        <a class="btn bg-gradient-yellow btn-sm mr-1" href="{{ route('computadores.edit', $computador->id) }}" title="Editar">
            <i class="fas fa-pencil-alt fa-fw text-black"></i>
        </a>
        @endcan

        {{-- Botón Eliminar --}}
        @can('borrar-computadores')
        <form method="POST" action="{{ route('computadores.destroy', $computador->id) }}" style="display:inline-block;" class="delete-form m-0 p-0">
            @csrf
            @method('DELETE')
            <button type="button" class="btn bg-gradient-danger btn-sm delete-button" title="Inactivar equipo">
                <i class="far fa-trash-alt fa-fw text-white"></i>
            </button>
        </form>
        @endcan
    </div>
</td>
                </tr>
            @endforeach
        </x-adminlte-datatable>
    </x-adminlte-card>
@stop

@section('css')
<style>
    /* Previene el salto de línea en la columna de acciones y asegura alineación horizontal */
    #computadoresTable tbody td:last-child {
        white-space: nowrap !important;
        width: 1% !important;
    }

    #computadoresTable tbody td {
        vertical-align: middle !important;
        padding: 0.75rem 0.5rem !important;
    }
</style>
@stop

@section('footer')
    <div class="text-center text-muted">
        <p>
            <img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" alt="Logo UP" style="max-height: 70px; border-radius: 15px" class="mr-2"> 
            © {{ date('Y') }} S.R.C - Sistema de Reserva de Computadores - Universidad de Pamplona Sede Villa del Rosario. Todos los derechos reservados.
        </p>
    </div>
@stop

@section('js')
    <script>
        function copyToClipboard(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                let originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check text-success"></i>';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                }, 1500);
            });
        }
    </script>

    @if ($message = Session::get('success'))
        <script>
            Swal.fire({
                title: "¡Operación Exitosa!",
                text: "{{ $message }}",
                timer: 2500,
                icon: "success",
                confirmButtonColor: '#3085d6',
                buttonsStyling: true
            });
        </script>
    @endif

    <script>
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-button')) {
                var button = e.target.closest('.delete-button');
                var form = button.closest('form');
                Swal.fire({
                    title: "¿Inactivar equipo de cómputo?",
                    text: "El equipo cambiará su estado a inactivo y no estará disponible para préstamos.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: "Sí, inactivar",
                    cancelButtonText: "Cancelar",
                    buttonsStyling: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        });
    </script>

    @if ($message = Session::get('error'))
        <script>
            Swal.fire({ 
                title: "Acción no permitida", 
                text: "{{ $message }}", 
                icon: "error",
                confirmButtonColor: '#3085d6'
            });
        </script>
    @endif
@stop