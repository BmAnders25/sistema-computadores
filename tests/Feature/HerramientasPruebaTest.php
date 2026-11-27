<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\RecordatorioInicioReserva;
use App\Notifications\ReservaConfirmada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HerramientasPruebaTest extends TestCase
{
    use RefreshDatabase;

    private const CSRF_TOKEN = 'herramientas-test-csrf-token';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tools_return_404_outside_local_and_require_administration_locally(): void
    {
        $admin = $this->userWithRole('Administracion');
        $student = $this->userWithRole('Estudiante');

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('herramientas.prueba'))
            ->assertNotFound();

        $this->app['env'] = 'local';
        User::factory()->create([
            'email' => 'prueba.menu@unipamplona.edu.co',
            'estado' => 'activo',
        ]);
        $this->actingAs($student)
            ->withSession(['two_factor_passed' => true])
            ->get(route('herramientas.prueba'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('herramientas.prueba'))
            ->assertOk()
            ->assertSee('Herramientas de prueba')
            ->assertSee('Ejecutar recordatorios ahora')
            ->assertSee('Entrar como');
    }

    public function test_impersonation_is_limited_to_test_users_and_return_validates_admin_id(): void
    {
        $this->app['env'] = 'local';
        $admin = $this->userWithRole('Administracion');
        $studentRole = Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
        $studentRole->givePermissionTo(Permission::firstOrCreate([
            'name' => 'ver-reservas',
            'guard_name' => 'web',
        ]));
        $target = User::factory()->create([
            'email' => 'prueba.impersonar@unipamplona.edu.co',
            'estado' => 'activo',
            'two_factor_enabled' => false,
        ]);
        $target->assignRole($studentRole);
        $regularUser = User::factory()->create([
            'email' => 'usuario.real@unipamplona.edu.co',
            'estado' => 'activo',
        ]);

        $this->actingAs($admin)
            ->withSession($this->sessionConCsrf(['two_factor_passed' => true]))
            ->post(route('herramientas.prueba.impersonar', $regularUser), $this->datosConCsrf())
            ->assertNotFound();

        $this->actingAs($admin)
            ->withSession($this->sessionConCsrf(['two_factor_passed' => true]))
            ->post(route('herramientas.prueba.impersonar', $target), $this->datosConCsrf())
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame($admin->id, session('impersonador_id'));
        $this->assertTrue(session('two_factor_passed'));

        $this->get(route('reservas.index'))
            ->assertOk()
            ->assertSee('Estás probando como')
            ->assertSee($target->name)
            ->assertSee('Volver a mi cuenta')
            ->assertSeeInOrder([
                'class="content-wrapper',
                'class="src-impersonation-banner"',
            ]);

        $this->withSession($this->sessionConCsrf([
            'impersonador_id' => $target->id,
            'two_factor_passed' => true,
        ]))
            ->post(route('herramientas.prueba.volver'), $this->datosConCsrf())
            ->assertForbidden();

        $this->withSession($this->sessionConCsrf([
            'impersonador_id' => $admin->id,
            'two_factor_passed' => true,
        ]))
            ->post(route('herramientas.prueba.volver'), $this->datosConCsrf())
            ->assertRedirect(route('herramientas.prueba'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonador_id'));
    }

    public function test_running_reminders_from_the_tools_page_sends_only_once(): void
    {
        $this->app['env'] = 'local';
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:00:00', config('app.timezone')));
        Notification::fake();
        $admin = $this->userWithRole('Administracion');
        $student = User::factory()->create([
            'email' => 'prueba.inicio@unipamplona.edu.co',
            'estado' => 'activo',
            'two_factor_enabled' => false,
        ]);
        $aula = Aula::create([
            'bloque' => 'Bloque A',
            'nombre' => 'LAB-HERRAMIENTAS',
            'capacidad' => 20,
            'estado' => 'activo',
        ]);
        $computador = Computador::create([
            'serial' => 'TOOLS-REMINDER-01',
            'aula_id' => $aula->id,
            'tipo_equipo' => 'escritorio',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'disponible',
            'condicion' => 'bueno',
        ]);
        $bloque = BloqueHorario::create([
            'nombre' => 'Bloque recordatorio de herramientas',
            'hora_inicio' => '08:20:00',
            'hora_fin' => '09:00:00',
            'activo' => true,
        ]);
        Reserva::create([
            'user_id' => $student->id,
            'computador_id' => $computador->id,
            'bloque_horario_id' => $bloque->id,
            'fecha' => '2026-11-23',
            'estado' => 'pendiente',
            'clave_unica' => Reserva::claveEquipo($computador->id, '2026-11-23', $bloque->id),
        ]);

        $this->actingAs($admin)
            ->withSession($this->sessionConCsrf(['two_factor_passed' => true]))
            ->post(route('herramientas.prueba.recordatorios'), $this->datosConCsrf())
            ->assertRedirect(route('herramientas.prueba'))
            ->assertSessionHas('resultado', 'Inicio: 1 enviados, Fin: 0 enviados');

        $this->post(route('herramientas.prueba.recordatorios'), $this->datosConCsrf())
            ->assertRedirect(route('herramientas.prueba'))
            ->assertSessionHas('resultado', 'Inicio: 0 enviados, Fin: 0 enviados');

        Notification::assertSentTo($student, RecordatorioInicioReserva::class);
        Notification::assertNotSentTo($student, ReservaConfirmada::class);
        $this->assertNotNull(Reserva::query()->where('user_id', $student->id)->value('recordatorio_inicio_enviado_at'));
    }

    public function test_restart_scenarios_runs_only_the_three_reminder_seeders(): void
    {
        $this->app['env'] = 'local';
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:00:00', config('app.timezone')));
        $admin = $this->userWithRole('Administracion');
        $aulas = [
            Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-REINICIO-A',
                'capacidad' => 20,
                'estado' => 'activo',
            ]),
            Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-REINICIO-B',
                'capacidad' => 20,
                'estado' => 'activo',
            ]),
        ];
        foreach ($aulas as $indice => $aula) {
            Computador::create([
                'serial' => 'TOOLS-RESTART-'.($indice + 1),
                'aula_id' => $aula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
        }

        $this->actingAs($admin)
            ->withSession($this->sessionConCsrf(['two_factor_passed' => true]))
            ->post(route('herramientas.prueba.reiniciar'), $this->datosConCsrf())
            ->assertRedirect(route('herramientas.prueba'));

        $this->assertDatabaseHas('users', ['email' => 'prueba.inicio@unipamplona.edu.co']);
        $this->assertDatabaseHas('users', ['email' => 'prueba.fin@unipamplona.edu.co']);
        $this->assertDatabaseHas('users', ['email' => 'prueba.docente@unipamplona.edu.co']);
        $this->assertSame(3, User::query()->where('email', 'like', 'prueba.%')->count());
    }

    public function test_tools_can_send_a_confirmation_to_a_test_users_latest_reservation(): void
    {
        $this->app['env'] = 'local';
        Mail::fake();
        $admin = $this->userWithRole('Administracion');
        $student = User::factory()->create([
            'email' => 'prueba.confirmacion@unipamplona.edu.co',
            'estado' => 'activo',
        ]);
        $block = BloqueHorario::create([
            'nombre' => 'Bloque confirmación prueba',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'activo' => true,
        ]);
        $reservation = Reserva::create([
            'user_id' => $student->id,
            'bloque_horario_id' => $block->id,
            'fecha' => today()->addDay()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($admin)
            ->withSession($this->sessionConCsrf(['two_factor_passed' => true]))
            ->post(route('herramientas.prueba.confirmacion', $student), $this->datosConCsrf())
            ->assertRedirect(route('herramientas.prueba'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'type' => ReservaConfirmada::class,
        ]);
        $this->assertSame($reservation->id, $student->notifications()->first()->data['reserva_id']);
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create([
            'estado' => 'activo',
            'two_factor_enabled' => false,
        ]);
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }

    private function sessionConCsrf(array $extra = []): array
    {
        return array_merge($extra, ['_token' => $this->csrfToken()]);
    }

    private function datosConCsrf(array $data = []): array
    {
        return array_merge($data, ['_token' => $this->csrfToken()]);
    }

    private function csrfToken(): string
    {
        return $this->app['session']->token() ?: self::CSRF_TOKEN;
    }
}
