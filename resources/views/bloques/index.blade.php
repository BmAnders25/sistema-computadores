@extends('adminlte::page')

@section('title', 'Bloques Horarios')

@section('content_header')
    <h1 class="m-0 text-dark">Administración de Bloques Horarios</h1>
@stop

@section('content')

    <x-adminlte-card title="Listado de Bloques Horarios" theme="primary" theme-mode="outline">
        @can('crear-bloques-horarios')
            <a class="btn btn-primary mb-3" href="{{ route('bloques-horarios.create') }}">
                <i class="fa fa-plus-circle mr-1"></i> Nuevo Bloque Horario
            </a>
        @endcan

        @php
            $heads = [
                'ID',
                'Nombre del Bloque',
                'Hora de Inicio',
                'Hora de Fin',
                'Duración',
                'Estado',
                'Acciones'
            ];
            $config['language'] = ['url' => asset('vendor/datatables/es-CO.json')];
        @endphp

        <x-adminlte-datatable id="bloquesTable" :heads="$heads" head-theme="dark"
            :config="$config" striped hoverable with-buttons responsive>

            @foreach ($bloques as $bloque)
                @php
                    // Cálculo dinámico de la duración para mostrarla amigablemente
                    $inicio = \Carbon\Carbon::parse($bloque->hora_inicio);
                    $fin = \Carbon\Carbon::parse($bloque->hora_fin);
                    $minutos = $inicio->diffInMinutes($fin);
                    $horasTotales = floor($minutos / 60);
                    $minRestantes = $minutos % 60;
                    $duracionTexto = ($horasTotales > 0 ? $horasTotales . 'h ' : '') . ($minRestantes > 0 ? $minRestantes . 'min' : '');
                @endphp
                <tr>
                    {{-- ID --}}
                    <td>{{ $bloque->id }}</td>

                    {{-- Nombre --}}
                    <td><strong>{{ $bloque->nombre }}</strong></td>

                    {{-- Hora Inicio --}}
                    <td>
                        <span class="text-dark">
                            <i class="far fa-clock text-info mr-1"></i>{{ \Carbon\Carbon::parse($bloque->hora_inicio)->format('h:i A') }}
                        </span>
                    </td>

                    {{-- Hora Fin --}}
                    <td>
                        <span class="text-dark">
                            <i class="far fa-clock text-danger mr-1"></i>{{ \Carbon\Carbon::parse($bloque->hora_fin)->format('h:i A') }}
                        </span>
                    </td>

                    {{-- Duración --}}
                    <td>
                        <span class="badge bg-secondary px-2 py-1">
                            <i class="fas fa-hourglass-half mr-1"></i>{{ $duracionTexto }}
                        </span>
                    </td>

                    {{-- Estado --}}
                    <td>
                        @if ($bloque->activo)
                            <span class="badge bg-gradient-success px-2 py-1 text-md"><i class="fas fa-check-circle mr-1"></i> Activo</span>
                        @else
                            <span class="badge bg-gradient-danger px-2 py-1 text-md"><i class="fas fa-ban mr-1"></i> Inactivo</span>
                        @endif
                    </td>

                    {{-- Acciones --}}
                    <td class="text-center align-middle text-nowrap">
                        <div class="d-inline-flex align-items-center">
                            {{-- Botón Ver --}}
                            <a class="btn bg-gradient-info btn-sm mr-1" href="{{ route('bloques-horarios.show', $bloque->id) }}" title="Ver detalles UX">
                                <i class="far fa-eye fa-fw text-white"></i>
                            </a>

                            {{-- Botón Editar --}}
                            @can('editar-bloques-horarios')
                            <a class="btn bg-gradient-yellow btn-sm mr-1" href="{{ route('bloques-horarios.edit', $bloque->id) }}" title="Editar">
                                <i class="fas fa-pencil-alt fa-fw text-black"></i>
                            </a>
                            @endcan

                            {{-- Botón Eliminar --}}
                            @can('borrar-bloques-horarios')
                            <form method="POST" action="{{ route('bloques-horarios.destroy', $bloque->id) }}" style="display:inline-block;" class="delete-form m-0 p-0">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn bg-gradient-danger btn-sm delete-button" title="Eliminar bloque">
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
    #bloquesTable tbody td:last-child {
        white-space: nowrap !important;
        width: 1% !important;
    }
    #bloquesTable tbody td {
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
                    title: "¿Eliminar bloque horario?",
                    text: "Esta acción eliminará el bloque y podría afectar las programaciones activas.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: "Sí, eliminar",
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
@stop