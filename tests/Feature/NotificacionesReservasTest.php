<?php

namespace Tests\Feature;

use App\Models\BloqueHorario;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\RecordatorioFinReserva;
use App\Notifications\RecordatorioInicioReserva;
use Database\Seeders\NotificacionesPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificacionesReservasTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_start_reminders_are_sent_only_inside_the_window_to_active_users_once(): void
    {
        Notification::fake();
        $ahora = Carbon::parse('2026-11-22 08:00:00', config('app.timezone'));
        Carbon::setTestNow($ahora);

        $activo = $this->crearUsuario('activo');
        $fueraDeVentana = $this->crearUsuario('activo');
        $inactivo = $this->crearUsuario('inactivo');
        $reservaInicio = $this->crearReserva($activo, 'pendiente', '08:20:00', '09:00:00');
        $this->crearReserva($fueraDeVentana, 'pendiente', '11:00:00', '12:00:00');
        $this->crearReserva($inactivo, 'pendiente', '08:20:00', '09:00:00');

        $this->artisan('reservas:recordatorios')
            ->expectsOutput('Inicio: 1 enviados, Fin: 0 enviados')
            ->assertExitCode(0);

        $this->artisan('reservas:recordatorios')
            ->expectsOutput('Inicio: 0 enviados, Fin: 0 enviados')
            ->assertExitCode(0);

        Notification::assertSentTo($activo, RecordatorioInicioReserva::class, 1);
        Notification::assertNotSentTo($fueraDeVentana, RecordatorioInicioReserva::class);
        Notification::assertNotSentTo($inactivo, RecordatorioInicioReserva::class);

        $datos = (new RecordatorioInicioReserva($reservaInicio->load('bloque')))->toArray($activo);
        $this->assertSame(['titulo', 'mensaje', 'url', 'tipo', 'reserva_id'], array_keys($datos));
        $this->assertSame('inicio', $datos['tipo']);
        $this->assertSame($reservaInicio->id, $datos['reserva_id']);
        $this->assertStringContainsString('comienza en unos minutos', $datos['mensaje']);
    }

    public function test_finish_reminder_is_sent_only_for_an_active_loan_ending_within_fifteen_minutes(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-22 08:00:00', config('app.timezone')));

        $entregada = $this->crearUsuario('activo');
        $devuelta = $this->crearUsuario('activo');
        $cancelada = $this->crearUsuario('activo');
        $this->crearReserva($entregada, 'entregada', '07:00:00', '08:10:00');
        $this->crearReserva($devuelta, 'devuelta', '07:00:00', '08:10:00');
        $this->crearReserva($cancelada, 'cancelada', '07:00:00', '08:10:00');

        $this->artisan('reservas:recordatorios')
            ->expectsOutput('Inicio: 0 enviados, Fin: 1 enviados')
            ->assertExitCode(0);

        Notification::assertSentTo($entregada, RecordatorioFinReserva::class, 1);
        Notification::assertNotSentTo($devuelta, RecordatorioFinReserva::class);
        Notification::assertNotSentTo($cancelada, RecordatorioFinReserva::class);
    }

    public function test_users_can_only_read_their_own_notifications_and_can_clear_all_unread(): void
    {
        $propietario = $this->crearUsuario('activo');
        $otroUsuario = $this->crearUsuario('activo');
        $propia = $this->crearNotificacion($propietario);
        $ajena = $this->crearNotificacion($otroUsuario);

        $this->actingAs($propietario)
            ->withSession(['two_factor_passed' => true])
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Historial de notificaciones')
            ->assertSee('src-notificaciones')
            ->assertSee('fas fa-bell')
            ->assertSee('aria-label="Notificaciones"', false)
            ->assertDontSee('class="fas fa-bell text-white"', false);

        $this->actingAs($propietario)
            ->withSession(['two_factor_passed' => true])
            ->post(route('notificaciones.leer', $propia->id))
            ->assertRedirect(route('reservas.index'));

        $this->actingAs($propietario)
            ->withSession(['two_factor_passed' => true])
            ->post(route('notificaciones.leer', $ajena->id))
            ->assertNotFound();

        $this->assertNotNull($propietario->notifications()->findOrFail($propia->id)->read_at);
        $this->assertNull($otroUsuario->notifications()->findOrFail($ajena->id)->read_at);

        $this->crearNotificacion($propietario);
        $this->actingAs($propietario)
            ->withSession(['two_factor_passed' => true])
            ->post(route('notificaciones.leer-todas'))
            ->assertRedirect(route('notificaciones.index'));

        $this->actingAs($propietario)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('notificaciones.resumen'))
            ->assertOk()
            ->assertJsonStructure(['label', 'label_color', 'dropdown'])
            ->assertJsonMissingPath('icon_color')
            ->assertJsonPath('label', 0);
    }

    public function test_notification_test_seeder_is_repeatable_and_creates_expected_slots(): void
    {
        $this->app['env'] = 'local';
        Notification::fake();
        $ahora = Carbon::parse('2026-11-22 16:00:00', config('app.timezone'));
        Carbon::setTestNow($ahora);

        $this->seed(NotificacionesPruebaSeeder::class);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('reservas', 4);

        $correos = [
            'prueba.inicio@unipamplona.edu.co',
            'prueba.fin@unipamplona.edu.co',
            'prueba.lejana@unipamplona.edu.co',
            'prueba.devuelta@unipamplona.edu.co',
        ];
        $usuarios = User::query()->whereIn('email', $correos)->get()->keyBy('email');
        $this->assertCount(4, $usuarios);

        foreach ($usuarios as $usuario) {
            $this->assertSame('activo', $usuario->estado);
            $this->assertTrue($usuario->hasRole('Estudiante'));
            $this->assertSame(1, Reserva::query()->where('user_id', $usuario->id)->count());
            $this->assertTrue(password_verify('password', $usuario->password));
        }

        $esperados = [
            'prueba.inicio@unipamplona.edu.co' => ['pendiente', 1200, 'hora_inicio'],
            'prueba.fin@unipamplona.edu.co' => ['entregada', 600, 'hora_fin'],
            'prueba.lejana@unipamplona.edu.co' => ['pendiente', 10800, 'hora_inicio'],
            'prueba.devuelta@unipamplona.edu.co' => ['devuelta', 600, 'hora_fin'],
        ];

        foreach ($esperados as $correo => [$estado, $segundos, $horaClave]) {
            $reserva = Reserva::query()
                ->where('user_id', $usuarios[$correo]->id)
                ->with('bloque')
                ->firstOrFail();
            $this->assertSame($estado, $reserva->estado);

            $momento = Carbon::parse(
                $reserva->fecha->toDateString() . ' ' . $reserva->bloque->{$horaClave},
                config('app.timezone')
            );
            $this->assertEquals($segundos, $ahora->diffInSeconds($momento, false));
        }

        $reservaInicio = Reserva::query()->where('user_id', $usuarios['prueba.inicio@unipamplona.edu.co']->id)->firstOrFail();
        $reservaInicio->newQuery()->whereKey($reservaInicio->getKey())->update([
            'recordatorio_inicio_enviado_at' => $ahora,
            'recordatorio_fin_enviado_at' => $ahora,
        ]);

        $this->seed(NotificacionesPruebaSeeder::class);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('reservas', 4);
        $this->assertNull($reservaInicio->fresh()->recordatorio_inicio_enviado_at);
        $this->assertNull($reservaInicio->fresh()->recordatorio_fin_enviado_at);
    }

    private function crearUsuario(string $estado): User
    {
        return User::factory()->create([
            'estado' => $estado,
            'two_factor_enabled' => false,
        ]);
    }

    private function crearReserva(User $usuario, string $estado, string $inicio, string $fin): Reserva
    {
        $bloque = BloqueHorario::query()->create([
            'nombre' => 'Bloque de prueba',
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'activo' => true,
        ]);

        return Reserva::query()->create([
            'user_id' => $usuario->id,
            'bloque_horario_id' => $bloque->id,
            'fecha' => Carbon::now(config('app.timezone'))->toDateString(),
            'estado' => $estado,
        ]);
    }

    private function crearNotificacion(User $usuario)
    {
        return $usuario->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => RecordatorioInicioReserva::class,
            'data' => [
                'titulo' => 'Prueba',
                'mensaje' => 'Notificación de prueba',
                'url' => route('reservas.index'),
                'tipo' => 'inicio',
                'reserva_id' => 1,
            ],
        ]);
    }
}
