<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_excludes_computers_with_overlapping_computer_or_classroom_reservations(): void
    {
        $user = $this->createUserWithPermissions();
        $selectedBlock = $this->createBlock('10:00', '12:00');
        $overlappingBlock = $this->createBlock('11:00', '13:00');
        $date = today()->addDay()->toDateString();

        $classroom = $this->createClassroom('LAB-1');
        $reservedClassroomComputer = $this->createComputer($classroom, 'ROOM-01');

        $computerRoom = $this->createClassroom('LAB-2');
        $reservedComputer = $this->createComputer($computerRoom, 'BUSY-01');
        $availableComputer = $this->createComputer($computerRoom, 'FREE-01');

        Reserva::create([
            'user_id' => $user->id,
            'aula_id' => $classroom->id,
            'bloque_horario_id' => $overlappingBlock->id,
            'fecha' => $date,
            'estado' => 'pendiente',
            'clave_unica' => Reserva::claveAula($classroom->id, $date, $overlappingBlock->id),
        ]);

        Reserva::create([
            'user_id' => $user->id,
            'computador_id' => $reservedComputer->id,
            'bloque_horario_id' => $overlappingBlock->id,
            'fecha' => $date,
            'estado' => 'pendiente',
            'clave_unica' => Reserva::claveEquipo($reservedComputer->id, $date, $overlappingBlock->id),
        ]);

        Reserva::create([
            'user_id' => $user->id,
            'computador_id' => $availableComputer->id,
            'bloque_horario_id' => $selectedBlock->id,
            'fecha' => $date,
            'estado' => 'cancelada',
            'clave_unica' => null,
        ]);

        $overlappingReservations = Reserva::activas()
            ->whereDate('fecha', $date)
            ->whereHas('bloque', fn ($query) => $query
                ->where('hora_inicio', '<', '12:00:00')
                ->where('hora_fin', '>', '10:00:00'))
            ->get();

        $this->assertCount(2, $overlappingReservations, $overlappingReservations->toJson());

        $fechaInicio = $date . 'T10:00';
        $fechaFin = $date . 'T12:00';

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('disponibilidad.index', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ]));

        $response->assertOk()
            ->assertSee($availableComputer->serial)
            ->assertDontSee($reservedClassroomComputer->serial)
            ->assertDontSee($reservedComputer->serial);

        $reservaUrl = route('reservas.create', [
            'tipo' => 'equipo',
            'computador_id' => $availableComputer->id,
            'fecha' => $date,
            'bloque_horario_id' => $selectedBlock->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        $response->assertSee(e($reservaUrl), false);

        $createResponse = $this->get($reservaUrl);
        $createResponse->assertOk()
            ->assertSee('value="' . $date . '"', false)
            ->assertSee('name="bloque_horario_id" id="bloque_horario_id" value="' . $selectedBlock->id . '"', false)
            ->assertSee('name="computador_id" id="computador_id" value="' . $availableComputer->id . '"', false)
            ->assertSee('data-computer-id="' . $availableComputer->id . '"', false)
            ->assertSee('class="src-booking-resource is-selected"', false)
            ->assertDontSee('<select', false)
            ->assertSee('10:00')
            ->assertSee('12:00');
    }

    public function test_it_requires_the_selected_range_to_match_an_active_reservation_block(): void
    {
        $user = $this->createUserWithPermissions();
        $this->createBlock('10:00', '12:00');

        $date = today()->addDay()->toDateString();
        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('disponibilidad.index', [
                'fecha_inicio' => $date . 'T10:30',
                'fecha_fin' => $date . 'T11:30',
            ]));

        $response->assertOk()
            ->assertSee('El rango debe coincidir con uno de los bloques horarios activos.');
    }

    public function test_reservation_history_uses_the_src_layout_and_shows_computer_and_schedule_details(): void
    {
        $user = $this->createUserWithPermissions();
        $block = $this->createBlock('10:00', '12:00');
        $classroom = $this->createClassroom('LAB-1');
        $computer = $this->createComputer($classroom, 'DEMO-01');
        $date = today()->addDays(2)->toDateString();

        Reserva::create([
            'user_id' => $user->id,
            'computador_id' => $computer->id,
            'bloque_horario_id' => $block->id,
            'fecha' => $date,
            'estado' => 'entregada',
            'clave_unica' => Reserva::claveEquipo($computer->id, $date, $block->id),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('reservas.index'));

        $response->assertOk()
            ->assertSee('Tus reservas, en un solo lugar')
            ->assertSee('Historial de reservas')
            ->assertSee('Dell OptiPlex')
            ->assertSee('Serial: DEMO-01')
            ->assertSee('10:00')
            ->assertSee('12:00')
            ->assertSee('En uso')
            ->assertSee('Nueva reserva');
    }

    public function test_reservation_index_searches_across_pages_and_paginates_results(): void
    {
        $user = $this->createUserWithPermissions();
        $aula = $this->createClassroom('LAB-PAGINACION');
        $computer = $this->createComputer($aula, 'RESERVATION-PAGE-01');
        $defaultBlock = $this->createBlock('10:00', '12:00');
        $targetBlock = BloqueHorario::create([
            'nombre' => 'TARGET-SCHEDULE',
            'hora_inicio' => '13:00',
            'hora_fin' => '15:00',
            'activo' => true,
        ]);

        Reserva::create([
            'user_id' => $user->id,
            'computador_id' => $computer->id,
            'bloque_horario_id' => $targetBlock->id,
            'fecha' => today()->toDateString(),
            'estado' => 'pendiente',
            'clave_unica' => 'reservation-pagination-target',
        ]);

        for ($number = 1; $number <= 12; $number++) {
            Reserva::create([
                'user_id' => $user->id,
                'computador_id' => $computer->id,
                'bloque_horario_id' => $defaultBlock->id,
                'fecha' => today()->addDays($number)->toDateString(),
                'estado' => 'pendiente',
                'clave_unica' => 'reservation-pagination-' . $number,
            ]);
        }

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('reservas.index'))
            ->assertOk()
            ->assertSee('Mostrando del 1 al 12 de 13 registros')
            ->assertDontSee('TARGET-SCHEDULE');

        $this->get(route('reservas.index', ['search' => 'TARGET-SCHEDULE']))
            ->assertOk()
            ->assertSee('TARGET-SCHEDULE')
            ->assertSee('Mostrando del 1 al 1 de 1 registros')
            ->assertSee('name="search" value="TARGET-SCHEDULE"', false);
    }

    private function createUserWithPermissions(): User
    {
        $user = User::create([
            'name' => 'Estudiante de prueba',
            'email' => 'estudiante@example.test',
            'password' => 'password',
            'estado' => 'activo',
        ]);

        $user->givePermissionTo(
            Permission::create(['name' => 'ver-disponibilidad', 'guard_name' => 'web']),
            Permission::create(['name' => 'crear-reservas', 'guard_name' => 'web']),
            Permission::create(['name' => 'ver-reservas', 'guard_name' => 'web'])
        );

        return $user;
    }

    private function createBlock(string $start, string $end): BloqueHorario
    {
        return BloqueHorario::create([
            'nombre' => $start . ' - ' . $end,
            'hora_inicio' => $start,
            'hora_fin' => $end,
            'activo' => true,
        ]);
    }

    private function createClassroom(string $name): Aula
    {
        return Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => $name,
            'capacidad' => 30,
            'estado' => 'activo',
        ]);
    }

    private function createComputer(Aula $classroom, string $serial): Computador
    {
        return Computador::create([
            'serial' => $serial,
            'aula_id' => $classroom->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'procesador' => 'Core i5',
            'ram_gb' => 16,
            'almacenamiento' => '512 GB SSD',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
    }
}
