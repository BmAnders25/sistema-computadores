<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\RecordatorioInicioReserva;
use App\Notifications\ReservaConfirmada;
use App\Notifications\ReservaNoPresentada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReservationFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_status_accessors_use_display_labels_and_neutral_legacy_color(): void
    {
        $esperados = [
            'pendiente' => ['Reservada', 'pending'],
            'entregada' => ['En uso', 'in-use'],
            'devuelta' => ['Devuelta', 'returned'],
            'cancelada' => ['Cancelada', 'cancelled'],
            'no_show' => ['No se presentó', 'cancelled'],
            'confirmada' => ['Confirmada (legado)', 'muted'],
        ];

        foreach ($esperados as $estado => [$etiqueta, $color]) {
            $reserva = new Reserva(['estado' => $estado]);
            $this->assertSame($etiqueta, $reserva->estado_etiqueta);
            $this->assertSame($color, $reserva->estado_color);
        }
    }

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

    public function test_manual_status_action_is_offered_only_for_classroom_reservations(): void
    {
        $user = $this->createUserWithPermissions(['editar-reservas', 'ver-reservas']);
        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-CAMBIO-ESTADO',
            'capacidad' => 24,
            'estado' => 'activo',
        ]);
        $computer = Computador::create([
            'serial' => 'CAMBIO-ESTADO-01',
            'aula_id' => $aula->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque para cambio de estado',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'activo' => true,
        ]);
        $fecha = today()->addDay()->toDateString();
        $classroomReservation = Reserva::create([
            'user_id' => $user->id,
            'aula_id' => $aula->id,
            'bloque_horario_id' => $block->id,
            'fecha' => $fecha,
            'estado' => 'entregada',
            'clave_unica' => Reserva::claveAula($aula->id, $fecha, $block->id),
        ]);
        $computerReservation = Reserva::create([
            'user_id' => $user->id,
            'computador_id' => $computer->id,
            'bloque_horario_id' => $block->id,
            'fecha' => $fecha,
            'estado' => 'entregada',
            'clave_unica' => Reserva::claveEquipo($computer->id, $fecha, $block->id),
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->get(route('reservas.index'))
            ->assertOk()
            ->assertSee(route('reservas.edit', $classroomReservation), false)
            ->assertDontSee(route('reservas.edit', $computerReservation), false);
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
            'clave_unica' => 'test-reservation-'.$user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->put(route('reservas.update', $reservation), ['estado' => 'entregada']);

        $response->assertRedirect(route('reservas.index'));
        $this->assertDatabaseHas('reservas', [
            'id' => $reservation->id,
            'estado' => 'entregada',
        ]);
        $this->assertNotNull($reservation->fresh()->entregado_at);
    }

    public function test_new_reservations_start_pending_and_mysql_approvers_can_confirm_them(): void
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
        Notification::assertSentTo($student, ReservaConfirmada::class);
        $confirmacion = Notification::sent($student, ReservaConfirmada::class)->first();
        $this->assertSame(['database', 'mail'], $confirmacion->via($student));
        $campana = $confirmacion->toArray($student);
        $this->assertSame(['titulo', 'mensaje', 'url', 'tipo', 'reserva_id'], array_keys($campana));
        $this->assertSame('confirmacion', $campana['tipo']);
        $this->assertSame(route('reservas.index'), $campana['url']);
        $this->assertSame($reservation->id, $campana['reserva_id']);
        $this->assertStringContainsString('OptiPlex', $campana['mensaje']);
        $this->assertStringContainsString($block->nombre, $campana['mensaje']);

        $correo = $confirmacion->toMail($student);
        $this->assertSame('Reserva registrada', $correo->subject);
        $this->assertSame('Hola '.$student->name, $correo->greeting);
        $this->assertSame(route('reservas.index'), $correo->actionUrl);

        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->patch(route('reservas.confirmar', $reservation))
            ->assertForbidden();

        $celador = $this->createUserWithRoleAndPermissions('Celador', ['aprobar-reservas']);

        if (DB::getDriverName() === 'mysql') {
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
        } else {
            $this->assertDatabaseHas('reservas', [
                'id' => $reservation->id,
                'estado' => 'pendiente',
            ]);
        }
    }

    public function test_new_reservation_sends_immediate_reservation_confirmation(): void
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

        Notification::assertSentTo($student, ReservaConfirmada::class);
        $correo = Notification::sent($student, ReservaConfirmada::class)->first()->toMail($student);
        $this->assertSame('Reserva registrada', $correo->subject);
        $this->assertSame('Hola '.$student->name, $correo->greeting);
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
        $this->assertSame(ReservaConfirmada::class, $notification->type);
        $this->assertSame('confirmacion', $notification->data['tipo']);
        $this->assertSame(route('reservas.index'), $notification->data['url']);
        $this->assertSame(
            Reserva::where('user_id', $student->id)->value('id'),
            $notification->data['reserva_id']
        );

        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('fa-calendar-check');
    }

    public function test_mail_rate_limit_does_not_block_bell_notifications_or_send_a_second_immediate_email(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:00:00', config('app.timezone')));
        $this->mock(MailChannel::class, function ($mailChannel) {
            $mailChannel->shouldReceive('send')
                ->once()
                ->andThrow(new \RuntimeException('Mail provider rate limit'));
        });

        try {
            $student = $this->createUserWithPermissions(['crear-reservas']);
            $aula = Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-RATE-LIMIT',
                'capacidad' => 24,
                'estado' => 'activo',
            ]);
            $computador = Computador::create([
                'serial' => 'RATE-LIMIT-01',
                'aula_id' => $aula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
            $block = BloqueHorario::create([
                'nombre' => 'Inicio en 20 minutos',
                'hora_inicio' => '08:20',
                'hora_fin' => '09:00',
                'activo' => true,
            ]);

            $this->actingAs($student)
                ->withSession(['two_factor_passed' => true])
                ->post(route('reservas.store'), [
                    'tipo' => 'equipo',
                    'fecha' => '2026-11-23',
                    'bloque_horario_id' => $block->id,
                    'computador_id' => $computador->id,
                ])
                ->assertRedirect(route('reservas.index'))
                ->assertSessionHas('warning', 'La reserva se guardó y la campana se actualizó, pero no se pudo enviar el correo.');

            $reservation = Reserva::where('user_id', $student->id)->firstOrFail();
            $this->assertNotNull($reservation->recordatorio_inicio_enviado_at);
            $this->assertCount(2, $student->notifications);
            $this->assertEqualsCanonicalizing(
                [ReservaConfirmada::class, RecordatorioInicioReserva::class],
                $student->notifications->pluck('type')->all()
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_changing_reservation_to_no_show_releases_its_key_and_notifies_celadores_once(): void
    {
        $admin = $this->createUserWithPermissions(['editar-reservas']);
        $celador = $this->createUserWithRoleAndPermissions('Celador', []);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque no show',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'activo' => true,
        ]);
        $reservation = Reserva::create([
            'user_id' => $admin->id,
            'bloque_horario_id' => $block->id,
            'fecha' => today()->addDay()->toDateString(),
            'estado' => 'pendiente',
            'clave_unica' => 'no-show-reservation-'.$admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->put(route('reservas.update', $reservation), ['estado' => 'no_show'])
            ->assertRedirect(route('reservas.index'));

        $this->assertDatabaseHas('reservas', [
            'id' => $reservation->id,
            'estado' => 'no_show',
            'clave_unica' => null,
        ]);
        $this->assertSame(
            1,
            $celador->notifications()->where('type', ReservaNoPresentada::class)->count()
        );
        $this->assertSame(
            0,
            $admin->notifications()->where('type', ReservaNoPresentada::class)->count()
        );

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->put(route('reservas.update', $reservation), ['estado' => 'no_show'])
            ->assertRedirect(route('reservas.index'));

        $this->assertSame(
            1,
            $celador->notifications()->where('type', ReservaNoPresentada::class)->count()
        );
    }

    public function test_no_show_action_is_only_shown_for_pending_reservations_and_releases_the_slot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 09:00:00', config('app.timezone')));

        try {
            $admin = $this->createUserWithPermissions(['registrar-entregas', 'ver-reservas']);
            $admin->assignRole(Role::firstOrCreate([
                'name' => 'Administracion',
                'guard_name' => 'web',
            ]));
            $celador = $this->createUserWithRoleAndPermissions('Celador', []);
            $owner = User::factory()->create(['estado' => 'activo']);
            $otherOwner = User::factory()->create(['estado' => 'activo']);
            $aula = Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-NO-SHOW-ACTION',
                'capacidad' => 24,
                'estado' => 'activo',
            ]);
            $block = BloqueHorario::create([
                'nombre' => 'Bloque no show futuro',
                'hora_inicio' => '10:00',
                'hora_fin' => '12:00',
                'activo' => true,
            ]);
            $fecha = today()->addDay()->toDateString();
            $computer = Computador::create([
                'serial' => 'NO-SHOW-ACTION-01',
                'aula_id' => $aula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
            $pendingReservation = Reserva::create([
                'user_id' => $owner->id,
                'computador_id' => $computer->id,
                'bloque_horario_id' => $block->id,
                'fecha' => $fecha,
                'estado' => 'pendiente',
                'clave_unica' => Reserva::claveEquipo($computer->id, $fecha, $block->id),
            ]);
            $deliveredComputer = Computador::create([
                'serial' => 'NO-SHOW-ACTION-02',
                'aula_id' => $aula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'prestado',
                'condicion' => 'bueno',
            ]);
            $deliveredDate = today()->addDays(2)->toDateString();
            $deliveredReservation = Reserva::create([
                'user_id' => $otherOwner->id,
                'computador_id' => $deliveredComputer->id,
                'bloque_horario_id' => $block->id,
                'fecha' => $deliveredDate,
                'estado' => 'entregada',
                'clave_unica' => Reserva::claveEquipo($deliveredComputer->id, $deliveredDate, $block->id),
            ]);

            $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reservas.index'))
                ->assertOk()
                ->assertSee(route('reservas.no-presentada', $pendingReservation), false)
                ->assertDontSee(route('reservas.no-presentada', $deliveredReservation), false);

            $this->post(route('reservas.no-presentada', $pendingReservation))
                ->assertRedirect(route('reservas.index'))
                ->assertSessionHas('success');

            $this->assertDatabaseHas('reservas', [
                'id' => $pendingReservation->id,
                'estado' => 'no_show',
                'clave_unica' => null,
            ]);
            $this->assertSame(
                1,
                $celador->notifications()->where('type', ReservaNoPresentada::class)->count()
            );

            $rebooked = app(\App\Services\ReservaService::class)
                ->reservarEquipo($owner, $computer->id, $fecha, $block->id);

            $this->assertSame('pendiente', $rebooked->estado);
            $this->assertSame(
                Reserva::claveEquipo($computer->id, $fecha, $block->id),
                $rebooked->clave_unica
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_celador_can_mark_reservation_as_no_show_only_after_block_starts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 09:00:00', config('app.timezone')));

        try {
            $celador = $this->createUserWithRoleAndPermissions('Celador', ['registrar-entregas', 'ver-reservas']);
            $owner = User::factory()->create(['estado' => 'activo']);
            $aula = Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-NO-SHOW-CELADOR',
                'capacidad' => 24,
                'estado' => 'activo',
            ]);
            $block = BloqueHorario::create([
                'nombre' => 'Bloque no iniciado',
                'hora_inicio' => '10:00',
                'hora_fin' => '12:00',
                'activo' => true,
            ]);
            $computer = Computador::create([
                'serial' => 'NO-SHOW-CELADOR-01',
                'aula_id' => $aula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
            $fecha = today()->toDateString();
            $reservation = Reserva::create([
                'user_id' => $owner->id,
                'computador_id' => $computer->id,
                'bloque_horario_id' => $block->id,
                'fecha' => $fecha,
                'estado' => 'pendiente',
                'clave_unica' => Reserva::claveEquipo($computer->id, $fecha, $block->id),
            ]);

            $this->actingAs($celador)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reservas.index'))
                ->assertOk()
                ->assertDontSee(route('reservas.no-presentada', $reservation), false);

            $this->from(route('reservas.index'))
                ->post(route('reservas.no-presentada', $reservation))
                ->assertRedirect(route('reservas.index'))
                ->assertSessionHasErrors([
                    'reserva' => 'Solo puedes marcar una reserva como no presentada después del inicio de su bloque horario.',
                ]);
            $this->assertSame('pendiente', $reservation->fresh()->estado);

            Carbon::setTestNow(Carbon::parse('2026-11-23 10:01:00', config('app.timezone')));

            $this->get(route('reservas.index'))
                ->assertOk()
                ->assertSee(route('reservas.no-presentada', $reservation), false);

            $this->post(route('reservas.no-presentada', $reservation))
                ->assertRedirect(route('reservas.index'))
                ->assertSessionHas('success');

            $this->assertDatabaseHas('reservas', [
                'id' => $reservation->id,
                'estado' => 'no_show',
                'clave_unica' => null,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_closed_reservation_states_release_the_slot_for_reserva_service(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:00:00', config('app.timezone')));
        Notification::fake();

        try {
            $aula = Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-LIBERAR-RESERVA',
                'capacidad' => 24,
                'estado' => 'activo',
            ]);
            $block = BloqueHorario::create([
                'nombre' => 'Bloque para liberar',
                'hora_inicio' => '10:00',
                'hora_fin' => '12:00',
                'activo' => true,
            ]);
            $fecha = today()->addDay()->toDateString();
            $editPermission = Permission::create([
                'name' => 'editar-reservas',
                'guard_name' => 'web',
            ]);

            foreach (['devuelta', 'cancelada', 'no_show'] as $indice => $estado) {
                $user = User::factory()->create(['estado' => 'activo']);
                $user->givePermissionTo($editPermission);
                $computer = Computador::create([
                    'serial' => 'LIBERAR-'.strtoupper($estado),
                    'aula_id' => $aula->id,
                    'tipo_equipo' => 'escritorio',
                    'marca' => 'Dell',
                    'modelo' => 'OptiPlex',
                    'estado' => 'disponible',
                    'condicion' => 'bueno',
                ]);
                $clave = Reserva::claveEquipo($computer->id, $fecha, $block->id);
                $reservation = Reserva::create([
                    'user_id' => $user->id,
                    'computador_id' => $computer->id,
                    'bloque_horario_id' => $block->id,
                    'fecha' => $fecha,
                    'estado' => $estado === 'devuelta' ? 'entregada' : 'pendiente',
                    'entregado_at' => $estado === 'devuelta' ? now()->subHour() : null,
                    'clave_unica' => $clave,
                ]);

                $this->actingAs($user)
                    ->withSession(['two_factor_passed' => true])
                    ->put(route('reservas.update', $reservation), ['estado' => $estado])
                    ->assertRedirect(route('reservas.index'));

                $reservation = $reservation->fresh();
                $this->assertSame($estado, $reservation->estado);
                $this->assertNull($reservation->clave_unica);

                if ($estado === 'devuelta') {
                    $this->assertNotNull($reservation->entregado_at);
                    $this->assertNotNull($reservation->devuelto_at);
                }

                $nuevaReserva = app(\App\Services\ReservaService::class)
                    ->reservarEquipo($user, $computer->id, $fecha, $block->id);

                $this->assertSame('pendiente', $nuevaReserva->estado);
                $this->assertSame($clave, $nuevaReserva->clave_unica);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_closed_reservations_cannot_be_reactivated_from_the_edit_form(): void
    {
        $user = $this->createUserWithPermissions(['editar-reservas']);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque cerrado',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'activo' => true,
        ]);

        foreach (['devuelta', 'cancelada', 'no_show'] as $indice => $estado) {
            foreach (['pendiente', 'entregada'] as $nuevoEstado) {
                $reservation = Reserva::create([
                    'user_id' => $user->id,
                    'bloque_horario_id' => $block->id,
                    'fecha' => today()->addDays($indice + 1)->toDateString(),
                    'estado' => $estado,
                    'clave_unica' => null,
                ]);

                $this->actingAs($user)
                    ->withSession(['two_factor_passed' => true])
                    ->put(route('reservas.update', $reservation), ['estado' => $nuevoEstado])
                    ->assertSessionHasErrors([
                        'estado' => 'No es posible reactivar una reserva cerrada. Debes crear una nueva reserva.',
                    ]);

                $this->assertSame($estado, $reservation->fresh()->estado);
                $this->assertNull($reservation->fresh()->clave_unica);
            }
        }
    }

    public function test_reservation_cannot_be_returned_before_it_is_delivered(): void
    {
        $user = $this->createUserWithPermissions(['editar-reservas']);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque pendiente',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'activo' => true,
        ]);
        $reservation = Reserva::create([
            'user_id' => $user->id,
            'bloque_horario_id' => $block->id,
            'fecha' => today()->addDay()->toDateString(),
            'estado' => 'pendiente',
            'clave_unica' => 'pending-return-'.$user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->put(route('reservas.update', $reservation), ['estado' => 'devuelta'])
            ->assertSessionHasErrors([
                'estado' => 'Solo se puede marcar como devuelta una reserva que esté en uso (entregada).',
            ]);

        $this->assertSame('pendiente', $reservation->fresh()->estado);
        $this->assertSame('pending-return-'.$user->id, $reservation->fresh()->clave_unica);
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

            Notification::assertSentTo($soonStudent, ReservaConfirmada::class);
            Notification::assertSentTo($soonStudent, RecordatorioInicioReserva::class);
            Notification::assertSentTo($laterStudent, ReservaConfirmada::class);
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
            'clave_unica' => 'pending-rejection-'.$owner->id,
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

    public function test_aula_form_can_save_the_inactive_state(): void
    {
        $user = $this->createUserWithPermissions(['crear-aulas']);

        $response = $this->actingAs($user)
            ->withSession(['two_factor_passed' => true])
            ->post(route('aulas.store'), [
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-MANTENIMIENTO',
                'capacidad' => 24,
                'estado' => 'inactivo',
            ]);

        $response->assertRedirect(route('aulas.index'));
        $this->assertDatabaseHas('aulas', [
            'nombre' => 'LAB-MANTENIMIENTO',
            'estado' => 'inactivo',
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
