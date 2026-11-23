@php
    $selectedPermissions = old('permission', $rolePermissions ?? []);
    $permissionGroupNames = [
        'disponibilidad' => 'Reservas',
        'reservas' => 'Reservas',
        'computadores' => 'Computadores',
        'aulas' => 'Aulas',
        'bloques-horarios' => 'Bloques horarios',
        'entregas' => 'Entregas y devoluciones',
        'devoluciones' => 'Entregas y devoluciones',
        'mantenimientos' => 'Mantenimiento',
        'usuarios' => 'Usuarios',
        'roles' => 'Roles',
        'permisos' => 'Permisos',
        'reportes' => 'Reportes',
        'estadistica' => 'Reportes',
        'configuracion' => 'Administración',
    ];
    $permissionGroups = $permission->groupBy(function ($p) use ($permissionGroupNames) {
        if ($p->name === 'home' || $p->name === 'reservar-aulas') {
            return $p->name === 'home' ? 'General' : 'Reservas';
        }

        $module = preg_replace('/^(ver|crear|editar|borrar|cancelar|registrar|aprobar)-/', '', $p->name);

        return $permissionGroupNames[$module] ?? 'Otros';
    });
@endphp

<div class="src-permissions-grid">
    @foreach ($permissionGroups as $group => $groupPermissions)
        <section class="src-permission-group" data-permission-group="{{ \Illuminate\Support\Str::slug($group) }}">
            <header class="src-permission-group-header">
                <h3>{{ $group }}</h3>
                @unless ($readOnly)
                    <button class="src-permission-select-all" type="button">Seleccionar todos</button>
                @endunless
            </header>
            <div class="src-permission-options">
                @foreach ($groupPermissions as $p)
                    <label class="src-permission-option" for="{{ $p->id }}">
                        <input name="permission[]" type="checkbox" value="{{ $p->id }}" id="{{ $p->id }}"
                            {{ in_array($p->id, $selectedPermissions) ? 'checked' : '' }}
                            {{ $readOnly ? 'disabled' : '' }}>
                        <span>{{ $p->name }}</span>
                    </label>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

@unless ($readOnly)
    <script>
        document.querySelectorAll('.src-permission-select-all').forEach(function(button) {
            button.addEventListener('click', function() {
                this.closest('.src-permission-group')
                    .querySelectorAll('input[name="permission[]"]')
                    .forEach(function(input) {
                        input.checked = true;
                    });
            });
        });
    </script>
@endunless
