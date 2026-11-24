<?php

namespace Tests\Feature;

use App\Exports\OcupacionExport;
use App\Models\Aula;
use App\Models\BloqueHorario;
use App\Models\Computador;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet as ExcelSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReporteOcupacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_access_is_restricted_to_administracion_and_exports_download(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'ver-reportes',
            'guard_name' => 'web',
        ]);
        $administracion = Role::firstOrCreate([
            'name' => 'Administracion',
            'guard_name' => 'web',
        ]);
        $administracion->givePermissionTo($permission);

        $admin = User::factory()->create([
            'estado' => 'activo',
            'two_factor_enabled' => false,
        ]);
        $admin->assignRole($administracion);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('reportes.ocupacion'))
            ->assertOk()
            ->assertSee('Reporte de ocupación');

        $filtros = [
            'desde' => today()->subDays(29)->toDateString(),
            'hasta' => today()->toDateString(),
        ];

        $excelResponse = $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('reportes.ocupacion.excel', $filtros));

        $excelResponse->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $excelResponse->headers->get('content-type')
        );

        $pdfResponse = $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->get(route('reportes.ocupacion.pdf', $filtros));

        $pdfResponse->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $pdfResponse->headers->get('content-type')
        );

        foreach (['Estudiante', 'Docente', 'Celador'] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $user = User::factory()->create([
                'estado' => 'activo',
                'two_factor_enabled' => false,
            ]);
            $user->assignRole($role);

            $this->actingAs($user)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion'))
                ->assertForbidden();

            $this->actingAs($user)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion.excel', $filtros))
                ->assertForbidden();

            $this->actingAs($user)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion.pdf', $filtros))
                ->assertForbidden();
        }
    }

    public function test_report_filters_validate_range_and_aula(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'ver-reportes',
            'guard_name' => 'web',
        ]);
        $administracion = Role::firstOrCreate([
            'name' => 'Administracion',
            'guard_name' => 'web',
        ]);
        $administracion->givePermissionTo($permission);

        $admin = User::factory()->create(['estado' => 'activo']);
        $admin->assignRole($administracion);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('reportes.ocupacion', [
                'desde' => today()->toDateString(),
                'hasta' => today()->subDay()->toDateString(),
            ]))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('reportes.ocupacion', [
                'desde' => today()->subDays(366)->toDateString(),
                'hasta' => today()->toDateString(),
            ]))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('reportes.ocupacion', [
                'desde' => today()->subDays(365)->toDateString(),
                'hasta' => today()->toDateString(),
            ]))
            ->assertOk();

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('reportes.ocupacion', [
                'desde' => today()->toDateString(),
                'hasta' => today()->toDateString(),
                'aula_id' => 999999,
            ]))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => true])
            ->getJson(route('reportes.ocupacion', [
                'desde' => today()->toDateString(),
                'hasta' => today()->toDateString(),
                'estado' => 'en_uso',
            ]))
            ->assertStatus(422);
    }

    public function test_report_occupancy_uses_current_states_and_separates_cancelled_and_no_show(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-11-23 09:00:00', config('app.timezone')));

        try {
            $permission = Permission::firstOrCreate([
                'name' => 'ver-reportes',
                'guard_name' => 'web',
            ]);
            $adminRole = Role::firstOrCreate([
                'name' => 'Administracion',
                'guard_name' => 'web',
            ]);
            $adminRole->givePermissionTo($permission);
            $admin = User::factory()->create(['estado' => 'activo']);
            $admin->assignRole($adminRole);
            $usuario = User::factory()->create(['estado' => 'activo']);
            $aula = Aula::create([
                'bloque' => 'Bloque A',
                'nombre' => 'LAB-REPORTE-ESTADOS',
                'capacidad' => 25,
                'estado' => 'activo',
            ]);
            $bloque = BloqueHorario::create([
                'nombre' => 'Bloque 1',
                'hora_inicio' => '09:00',
                'hora_fin' => '10:00',
                'activo' => true,
            ]);
            $fecha = '2026-11-22';

            foreach (['pendiente', 'entregada', 'devuelta', 'cancelada', 'no_show'] as $estado) {
                $computador = Computador::create([
                    'serial' => 'REPORTE-ESTADO-'.strtoupper($estado),
                    'aula_id' => $aula->id,
                    'tipo_equipo' => 'escritorio',
                    'marca' => 'Dell',
                    'modelo' => 'OptiPlex',
                    'estado' => 'disponible',
                    'condicion' => 'bueno',
                ]);
                $clave = in_array($estado, ['pendiente', 'entregada'], true)
                    ? Reserva::claveEquipo($computador->id, $fecha, $bloque->id)
                    : null;

                Reserva::create([
                    'user_id' => $usuario->id,
                    'computador_id' => $computador->id,
                    'bloque_horario_id' => $bloque->id,
                    'fecha' => $fecha,
                    'estado' => $estado,
                    'entregado_at' => in_array($estado, ['entregada', 'devuelta'], true) ? Carbon::parse($fecha)->addHours(9) : null,
                    'devuelto_at' => $estado === 'devuelta' ? Carbon::parse($fecha)->addHours(10) : null,
                    'clave_unica' => $clave,
                ]);
            }

            $filtros = ['desde' => $fecha, 'hasta' => $fecha];

            $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion', $filtros))
                ->assertOk()
                ->assertSee('3 de 5 slots')
                ->assertSee('60,00%')
                ->assertSee('No show: 20,00%')
                ->assertSee('1 registros encontrados');

            $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion', $filtros + ['estado' => 'no_show']))
                ->assertOk()
                ->assertSee('0 de 5 slots')
                ->assertSee('No show: 100,00%')
                ->assertSee('0 registros encontrados')
                ->assertSee('Sin datos');

            $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion', $filtros + ['estado' => 'devuelta']))
                ->assertOk()
                ->assertSee('1 de 5 slots')
                ->assertSee('20,00%')
                ->assertSee('1 registros encontrados');

            $otraAula = Aula::create([
                'bloque' => 'Bloque B',
                'nombre' => 'LAB-REPORTE-OTRO',
                'capacidad' => 20,
                'estado' => 'activo',
            ]);
            $otroComputador = Computador::create([
                'serial' => 'REPORTE-OTRO-01',
                'aula_id' => $otraAula->id,
                'tipo_equipo' => 'escritorio',
                'marca' => 'Dell',
                'modelo' => 'OptiPlex',
                'estado' => 'disponible',
                'condicion' => 'bueno',
            ]);
            Reserva::create([
                'user_id' => $usuario->id,
                'computador_id' => $otroComputador->id,
                'bloque_horario_id' => $bloque->id,
                'fecha' => $fecha,
                'estado' => 'pendiente',
                'clave_unica' => Reserva::claveEquipo($otroComputador->id, $fecha, $bloque->id),
            ]);

            $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion', $filtros + ['aula_id' => $aula->id]))
                ->assertOk()
                ->assertSee('3 de 5 slots')
                ->assertSee('60,00%')
                ->assertSee('1 registros encontrados');

            $this->actingAs($admin)
                ->withSession(['two_factor_passed' => true])
                ->get(route('reportes.ocupacion', $filtros + ['aula_id' => $otraAula->id]))
                ->assertOk()
                ->assertSee('1 de 1 slots')
                ->assertSee('100,00%')
                ->assertSee('1 registros encontrados');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_excel_export_has_formatted_sheets_and_preserves_zero_values(): void
    {
        $export = new OcupacionExport(
            [
                'ocupacion' => 0.22,
                'slots_reservados' => 1,
                'slots_disponibles' => 4,
                'reservas_mes' => ['total' => 0, 'variacion_porcentaje' => 0.0],
                'equipos_hoy' => [
                    'disponibles' => 0,
                    'en_uso' => 0,
                    'mantenimiento' => 0,
                    'inactivos' => 0,
                ],
                'cancelaciones' => ['tasa_cancelacion' => 0.0, 'tasa_no_show' => 0.0],
            ],
            [[
                'fecha' => '2026-11-22',
                'bloque' => 'Bloque 1',
                'aula' => 'LAB-1',
                'slots_reservados' => 1,
                'slots_disponibles' => 4,
            ]],
            Carbon::parse('2026-11-01'),
            Carbon::parse('2026-11-22'),
            null,
            null,
            null,
            'Administración'
        );

        [$resumenExport, $detalleExport] = $export->sheets();
        $libro = new Spreadsheet;
        $resumen = $libro->getActiveSheet();
        $detalle = $libro->createSheet();
        $resumen->fromArray($resumenExport->array(), null, 'A1', true);
        $detalle->fromArray($detalleExport->array(), null, 'A1', true);

        $resumenEvents = $resumenExport->registerEvents();
        $resumenEvents[AfterSheet::class](new AfterSheet(new ExcelSheet($resumen), $resumenExport));
        $detalleEvents = $detalleExport->registerEvents();
        $detalleEvents[AfterSheet::class](new AfterSheet(new ExcelSheet($detalle), $detalleExport));

        $this->assertSame(
            'S.R.C. — Reporte de Ocupación (Universidad de Pamplona)',
            $resumen->getCell('A1')->getValue()
        );
        $this->assertContains('A1:B1', $resumen->getMergeCells());
        $this->assertEquals(28, $resumen->getRowDimension(1)->getRowHeight());
        $this->assertSame('Administración', $resumen->getCell('B6')->getValue());
        $this->assertSame('7B1E24', $resumen->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertEquals(13, $resumen->getStyle('A1')->getFont()->getSize());
        $this->assertSame(0.0022, $resumen->getCell('B9')->getValue());
        $this->assertSame('0.00%', $resumen->getStyle('B9')->getNumberFormat()->getFormatCode());
        $this->assertSame(0, $resumen->getCell('B17')->getValue());
        $this->assertSame(0.0, $resumen->getCell('B19')->getValue());
        $this->assertSame('0.00%', $resumen->getStyle('B19')->getNumberFormat()->getFormatCode());
        $this->assertSame('A6262E', $resumen->getStyle('A8')->getFill()->getStartColor()->getRGB());
        $this->assertSame('CBD5E1', $resumen->getStyle('A9')->getBorders()->getBottom()->getColor()->getRGB());
        $this->assertSame('F8FAFC', $resumen->getStyle('A10')->getFill()->getStartColor()->getRGB());

        $this->assertContains('A1:F1', $detalle->getMergeCells());
        $this->assertContains('B2:F2', $detalle->getMergeCells());
        $this->assertSame('Ocupación (%)', $detalle->getCell('F8')->getValue());
        $this->assertSame(0.25, $detalle->getCell('F9')->getValue());
        $this->assertSame('0.00%', $detalle->getStyle('F9')->getNumberFormat()->getFormatCode());
        $this->assertSame(1, $detalle->getCell('D9')->getValue());
    }
}
