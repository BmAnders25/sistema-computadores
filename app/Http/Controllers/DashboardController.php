<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Services\EstadisticasService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $aulas = Aula::query()->orderBy('bloque')->orderBy('nombre')->get(['id', 'bloque', 'nombre']);

        return view('dashboard.index', compact('aulas'));
    }

    public function datos(Request $request, EstadisticasService $estadisticas)
    {
        $datos = $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'aula_id' => ['nullable', 'integer', 'exists:aulas,id'],
            'bloque' => ['nullable', 'string', 'max:50'],
        ]);

        $desde = Carbon::parse($datos['desde'])->startOfDay();
        $hasta = Carbon::parse($datos['hasta'])->endOfDay();

        if ($desde->diffInDays($hasta->copy()->startOfDay()) + 1 > 366) {
            throw ValidationException::withMessages([
                'hasta' => 'El rango seleccionado no puede superar 366 días.',
            ]);
        }

        $aulaId = isset($datos['aula_id']) ? (int) $datos['aula_id'] : null;
        $bloque = isset($datos['bloque']) && trim($datos['bloque']) !== ''
            ? trim($datos['bloque'])
            : null;

        return response()->json([
            'kpis' => $estadisticas->kpis($desde, $hasta, $aulaId, $bloque),
            'tendencia' => $estadisticas->tendenciaReservas($desde, $hasta, $aulaId, $bloque),
            'uso_por_aula' => $estadisticas->usoPorAula($desde, $hasta, $aulaId, $bloque),
            'ocupacion_por_bloque' => $estadisticas->ocupacionPorBloqueHorario($desde, $hasta, $aulaId, $bloque),
            'top_usuarios' => $estadisticas->topUsuarios($desde, $hasta, $aulaId, $bloque),
            'detalle_ocupacion' => $estadisticas->detalleOcupacion($desde, $hasta, $aulaId, $bloque),
        ]);
    }
}