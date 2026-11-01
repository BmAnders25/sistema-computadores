@php
    $esEdicion = isset($user);
    $rolActual = old('roles', $esEdicion ? $user->roles->first()?->name : null);
    $estadoActual = old('estado', $esEdicion ? $user->estado : 'activo');
    $dosPasos = old('two_factor_enabled', $esEdicion ? $user->two_factor_enabled : false);
@endphp

<div class="row">
    <x-adminlte-input name="name" type="text" label="Nombre completo" placeholder="Nombre completo"
        fgroup-class="col-md-6" value="{{ old('name', $user->name ?? '') }}" autocomplete="off" />
    <x-adminlte-input name="email" type="email" label="Correo" placeholder="usuario@unipamplona.edu.co"
        fgroup-class="col-md-6" value="{{ old('email', $user->email ?? '') }}" autocomplete="off" />
</div>

<div class="row">
    <x-adminlte-input name="codigo_institucional" type="text" label="Código institucional"
        placeholder="Obligatorio para estudiantes" fgroup-class="col-md-6"
        value="{{ old('codigo_institucional', $user->codigo_institucional ?? '') }}" />
    <x-adminlte-input name="programa" type="text" label="Programa académico"
        placeholder="Ej. Ingeniería de Sistemas" fgroup-class="col-md-6"
        value="{{ old('programa', $user->programa ?? '') }}" />
</div>

<div class="row">
    <x-adminlte-input name="password" type="password"
        label="{{ $esEdicion ? 'Nueva contraseña' : 'Contraseña' }}"
        placeholder="{{ $esEdicion ? 'Déjala en blanco para no cambiarla' : 'Mínimo 8 caracteres' }}"
        fgroup-class="col-md-6" autocomplete="new-password" />
    <x-adminlte-input name="confirm-password" type="password" label="Confirmar contraseña"
        placeholder="Repite la contraseña" fgroup-class="col-md-6" autocomplete="new-password" />
</div>

<div class="row">
    <x-adminlte-select name="roles" label="Rol del usuario" fgroup-class="col-md-6">
        <x-slot name="prependSlot">
            <div class="input-group-text bg-gradient-info"><i class="fas fa-user"></i></div>
        </x-slot>
        <option value="">Seleccionar rol</option>
        @foreach ($roles as $nombre)
            <option value="{{ $nombre }}" @selected($rolActual === $nombre)>{{ $nombre }}</option>
        @endforeach
    </x-adminlte-select>

    <x-adminlte-select name="estado" label="Estado de la cuenta" fgroup-class="col-md-6">
        <x-slot name="prependSlot">
            <div class="input-group-text bg-gradient-secondary"><i class="fas fa-toggle-on"></i></div>
        </x-slot>
        @foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo', 'suspendido' => 'Suspendido'] as $valor => $texto)
            <option value="{{ $valor }}" @selected($estadoActual === $valor)>{{ $texto }}</option>
        @endforeach
    </x-adminlte-select>
</div>

<div class="row">
    <div class="form-group col-md-12">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="two_factor_enabled"
                name="two_factor_enabled" value="1" @checked($dosPasos)>
            <label class="custom-control-label" for="two_factor_enabled">
                Exigir verificación en dos pasos
            </label>
        </div>
        <small class="text-muted">
            Los correos institucionales siempre la exigen. Esta casilla solo aplica a correos externos.
        </small>
    </div>
</div>