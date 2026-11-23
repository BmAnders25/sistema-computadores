<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\ConfirmacionReserva;
use App\Notifications\RecordatorioInicioReserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReservationFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_form_lists_active_classrooms(): void
    {
        $user = $this->createUserWithPermissions(['crear-reservas']);
        $classroom = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-ACTIVO',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('reservas.create'));

        $response->assertOk()
            ->assertSee($classroom->nombre);
    }

    public function test_reservation_update_accepts_database_status_values(): void
    {
        $user = $this->createUserWithPermissions(['editar-reservas', 'ver-reservas']);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque de prueba',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'activo' => true,
        ]);
        $reservation = Reserva::create([
            'user_id' => $user->id,
            'bloque_horario_id' => $block->id,
            'fecha' => today()->addDay()->toDateString(),
            'estado' => 'pendiente',
            'clave_unica' => 'test-reservation-' . $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->put(route('reservas.update', $reservation), ['estado' => 'entregada']);

        $response->assertRedirect(route('reservas.index'));
        $this->assertDatabaseHas('reservas', [
            'id' => $reservation->id,
            'estado' => 'entregada',
        ]);
    }

    public function test_new_reservations_are_pending_until_an_approver_confirms_them(): void
    {
        Notification::fake();
        $student = $this->createUserWithPermissions(['crear-reservas']);
        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-RESERVA',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $computador = Computador::create([
            'serial' => 'PENDING-01',
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
        $date = today()->addDay()->toDateString();

        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->post(route('reservas.store'), [
                'tipo' => 'equipo',
                'fecha' => $date,
                'bloque_horario_id' => $block->id,
                'computador_id' => $computador->id,
            ])
            ->assertRedirect(route('reservas.index'));

        $reservation = Reserva::where('user_id', $student->id)->firstOrFail();
        $this->assertSame('pendiente', $reservation->estado);
        Notification::assertSentToTimes($student, ConfirmacionReserva::class);
        $confirmacion = Notification::sent($student, ConfirmacionReserva::class)->first();
        $this->assertSame(['database', 'mail'], $confirmacion->via($student));
        $campana = $confirmacion->toArray($student);
        $this->assertSame(['titulo', 'mensaje', 'url', 'tipo', 'reserva_id'], array_keys($campana));
        $this->assertSame('confirmacion', $campana['tipo']);
        $this->assertSame(route('reservas.index'), $campana['url']);
        $this->assertSame($reservation->id, $campana['reserva_id']);

        $correo = $confirmacion->toMail($student);
        $this->assertSame('Solicitud de reserva recibida', $correo->subject);
        $this->assertSame('Hola ' . $student->name, $correo->greeting);
        $this->assertSame(route('reservas.index'), $correo->actionUrl);

        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->patch(route('reservas.confirmar', $reservation))
            ->assertForbidden();

        $celador = $this->createUserWithRoleAndPermissions('Celador', ['aprobar-reservas']);

        $this->actingAs($celador)
            ->withSession(['two_factor_passed' => true])
            ->from(route('reservas.index'))
            ->patch(route('reservas.confirmar', $reservation))
            ->assertRedirect(route('reservas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reservas', [
            'id' => $reservation->id,
            'estado' => 'confirmada',
        ]);
    }

    public function test_new_reservation_sends_immediate_email_confirmation(): void
    {
        Notification::fake();
        $student = $this->createUserWithPermissions(['crear-reservas']);
        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-CONFIRMACION',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $computador = Computador::create([
            'serial' => 'CONFIRMACION-01',
            'aula_id' => $aula->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque confirmación',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'activo' => true,
        ]);

        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->post(route('reservas.store'), [
                'tipo' => 'equipo',
                'fecha' => today()->addDay()->toDateString(),
                'bloque_horario_id' => $block->id,
                'computador_id' => $computador->id,
            ])
            ->assertRedirect(route('reservas.index'));

        Notification::assertSentToTimes($student, ConfirmacionReserva::class);
        $correo = Notification::sent($student, ConfirmacionReserva::class)->first()->toMail($student);
        $this->assertSame('Solicitud de reserva recibida', $correo->subject);
        $this->assertSame('Hola ' . $student->name, $correo->greeting);
        $this->assertSame(route('reservas.index'), $correo->actionUrl);
    }

    public function test_confirmation_is_saved_to_the_users_notification_feed(): void
    {
        Mail::fake();
        $student = $this->createUserWithPermissions(['crear-reservas']);
        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-CAMPANA-CONFIRMACION',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $computador = Computador::create([
            'serial' => 'CAMPANA-CONFIRMACION-01',
            'aula_id' => $aula->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque para campana',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'activo' => true,
        ]);

        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->post(route('reservas.store'), [
                'tipo' => 'equipo',
                'fecha' => today()->addDay()->toDateString(),
                'bloque_horario_id' => $block->id,
                'computador_id' => $computador->id,
            ])
            ->assertRedirect(route('reservas.index'));

        $notification = $student->notifications()->sole();
        $this->assertSame(ConfirmacionReserva::class, $notification->type);
        $this->assertSame('confirmacion', $notification->data['tipo']);
        $this->assertSame(route('reservas.index'), $notification->data['url']);
        $this->assertSame(
            Reserva::where('user_id', $student->id)->value('id'),
            $notification->data['reserva_id']
        );
    }

    public function test_new_reservation_inside_thirty_minutes_sends_start_reminder_and_marks_it(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-22 08:00:00', config('app.timezone')));

        try {
            $permission = Permission::create([
                'name' => 'crear-reservas',
                'guard_name' => 'web',
            ]);
            $soonStudent = User::factory()->create(['estado' => 'activo']);
            $laterStudent = User::factory()->create(['estado' => 'activo']);
            $soonStudent->givePermissionTo($permission);
            $laterStudent->givePermissionTo($permission);

            $aula = Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-RECORDATORIO-INMEDIATO',
                'capacidad' => 24,
                'estado' => 'activo',
            ]);
            $soonComputer = Computador::create([
                'serial' => 'INMEDIATO-01',
                'aula_id' => $aula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
            $laterComputer = Computador::create([
                'serial' => 'INMEDIATO-02',
                'aula_id' => $aula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
            $soonBlock = BloqueHorario::create([
                'nombre' => 'Inicio en 20 minutos',
                'hora_inicio' => '08:20',
                'hora_fin' => '09:00',
                'activo' => true,
            ]);
            $laterBlock = BloqueHorario::create([
                'nombre' => 'Inicio en tres horas',
                'hora_inicio' => '11:00',
                'hora_fin' => '12:00',
                'activo' => true,
            ]);

            foreach ([
                [$soonStudent, $soonComputer, $soonBlock],
                [$laterStudent, $laterComputer, $laterBlock],
            ] as [$student, $computer, $block]) {
                $this->actingAs($student)
                    ->withSession(['two_factor_passed' => true])
                    ->post(route('reservas.store'), [
                        'tipo' => 'equipo',
                        'fecha' => '2026-11-22',
                        'bloque_horario_id' => $block->id,
                        'computador_id' => $computer->id,
                    ])
                    ->assertRedirect(route('reservas.index'));
            }

            $soonReservation = Reserva::where('user_id', $soonStudent->id)->firstOrFail();
            $laterReservation = Reserva::where('user_id', $laterStudent->id)->firstOrFail();

            Notification::assertSentToTimes($soonStudent, ConfirmacionReserva::class);
            Notification::assertSentToTimes($soonStudent, RecordatorioInicioReserva::class);
            Notification::assertSentToTimes($laterStudent, ConfirmacionReserva::class);
            Notification::assertNotSentTo($laterStudent, RecordatorioInicioReserva::class);
            $this->assertNotNull($soonReservation->recordatorio_inicio_enviado_at);
            $this->assertNull($laterReservation->recordatorio_inicio_enviado_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_celador_can_reject_a_pending_reservation_via_the_existing_cancel_route(): void
    {
        $owner = $this->createUserWithPermissions(['crear-reservas']);
        $block = BloqueHorario::create([
            'nombre' => '09:00 - 11:00',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'activo' => true,
        ]);
        $reservation = Reserva::create([
            'user_id' => $owner->id,
            'bloque_horario_id' => $block->id,
            'fecha' => today()->addDay()->toDateString(),
            'estado' => 'pendiente',
            'clave_unica' => 'pending-rejection-' . $owner->id,
        ]);
        $celador = $this->createUserWithRoleAndPermissions('Celador', [
            'aprobar-reservas',
            'cancelar-reservas',
        ]);

        $this->actingAs($celador)
            ->withSession(['two_factor_passed' => true])
            ->delete(route('reservas.destroy', $reservation))
            ->assertRedirect(route('reservas.index'));

        $this->assertDatabaseHas('reservas', [
            'id' => $reservation->id,
            'estado' => 'cancelada',
            'clave_unica' => null,
        ]);
    }

    public function test_aula_form_can_save_the_maintenance_state(): void
    {
        $user = $this->createUserWithPermissions(['crear-aulas']);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->post(route('aulas.store'), [
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-MANTENIMIENTO',
                'capacidad' => 24,
                'estado' => 'mantenimiento',
            ]);

        $response->assertRedirect(route('aulas.index'));
        $this->assertDatabaseHas('aulas', [
            'nombre' => 'LAB-MANTENIMIENTO',
            'estado' => 'mantenimiento',
        ]);
    }

    private function createUserWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create(['estado' => 'activo']);

        foreach ($permissionNames as $permissionName) {
            $user->givePermissionTo(Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        return $user;
    }

    private function createUserWithRoleAndPermissions(string $roleName, array $permissionNames): User
    {
        $user = User::factory()->create(['estado' => 'activo']);
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        foreach ($permissionNames as $permissionName) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        return $user;
    }
}
