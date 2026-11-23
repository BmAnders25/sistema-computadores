<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\Computador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ComputerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_edit_and_show_computer_views_keep_the_crud_flow_functional(): void
    {
        $user = User::factory()->create(['estado' => 'activo']);
        foreach (['crear-computadores', 'editar-computadores'] as $permissionName) {
            $user->givePermissionTo(Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $classroom = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-CRUD',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('computadores.create'))
            ->assertOk()
            ->assertSee(route('computadores.store'))
            ->assertSee('name="serial"', false)
            ->assertSee('name="aula_id"', false)
            ->assertSee('name="tipo_equipo"', false)
            ->assertSee('name="procesador"', false)
            ->assertSee('name="ram_gb"', false)
            ->assertSee('Guardar Computador');

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->post(route('computadores.store'), [
                'serial' => 'CRUD-001',
                'aula_id' => $classroom->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'HP',
                'modelo' => 'ProDesk 400 G9',
                'procesador' => 'Intel Core i5-12500',
                'ram_gb' => 16,
                'almacenamiento' => '512 GB SSD',
                'sistema_operativo' => 'Windows 11 Pro',
                'estado' => 'disponible',
                'condicion' => 'bueno',
                'observaciones' => 'Equipo en buen estado.',
            ])
            ->assertRedirect(route('computadores.index'));

        $computador = Computador::where('serial', 'CRUD-001')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('computadores.edit', $computador->id))
            ->assertOk()
            ->assertSee(route('computadores.update', $computador->id))
            ->assertSee('value="CRUD-001"', false)
            ->assertSee('value="ProDesk 400 G9"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('Copiar UUID');

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->put(route('computadores.update', $computador->id), [
                'serial' => 'CRUD-001',
                'aula_id' => $classroom->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'HP',
                'modelo' => 'ProDesk actualizado',
                'procesador' => 'Intel Core i5-12500',
                'ram_gb' => 16,
                'almacenamiento' => '512 GB SSD',
                'sistema_operativo' => 'Windows 11 Pro',
                'estado' => 'disponible',
                'condicion' => 'bueno',
                'observaciones' => 'Actualización de prueba.',
            ])
            ->assertRedirect(route('computadores.index'));

        $this->assertDatabaseHas('computadores', [
            'id' => $computador->id,
            'modelo' => 'ProDesk actualizado',
            'observaciones' => 'Actualización de prueba.',
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('computadores.show', $computador->id))
            ->assertOk()
            ->assertSee('HP ProDesk actualizado')
            ->assertSee('CRUD-001')
            ->assertSee('Intel Core i5-12500')
            ->assertSee('LAB-CRUD')
            ->assertSee('Actualización de prueba.')
            ->assertSee('data-copy-computer-uuid', false);
    }
}
