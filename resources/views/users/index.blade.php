@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <h1 class="m-0 text-dark">
        Administración de Usuarios</h1>
@stop

@section('content')
    <x-adminlte-card>
        @can('crear-usuarios')
            <a class="btn btn-primary mr-2" href="{{ route('users.create') }}" role="button"><i class="fa fa-plus"></i> Nuevo
                Usuario</a>
        @endcan
        <div class="card-body">
            @php
                $config['language'] = ['url' => asset('vendor/datatables/es-CO.json')];
                //$config['paging'] = true;
                //$config['lengthMenu'] = [10, 50, 100, 500];
            @endphp
            <x-adminlte-datatable id="table1" :heads="['Id', 'Código', 'Nombre', 'Email', 'Rol', 'Estado', 'Acciones']" head-theme="dark" :config=$config striped hoverable
                with-buttons>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        {{-- Se amplían las opciones por si tu columna se llama de otra forma en la BD --}}
                       <td>{{ $user->codigo_institucional ?? 'N/A' }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if (!empty($user->getRoleNames()))
                                @foreach ($user->getRoleNames() as $rolNombre)
                                    @php
                                        $badgeColor = 'secondary';
                                        if (strtolower($rolNombre) == 'administrador' || strtolower($rolNombre) == 'admin') {
                                            $badgeColor = 'danger';
                                        } elseif (strtolower($rolNombre) == 'docente' || strtolower($rolNombre) == 'profesor') {
                                            $badgeColor = 'primary';
                                        } elseif (strtolower($rolNombre) == 'estudiante') {
                                            $badgeColor = 'success';
                                        }
                                    @endphp
                                    <h5><span class="badge badge-{{ $badgeColor }}">{{ $rolNombre }}</span></h5>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            {{-- Envuelto en <h5> para igualar exactamente el tamaño y padding del rol --}}
                            @php
                                $estadoActivo = method_exists($user, 'esActivo') ? $user->esActivo() : ($user->estado ?? true);
                            @endphp

                            @if ($estadoActivo)
                                <h5><span class="badge badge-success">Activo</span></h5>
                            @else
                                <h5><span class="badge badge-warning">Suspendido</span></h5>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex" role="group">
                                <a class="btn btn-info btn-sm mr-1" href="{{ route('users.show', $user->id) }}" role="button" title="Ver">
                                    <i class="far fa-eye fa-fw"></i>
                                </a>
                                @can('editar-usuarios')
                                    <a class="btn btn-success btn-sm mr-1" href="{{ route('users.edit', $user->id) }}" role="button" title="Editar">
                                        <i class="fas fa-pencil-alt fa-fw"></i>
                                    </a>
                                @endcan
                                @can('borrar-usuarios')
                                    <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="display: inline;" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-warning btn-sm delete-button" title="Eliminar">
                                            <i class="far fa-trash-alt fa-fw"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-adminlte-datatable>
        </div>
    </x-adminlte-card>
@stop

@section('footer')
<footer>
    <p><img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" width="4%" style="border-radius: 15px" alt="Logo S.O.AH"> © {{ date('Y') }} S.O.A.H.  Sistema De Organización y Administración Hotelera . Todos los derechos reservados.</p>
</footer>
@stop

@section('js')

    @if ($message = Session::get('success'))
        <script>
            Swal.fire({
                title: "Operación Exitosa!",
                text: "{{ $message }}",
                timer: 2000,
                icon: "success"
            });
        </script>
    @endif

    <script>
        var deleteButtons = document.querySelectorAll('.delete-button');
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var form = this.parentElement;
                Swal.fire({
                    title: "¿Estás seguro de Eliminar este Usuario?",
                    text: "¡No se podrá recuperar la información!",
                    icon: "error",
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "¡Sí, Eliminar!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                })
            });
        });
    </script>
@stop