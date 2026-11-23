<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\Computador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ComputerInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_page_shows_summary_cards_filters_and_preserves_computer_actions(): void
    {
        $user = User::factory()->create(['estado' => 'activo']);
        foreach (['ver-computadores', 'crear-computadores', 'editar-computadores', 'borrar-computadores'] as $permissionName) {
            $user->givePermissionTo(Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $classroom = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-1',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $available = $this->createComputer($classroom, 'INV-001', 'disponible', 'bueno');
        $this->createComputer($classroom, 'INV-002', 'prestado', 'bueno');
        $this->createComputer($classroom, 'INV-003', 'mantenimiento', 'regular');
        $this->createComputer($classroom, 'INV-004', 'inactivo', 'defectuoso');

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('computadores.index'));

        $response->assertOk()
            ->assertSee('Administración de computadores')
            ->assertSee('Total de equipos')
            ->assertSee('Disponibles')
            ->assertSee('En uso / Prestados')
            ->assertSee('En mantenimiento / Dañados')
            ->assertSee('<strong>4</strong>', false)
            ->assertSee('data-computer-status="available"', false)
            ->assertSee('data-computer-status="in-use"', false)
            ->assertSee('data-computer-status="maintenance"', false)
            ->assertSee('data-computer-export="excel"', false)
            ->assertSee('data-computer-export="pdf"', false)
            ->assertSee('data-computer-export="csv"', false)
            ->assertSee('Copiar UUID')
            ->assertSee($available->serial)
            ->assertSee(route('computadores.create'))
            ->assertSee(route('computadores.show', $available->id))
            ->assertSee(route('computadores.edit', $available->id))
            ->assertSee(route('computadores.destroy', $available->id))
            ->assertSee('value="DELETE"', false);
    }

    public function test_inventory_search_covers_all_pages_and_per_page_is_configurable(): void
    {
        $user = User::factory()->create(['estado' => 'activo']);
        $user->givePermissionTo(Permission::create([
            'name' => 'ver-computadores',
            'guard_name' => 'web',
        ]));

        $classroom = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-PAGINACION',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);

        for ($number = 1; $number <= 13; $number++) {
            $this->createComputer(
                $classroom,
                sprintf('PAGE-%03d', $number),
                'disponible',
                'bueno'
            );
        }

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('computadores.index'))
            ->assertOk()
            ->assertSee('Mostrando del 1 al 12 de 13 registros')
            ->assertSee('<strong>13</strong>', false)
            ->assertSee('Siguiente')
            ->assertSee('name="per_page"', false)
            ->assertDontSee('PAGE-013');

        $this->get(route('computadores.index', [
            'estado' => 'disponible',
            'aula_id' => $classroom->id,
        ]))
            ->assertOk()
            ->assertSee('estado=disponible', false)
            ->assertSee('aula_id=' . $classroom->id, false);

        $this->get(route('computadores.index', ['search' => 'PAGE-013']))
            ->assertOk()
            ->assertSee('PAGE-013')
            ->assertSee('Mostrando del 1 al 1 de 1 registros')
            ->assertSee('name="search" value="PAGE-013"', false);

        $this->get(route('computadores.index', ['per_page' => 24, 'aula_id' => $classroom->id, 'estado' => 'disponible']))
            ->assertOk()
            ->assertSee('Mostrando del 1 al 13 de 13 registros')
            ->assertSee('value="24" selected', false);
    }

    private function createComputer(Aula $classroom, string $serial, string $state, string $condition): Computador
    {
        return Computador::create([
            'serial' => $serial,
            'aula_id' => $classroom->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex 7010',
            'procesador' => 'Intel Core i5-13500',
            'ram_gb' => 16,
            'almacenamiento' => '512 GB SSD',
            'sistema_operativo' => 'Windows 11 Pro',
            'estado' => $state,
            'condicion' => $condition,
        ]);
    }
}
