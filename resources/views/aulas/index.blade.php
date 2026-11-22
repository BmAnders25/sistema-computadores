@extends('adminlte::page')

@section('title', 'Aulas y Laboratorios')

@section('content_header')
    <h1 class="m-0 text-dark">Administración de Aulas y Laboratorios</h1>
@stop

@section('content')

    <x-adminlte-card title="Listado de Aulas y Laboratorios" theme="primary" theme-mode="outline">
        @can('crear-aulas')
            <a class="btn btn-primary mb-3" href="{{ route('aulas.create') }}">
                <i class="fa fa-plus-circle mr-1"></i> Nueva Aula / Laboratorio
            </a>
        @endcan

        @php
            $heads = [
                'ID',
                'Bloque',
                'Nombre / Laboratorio',
                'Capacidad',
                'Equipos Asociados',
                'Estado',
                'Acciones'
            ];
            $config['language'] = ['url' => asset('vendor/datatables/es-CO.json')];
        @endphp

        <x-adminlte-datatable id="aulasTable" :heads="$heads" head-theme="dark"
            :config="$config" striped hoverable with-buttons responsive>

            @foreach ($aulas as $aula)
                <tr>
                    {{-- ID --}}
                    <td>{{ $aula->id }}</td>

                    {{-- Bloque --}}
                    <td>
                        <span class="badge bg-purple px-2 py-1 text-md">{{ $aula->bloque }}</span>
                    </td>

                    {{-- Nombre --}}
                    <td><strong>{{ $aula->nombre }}</strong></td>

                    {{-- Capacidad --}}
                    <td>
                        <span class="text-dark font-weight-bold">
                            <i class="fas fa-users text-info mr-1"></i>{{ $aula->capacidad ?? 'N/A' }} estudiantes
                        </span>
                    </td>

                    {{-- Computadores asociados --}}
                    <td>
                        <span class="badge bg-secondary px-2 py-1">
                            <i class="fas fa-desktop mr-1"></i>{{ $aula->computadores_count ?? $aula->computadores()->count() }} equipos
                        </span>
                    </td>

                    {{-- Estado --}}
                    <td>
                        @if ($aula->estado == 'activo' || $aula->estado == 'disponible')
                            <span class="badge bg-gradient-success px-2 py-1 text-md"><i class="fas fa-check-circle mr-1"></i> Activo</span>
                        @elseif ($aula->estado == 'mantenimiento')
                            <span class="badge bg-gradient-warning px-2 py-1 text-md"><i class="fas fa-tools mr-1"></i> Mantenimiento</span>
                        @else
                            <span class="badge bg-gradient-danger px-2 py-1 text-md"><i class="fas fa-ban mr-1"></i> Inactivo</span>
                        @endif
                    </td>

                    {{-- Acciones --}}
                    <td class="text-center align-middle text-nowrap">
                        <div class="d-inline-flex align-items-center">
                            {{-- Botón Ver --}}
                            <a class="btn bg-gradient-info btn-sm mr-1" href="{{ route('aulas.show', $aula->id) }}" title="Ver detalles">
                                <i class="far fa-eye fa-fw text-white"></i>
                            </a>

                            {{-- Botón Editar --}}
                            @can('editar-aulas')
                            <a class="btn bg-gradient-yellow btn-sm mr-1" href="{{ route('aulas.edit', $aula->id) }}" title="Editar">
                                <i class="fas fa-pencil-alt fa-fw text-black"></i>
                            </a>
                            @endcan

                            {{-- Botón Eliminar --}}
                            @can('borrar-aulas')
                            <form method="POST" action="{{ route('aulas.destroy', $aula->id) }}" style="display:inline-block;" class="delete-form m-0 p-0">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn bg-gradient-danger btn-sm delete-button" title="Eliminar aula">
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
    #aulasTable tbody td:last-child {
        white-space: nowrap !important;
        width: 1% !important;
    }
    #aulasTable tbody td {
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
                    title: "¿Eliminar aula o laboratorio?",
                    text: "Esta acción no se puede deshacer si tiene registros asociados.",
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