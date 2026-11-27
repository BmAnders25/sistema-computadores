<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Database\Seeders\LimpiarPruebasSeeder;
use Database\Seeders\PruebaRecordatorioInicioSeeder;
use Database\Seeders\PruebasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PruebasSeedersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_test_seeders_abort_outside_local_environment(): void
    {
        $this->app['env'] = 'testing';
        $this->expectException(HttpException::class);

        (new PruebaRecordatorioInicioSeeder)->run();
    }

    public function test_test_seeders_are_repeatable_local_only_and_do_not_send_confirmations(): void
    {
        $this->app['env'] = 'local';
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:00:00', config('app.timezone')));
        Notification::fake();
        $aulas = [
            Aula::create([
                'bloque' => 'Pruebas',
                'nombre' => 'LAB-SEEDER-A',
                'capacidad' => 20,
                'estado' => 'activo',
            ]),
            Aula::create([
                'bloque' => 'Pruebas',
                'nombre' => 'LAB-SEEDER-B',
                'capacidad' => 20,
                'estado' => 'activo',
            ]),
        ];
        for ($indice = 1; $indice <= 12; $indice++) {
            Computador::create([
                'serial' => sprintf('SEEDER-%02d', $indice),
                'aula_id' => $aulas[($indice - 1) % count($aulas)]->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
        }

        $this->seed(PruebasSeeder::class);
        $this->assertSame(13, Reserva::query()->count());
        Notification::assertNothingSent();
        $this->assertSame(1, Reserva::query()->whereNotNull('aula_id')->count());

        $inicio = User::query()->where('email', 'prueba.inicio@unipamplona.edu.co')->firstOrFail();
        $reservaInicio = $inicio->reservas()->firstOrFail();
        $reservaInicio->update(['estado' => 'cancelada', 'clave_unica' => null]);
        Reserva::query()->whereKey($reservaInicio->getKey())->update([
            'recordatorio_inicio_enviado_at' => Carbon::now(config('app.timezone')),
            'recordatorio_fin_enviado_at' => Carbon::now(config('app.timezone')),
        ]);

        $this->seed(PruebasSeeder::class);
        $this->assertSame(13, Reserva::query()->count());
        Notification::assertNothingSent();
        $this->assertSame('pendiente', $reservaInicio->fresh()->estado);
        $this->assertNull($reservaInicio->fresh()->recordatorio_inicio_enviado_at);
        $this->assertNull($reservaInicio->fresh()->recordatorio_fin_enviado_at);

        $usuarios = User::query()->where('email', 'like', 'prueba.%')->get();
        $this->assertCount(6, $usuarios);
        foreach ($usuarios as $usuario) {
            $this->assertSame('activo', $usuario->estado);
            $this->assertNotNull($usuario->email_verified_at);
            $this->assertTrue(password_verify('Password123', $usuario->password));
            $this->assertCount(1, $usuario->roles);
            if ($usuario->hasRole('Estudiante')) {
                $this->assertNotNull($usuario->codigo_institucional);
                $this->assertSame('Ingeniería de Sistemas', $usuario->programa);
            }
        }

        $this->assertDatabaseHas('reservas', [
            'estado' => 'no_show',
            'clave_unica' => null,
        ]);
        $this->assertSame(
            1,
            Reserva::query()->where('user_id', $usuarios->firstWhere('email', 'prueba.inicio@unipamplona.edu.co')->id)->count()
        );

        $this->seed(LimpiarPruebasSeeder::class);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('reservas', 0);
        $this->assertSame(0, \App\Models\BloqueHorario::query()->where('nombre', 'like', 'PRUEBA-%')->count());
    }
}
