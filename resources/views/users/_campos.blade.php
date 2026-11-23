@php
    $esEdicion = isset($user);
    $rolActual = old('roles', $esEdicion ? $user->roles->first()?->name : null);
    $estadoActual = old('estado', $esEdicion ? $user->estado : 'activo');
    $dosPasos = old('two_factor_enabled', $esEdicion ? $user->two_factor_enabled : false);
@endphp

<section class="src-form-section">
    <header class="src-form-section-heading">
        <span><i class="fas fa-user" aria-hidden="true"></i></span>
        <div><h2>Información de la cuenta</h2><p>Datos de identificación y contacto del usuario.</p></div>
    </header>
    <div class="src-form-grid">
        <div class="src-form-field">
            <label for="name">Nombre completo <span aria-hidden="true">*</span></label>
            <input class="src-form-control" id="name" name="name" type="text" value="{{ old('name', $user->name ?? '') }}" autocomplete="off" required>
            @error('name')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
        <div class="src-form-field">
            <label for="email">Correo electrónico <span aria-hidden="true">*</span></label>
            <input class="src-form-control" id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" placeholder="usuario@unipamplona.edu.co" autocomplete="off" required>
            @error('email')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
        <div class="src-form-field">
            <label for="codigo_institucional">Código institucional</label>
            <input class="src-form-control" id="codigo_institucional" name="codigo_institucional" type="text" value="{{ old('codigo_institucional', $user->codigo_institucional ?? '') }}" placeholder="Obligatorio para estudiantes">
            @error('codigo_institucional')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
        <div class="src-form-field">
            <label for="programa">Programa académico</label>
            <input class="src-form-control" id="programa" name="programa" type="text" value="{{ old('programa', $user->programa ?? '') }}" placeholder="Ej. Ingeniería de Sistemas">
            @error('programa')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
    </div>
</section>

<section class="src-form-section">
    <header class="src-form-section-heading">
        <span><i class="fas fa-user-shield" aria-hidden="true"></i></span>
        <div><h2>Acceso y seguridad</h2><p>Asigna el rol, el estado y las credenciales de acceso.</p></div>
    </header>
    <div class="src-form-grid">
        <div class="src-form-field">
            <label for="roles">Rol del usuario <span aria-hidden="true">*</span></label>
            <select class="src-form-control" id="roles" name="roles" required>
                <option value="">Seleccionar rol</option>
                @foreach ($roles as $nombre)
                    <option value="{{ $nombre }}" @selected($rolActual === $nombre)>{{ $nombre }}</option>
                @endforeach
            </select>
            @error('roles')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
        <div class="src-form-field">
            <label for="estado">Estado de la cuenta <span aria-hidden="true">*</span></label>
            <select class="src-form-control" id="estado" name="estado" required>
                @foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo', 'suspendido' => 'Suspendido'] as $valor => $texto)
                    <option value="{{ $valor }}" @selected($estadoActual === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
            @error('estado')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
        <div class="src-form-field">
            <label for="password">{{ $esEdicion ? 'Nueva contraseña' : 'Contraseña' }}{{ $esEdicion ? '' : ' *' }}</label>
            <input class="src-form-control" id="password" name="password" type="password" placeholder="{{ $esEdicion ? 'Déjala en blanco para no cambiarla' : 'Mínimo 8 caracteres' }}" autocomplete="new-password" {{ $esEdicion ? '' : 'required' }}>
            @error('password')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
        <div class="src-form-field">
            <label for="confirm-password">{{ $esEdicion ? 'Confirmar nueva contraseña' : 'Confirmar contraseña' }}{{ $esEdicion ? '' : ' *' }}</label>
            <input class="src-form-control" id="confirm-password" name="confirm-password" type="password" placeholder="Repite la contraseña" autocomplete="new-password" {{ $esEdicion ? '' : 'required' }}>
            @error('confirm-password')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
        <div class="src-form-field src-form-field-full">
            <label class="src-form-checkbox">
                <input id="two_factor_enabled" name="two_factor_enabled" type="checkbox" value="1" @checked($dosPasos)>
                <span><strong>Exigir verificación en dos pasos</strong><small>Los correos institucionales siempre la exigen. Esta opción solo aplica a correos externos.</small></span>
            </label>
            @error('two_factor_enabled')<span class="src-form-error">{{ $message }}</span>@enderror
        </div>
    </div>
</section>
