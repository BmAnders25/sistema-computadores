@extends('adminlte::page')

@section('title', 'S.O.A.H')

@section('content_header')
<h1 class="m-0 text-dark">Detalles del Rol</h1>
@stop

@section('content')
<x-adminlte-card>
    <div class="row">
        <div class="col-md-5">
            <x-adminlte-input name="name" label="Nombre Rol" placeholder="Nombre Rol" disabled :value="$role->name" />
        </div>
        <div class="col-md-9"></div> <!-- Columna vacía para ocupar espacio -->
    </div>

    <div class="row mt-3">
        <div class="col-md-12">
            <label for="checkPermisos" class="form-label">Permisos para este rol:</label>
            <table class="table table-bordered table-striped">
                <tbody>
                    @foreach ($permission->chunk(4) as $chunk)
                    <tr>
                        @foreach ($chunk as $p)
                        <td>
                            <div class="form-check">
                                <input name="permission[]" class="form-check-input" type="checkbox" value="{{ $p->id }}"
                                       id="{{ $p->id }}" {{ in_array($p->id, $rolePermissions) ? 'checked' : '' }} disabled>
                                <label class="form-check-label" for="{{ $p->id }}">
                                    {{ $p->name }}
                                </label>
                            </div>
                        </td>
                        @endforeach

                        {{-- Asegura que cada fila tenga exactamente 4 columnas --}}
                        @for ($i = $chunk->count(); $i < 4; $i++)
                            <td></td>
                        @endfor
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <br>
    <div class="row">
        <div class="form-group col-md-6">
            <a href="{{ route('roles.index') }}" class="btn btn-secondary mr-2"><i class="fas fa-undo"></i>
                Regresar</a>
        </div>
    </div>
</x-adminlte-card>

@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@stop

@section('footer')
    <footer>
        <p><img src="{{ asset('vendor/adminlte/dist/img/logo.png') }}" width="4%" style="border-radius: 15px" alt="Logo S.O.AH"> © {{ date('Y') }} S.O.A.H.  Sistema De Organización y Administración Hotelera . Todos los derechos reservados.</p>
    </footer>
@stop

