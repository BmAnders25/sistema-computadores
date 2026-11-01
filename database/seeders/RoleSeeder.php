<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Estudiante', 'Docente', 'Celador', 'Administracion'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $todos = ['Estudiante', 'Docente', 'Celador', 'Administracion'];
        $admin = ['Administracion'];
        $staff = ['Celador', 'Administracion'];
        $usuarios = ['Estudiante', 'Docente', 'Administracion'];

        $matriz = [
            'home'                   => $todos,
            // Reservas (estudiante/docente solo ven y cancelan las suyas: se valida en una Policy)
            'ver-disponibilidad'     => $todos,
            'ver-reservas'           => $todos,
            'crear-reservas'         => $usuarios,
            'cancelar-reservas'      => $usuarios,
            'reservar-aulas'         => ['Docente', 'Administracion'],
            // Entregas y devoluciones
            'ver-entregas'           => $staff,
            'registrar-entregas'     => $staff,
            'registrar-devoluciones' => $staff,
            // Inventario
            'ver-computadores'       => $staff,
            'crear-computadores'     => $admin,
            'editar-computadores'    => $admin,
            'borrar-computadores'    => $admin,
            // Mantenimiento (el celador reporta, administración corrige)
            'ver-mantenimientos'     => $staff,
            'crear-mantenimientos'   => $staff,
            'editar-mantenimientos'  => $admin,
            // Reportes y administración
            'ver-reportes'           => $admin,
            'ver-estadistica'        => $admin,
            'ver-configuracion'      => $admin,
            'ver-usuarios'           => $admin,
            'crear-usuarios'         => $admin,
            'editar-usuarios'        => $admin,
            'borrar-usuarios'        => $admin,
            'ver-roles'              => $admin,
            'crear-roles'            => $admin,
            'editar-roles'           => $admin,
            'borrar-roles'           => $admin,
            'ver-permisos'           => $admin,
            'crear-permisos'         => $admin,
            'editar-permisos'        => $admin,
            'borrar-permisos'        => $admin,
        ];

        foreach ($matriz as $permiso => $roles) {
            Permission::firstOrCreate(['name' => $permiso])->syncRoles($roles);
        }
    }
}