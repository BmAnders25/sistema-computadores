<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EntregaTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_blocks_availability_until_the_computer_is_returned(): void
    {
        $staff = $this->createUserWithPermissions([
            'aprobar-reservas',
            'cancelar-reservas',
            'ver-entregas',
            'registrar-entregas',
            'registrar-devoluciones',
            'ver-disponibilidad',
        ]);
        $staff->assignRole(Role::firstOrCreate([
            'name' => 'Celador',
            'guard_name' => 'web',
        ]));
        $student = $this->createUserWithPermissions(['crear-reservas']);

        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-1',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $computador = Computador::create([
            'serial' => 'DELIVERY-01',
            'aula_id' => $aula->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
        $bloque = BloqueHorario::create([
            'nombre' => '10:00 - 12:00',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'activo' => true,
        ]);
        $reserva = Reserva::create([
            'user_id' => $student->id,
            'computador_id' => $computador->id,
            'bloque_horario_id' => $bloque->id,
            'fecha' => today()->subDay()->toDateString(),
            'estado' => 'pendiente',
            'clave_unica' => Reserva::claveEquipo($computador->id, today()->subDay()->toDateString(), $bloque->id),
        ]);

        $this->actingAs($staff)
            ->withSession(['two_factor_passed' => true])
            ->from(route('reservas.index'))
            ->patch(route('reservas.confirmar', $reserva))
            ->assertRedirect(route('reservas.index'))
            ->assertSessionHas('success');

        $reserva->refresh();
        $this->assertSame('confirmada', $reserva->estado);

        $this->actingAs($staff)
            ->withSession(['two_factor_passed' => true])
            ->get(route('entregas.index'))
            ->assertOk()
            ->assertSee($student->email)
            ->assertSee($computador->serial)
            ->assertSee('Confirmada');

        $this->from(route('entregas.index'))
            ->post(route('entregas.entregar', $reserva))
            ->assertRedirect(route('entregas.index'))
            ->assertSessionHas('success');

        $reserva->refresh();
        $computador->refresh();
        $this->assertSame('entregada', $reserva->estado);
        $this->assertNotNull($reserva->entregado_at);
        $this->assertSame('prestado', $computador->estado);

        $fechaDisponibilidad = today()->addDays(2)->toDateString();
        $rango = [
            'fecha_inicio' => $fechaDisponibilidad . 'T10:00',
            'fecha_fin' => $fechaDisponibilidad . 'T12:00',
        ];

        $this->get(route('disponibilidad.index', $rango))
            ->assertOk()
            ->assertDontSee($computador->serial);

        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->from(route('reservas.create'))
            ->post(route('reservas.store'), [
                'tipo' => 'equipo',
                'fecha' => $fechaDisponibilidad,
                'bloque_horario_id' => $bloque->id,
                'computador_id' => $computador->id,
            ])
            ->assertRedirect(route('reservas.create'))
            ->assertSessionHasErrors('reserva');

        $this->actingAs($staff)
            ->withSession(['two_factor_passed' => true])
            ->from(route('entregas.index'))
            ->post(route('entregas.devolver', $reserva))
            ->assertRedirect(route('entregas.index'))
            ->assertSessionHas('success');

        $reserva->refresh();
        $computador->refresh();
        $this->assertSame('devuelta', $reserva->estado);
        $this->assertNotNull($reserva->devuelto_at);
        $this->assertNull($reserva->clave_unica);
        $this->assertSame('disponible', $computador->estado);

        $this->get(route('disponibilidad.index', $rango))
            ->assertOk()
            ->assertSee($computador->serial);
    }

    public function test_delivery_index_searches_across_pages_and_keeps_global_counts(): void
    {
        $staff = $this->createUserWithPermissions(['ver-entregas']);
        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-ENTREGAS-PAGINACION',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $computer = Computador::create([
            'serial' => 'DELIVERY-PAGINATION-01',
            'aula_id' => $aula->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
        $block = BloqueHorario::create([
            'nombre' => '09:00 - 11:00',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'activo' => true,
        ]);
        $targetUser = User::factory()->create([
            'name' => 'Target Delivery Student',
            'email' => 'target-delivery@example.test',
            'estado' => 'activo',
        ]);

        Reserva::create([
            'user_id' => $targetUser->id,
            'computador_id' => $computer->id,
            'bloque_horario_id' => $block->id,
            'fecha' => today()->toDateString(),
            'estado' => 'confirmada',
            'clave_unica' => 'delivery-pagination-target',
        ]);

        for ($number = 1; $number <= 12; $number++) {
            $user = User::factory()->create(['estado' => 'activo']);
            Reserva::create([
                'user_id' => $user->id,
                'computador_id' => $computer->id,
                'bloque_horario_id' => $block->id,
                'fecha' => today()->addDays($number)->toDateString(),
                'estado' => 'confirmada',
                'clave_unica' => 'delivery-pagination-' . $number,
            ]);
        }

        $this->actingAs($staff)
            ->withSession(['two_factor_passed' => true])
            ->get(route('entregas.index'))
            ->assertOk()
            ->assertSee('Mostrando del 1 al 12 de 13 registros')
            ->assertSee('Por entregar')
            ->assertDontSee($targetUser->email);

        $this->get(route('entregas.index', ['search' => 'target-delivery@example.test']))
            ->assertOk()
            ->assertSee($targetUser->email)
            ->assertSee('Mostrando del 1 al 1 de 1 registros')
            ->assertSee('name="search" value="target-delivery@example.test"', false);
    }

    private function createUserWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create(['estado' => 'activo']);

        foreach ($permissionNames as $permissionName) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        return $user;
    }
}
