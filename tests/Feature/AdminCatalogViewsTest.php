<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCatalogViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_catalog_views_render_with_the_existing_routes_and_fields(): void
    {
        $admin = User::factory()->create(['estado' => 'activo']);
        $permissionNames = [
            'ver-aulas', 'crear-aulas', 'editar-aulas',
            'ver-bloques-horarios', 'crear-bloques-horarios', 'editar-bloques-horarios',
            'ver-usuarios', 'crear-usuarios', 'editar-usuarios',
            'ver-roles', 'crear-roles', 'editar-roles',
        ];

        foreach ($permissionNames as $permissionName) {
            $admin->givePermissionTo(Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $role = Role::create(['name' => 'Catalogo', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::first());
        $admin->assignRole($role);

        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-CATALOGO',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $bloque = BloqueHorario::create([
            'nombre' => 'Bloque de catalogo',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'activo' => true,
        ]);

        $this->actingAs($admin)->withSession(['two_factor_passed' => true]);

        foreach ([
            route('aulas.index'),
            route('aulas.create'),
            route('aulas.edit', $aula),
            route('aulas.show', $aula),
            route('bloques-horarios.index'),
            route('bloques-horarios.create'),
            route('bloques-horarios.edit', $bloque),
            route('bloques-horarios.show', $bloque),
            route('users.index'),
            route('users.create'),
            route('users.edit', $admin),
            route('users.show', $admin),
            route('roles.index'),
            route('roles.create'),
            route('roles.edit', $role),
            route('roles.show', $role),
            route('permissions.index'),
            route('permissions.create'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $permission = Permission::firstOrCreate(['name' => 'catalogo-test', 'guard_name' => 'web']);
        $this->get(route('permissions.edit', $permission))->assertOk()
            ->assertSee('name="name"', false);
        $this->get(route('permissions.show', $permission))->assertOk()
            ->assertSee($permission->name);

        $this->get(route('bloques-horarios.create'))->assertSee('name="hora_inicio"', false)
            ->assertSee('name="hora_fin"', false)
            ->assertSee('name="activo"', false);
        $this->get(route('users.create'))->assertSee('name="confirm-password"', false)
            ->assertSee('name="roles"', false)
            ->assertSee('name="two_factor_enabled"', false);
        $this->get(route('roles.create'))->assertSee('name="permission[]"', false);
    }

    public function test_block_time_fields_render_without_seconds(): void
    {
        $admin = User::factory()->create(['estado' => 'activo']);
        foreach (['crear-bloques-horarios', 'editar-bloques-horarios'] as $permissionName) {
            $admin->givePermissionTo(Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $bloque = BloqueHorario::create([
            'nombre' => 'Bloque con segundos',
            'hora_inicio' => '07:30:45',
            'hora_fin' => '09:00:30',
            'activo' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('bloques-horarios.edit', $bloque))
            ->assertOk()
            ->assertSee('value="07:30"', false)
            ->assertSee('value="09:00"', false)
            ->assertDontSee('value="07:30:45"', false)
            ->assertDontSee('value="09:00:30"', false);

        $this->actingAs($admin)
            ->withSession([
                'two_factor_passed' => true,
                '_old_input' => [
                    'hora_inicio' => '10:15:40',
                    'hora_fin' => '11:45:55',
                ],
            ])
            ->get(route('bloques-horarios.create'))
            ->assertOk()
            ->assertSee('value="10:15"', false)
            ->assertSee('value="11:45"', false)
            ->assertDontSee('value="10:15:40"', false)
            ->assertDontSee('value="11:45:55"', false);
    }

    public function test_catalog_search_filters_database_results_before_pagination(): void
    {
        $admin = User::factory()->create(['estado' => 'activo']);
        foreach (['ver-aulas', 'ver-bloques-horarios', 'ver-usuarios', 'ver-roles'] as $permissionName) {
            $admin->givePermissionTo(Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        for ($number = 1; $number <= 12; $number++) {
            Aula::create([
                'bloque' => 'Bloque Z',
                'nombre' => sprintf('Aula %02d', $number),
                'capacidad' => 24,
                'estado' => 'activo',
            ]);
            BloqueHorario::create([
                'nombre' => sprintf('Bloque horario %02d', $number),
                'hora_inicio' => sprintf('%02d:00', $number),
                'hora_fin' => sprintf('%02d:30', $number),
                'activo' => true,
            ]);
            User::factory()->create([
                'name' => sprintf('Usuario %02d', $number),
                'estado' => 'activo',
            ]);
            Role::create([
                'name' => sprintf('Rol %02d', $number),
                'guard_name' => 'web',
            ]);
            Permission::create([
                'name' => sprintf('permiso-%02d', $number),
                'guard_name' => 'web',
            ]);
        }

        Aula::create([
            'bloque' => 'Bloque Z',
            'nombre' => 'ZZZ-AULA-OBJETIVO',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        BloqueHorario::create([
            'nombre' => 'ZZZ-BLOQUE-OBJETIVO',
            'hora_inicio' => '23:00',
            'hora_fin' => '23:30',
            'activo' => true,
        ]);
        User::factory()->create([
            'name' => 'ZZZ Usuario Objetivo',
            'email' => 'zzz-usuario@example.test',
            'estado' => 'activo',
        ]);
        Role::create(['name' => 'ZZZ Rol Objetivo', 'guard_name' => 'web']);
        Permission::create(['name' => 'zzzz-permiso-objetivo', 'guard_name' => 'web']);

        $this->actingAs($admin)->withSession(['two_factor_passed' => true]);

        foreach ([
            [route('aulas.index', ['search' => 'ZZZ-AULA-OBJETIVO']), 'ZZZ-AULA-OBJETIVO'],
            [route('bloques-horarios.index', ['search' => 'ZZZ-BLOQUE-OBJETIVO']), 'ZZZ-BLOQUE-OBJETIVO'],
            [route('users.index', ['search' => 'zzz-usuario@example.test']), 'zzz-usuario@example.test'],
            [route('roles.index', ['search' => 'ZZZ Rol Objetivo']), 'ZZZ Rol Objetivo'],
            [route('permissions.index', ['search' => 'zzzz-permiso-objetivo']), 'zzzz-permiso-objetivo'],
        ] as [$url, $expected]) {
            $this->get($url)->assertOk()
                ->assertSee($expected)
                ->assertSee('Mostrando del 1 al 1 de 1 registros');
        }
    }
}
