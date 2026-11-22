@extends('adminlte::page')

@section('title', 'Gestión de Reservas')

@section('content_header')
    <h1 class="m-0 text-dark">Listado de Reservas</h1>
@stop

@section('content')
    <x-adminlte-card title="Historial y Reservas Activas" theme="primary" theme-mode="outline">
        @can('crear-reservas')
            <a class="btn btn-primary mb-3" href="{{ route('reservas.create') }}">
                <i class="fa fa-plus-circle mr-1"></i> Nueva Reserva
            </a>
        @endcan

        @php
            $heads = [
                'ID',
                'Usuario',
                'Tipo / Destino',
                'Fecha',
                'Bloque Horario',
                'Clave Única',
                'Estado',
                'Acciones'
            ];
            $config['language'] = ['url' => asset('vendor/datatables/es-CO.json')];
        @endphp

        <x-adminlte-datatable id="reservasTable" :heads="$heads" head-theme="dark"
            :config="$config" striped hoverable with-buttons responsive>

            @foreach ($reservas as $reserva)
                <tr>
                    <td>{{ $reserva->id }}</td>
                    <td><strong>{{ $reserva->user->name ?? 'N/A' }}</strong></td>
                    <td>
                        @if($reserva->computador_id)
                            <span class="badge bg-info">Computador #{{ $reserva->computador_id }}</span>
                            <div class="small text-muted">Serial: {{ $reserva->computador->serial ?? 'N/A' }}</div>
                        @else
                            <span class="badge bg-purple">Aula Completa</span>
                            <div class="small text-muted">{{ $reserva->aula->nombre ?? 'N/A' }}</div>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}</td>
                    <td>
                        <div><strong>{{ $reserva->bloque->nombre ?? 'N/A' }}</strong></div>
                        <span class="small text-muted">
                            <i class="far fa-clock"></i> {{ $reserva->bloque->hora_inicio ?? '' }} - {{ $reserva->bloque->hora_fin ?? '' }}
                        </span>
                    </td>
                    <td><span class="text-monospace text-secondary">{{ $reserva->clave_unica }}</span></td>
                    <td>
                        @if ($reserva->estado == 'pendiente')
                            <span class="badge bg-gradient-warning px-2 py-1 text-md"><i class="fas fa-hourglass-half mr-1"></i> Pendiente</span>
                        @elseif ($reserva->estado == 'entregada')
                            <span class="badge bg-gradient-success px-2 py-1 text-md"><i class="fas fa-check-circle mr-1"></i> Entregada</span>
                        @elseif ($reserva->estado == 'devuelta')
                            <span class="badge bg-gradient-info px-2 py-1 text-md"><i class="fas fa-undo mr-1"></i> Devuelta</span>
                        @elseif ($reserva->estado == 'cancelada')
                            <span class="badge bg-gradient-danger px-2 py-1 text-md"><i class="fas fa-ban mr-1"></i> Cancelada</span>
                        @elseif ($reserva->estado == 'no_show')
                            <span class="badge bg-gradient-dark px-2 py-1 text-md"><i class="fas fa-user-slash mr-1"></i> No se presentó</span>
                        @else
                            <span class="badge bg-gradient-secondary px-2 py-1 text-md">{{ ucfirst($reserva->estado) }}</span>
                        @endif
                    </td>
                    <td class="text-center align-middle text-nowrap">
                        <div class="d-inline-flex align-items-center">
                            {{-- Ver Detalles --}}
                            <a class="btn bg-gradient-info btn-sm mr-1" href="{{ route('reservas.show', $reserva->id) }}" title="Ver detalles">
                                <i class="far fa-eye fa-fw text-white"></i>
                            </a>

                            {{-- Aprobar / Confirmar Ingreso (Solo Celador o Administración) --}}
                            @role('Celador|Administracion')
                                @if($reserva->estado == 'pendiente')
                                    <form action="{{ route('reservas.confirmar', $reserva->id) }}" method="POST" style="display:inline-block;" class="m-0 p-0 mr-1">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn bg-gradient-success btn-sm" title="Confirmar Ingreso">
                                            <i class="fas fa-check fa-fw text-white"></i>
                                        </button>
                                    </form>
                                @endif
                            @endrole

                            {{-- Cancelar Reserva[cite: 6] --}}
                            @can('cancelar-reservas')
                                @if($reserva->estado == 'pendiente')
                                <form method="POST" action="{{ route('reservas.destroy', $reserva->id) }}" style="display:inline-block;" class="delete-form m-0 p-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn bg-gradient-danger btn-sm cancel-button" title="Cancelar reserva">
                                        <i class="fas fa-times fa-fw text-white"></i>
                                    </button>
                                </form>
                                @endif
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
    #reservasTable tbody td:last-child {
        white-space: nowrap !important;
        width: 1% !important;
    }
    #reservasTable tbody td {
        vertical-align: middle !important;
        padding: 0.75rem 0.5rem !important;
    }
</style>
@stop

@section('js')
    @if ($message = Session::get('success'))
        <script>
            Swal.fire({
                title: "¡Éxito!",
                text: "{{ $message }}",
                timer: 2500,
                icon: "success",
                confirmButtonColor: '#3085d6'
            });
        </script>
    @endif
    @if ($message = Session::get('error'))
        <script>
            Swal.fire({
                title: "Atención",
                text: "{{ $message }}",
                icon: "error",
                confirmButtonColor: '#d33'
            });
        </script>
    @endif

    <script>
        document.addEventListener('click', function(e) {
            if (e.target.closest('.cancel-button')) {
                var button = e.target.closest('.cancel-button');
                var form = button.closest('form');
                Swal.fire({
                    title: "¿Cancelar reserva?",
                    text: "Esta acción liberará el equipo o aula apartada.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: "Sí, cancelar",
                    cancelButtonText: "Volver"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        });
    </script>
@stop