<?php

namespace App\Http\Controllers;

use App\Exports\OcupacionExport;
use App\Http\Requests\ReporteOcupacionRequest;
use App\Models\Aula;
use App\Services\EstadisticasService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteOcupacionController extends Controller
{
    public function index(ReporteOcupacionRequest $request, EstadisticasService $estadisticas)
    {
        [$desde, $hasta, $aulaId, $estado] = $this->normalizarFiltros($request);
        $detalle = $estadisticas->detalleOcupacion($desde, $hasta, $aulaId, null, $estado);
        $kpis = $estadisticas->kpis($desde, $hasta, $aulaId, null, $estado);

        $pagina = max(1, (int) $request->query('page', 1));
        $detallePaginado = new LengthAwarePaginator(
            collect(array_slice($detalle, ($pagina - 1) * 15, 15))->values(),
            count($detalle),
            15,
            $pagina,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $aulas = Aula::query()->orderBy('bloque')->orderBy('nombre')->get(['id', 'bloque', 'nombre']);

        return view('reportes.ocupacion', [
            'detalle' => $detallePaginado,
            'kpis' => $kpis,
            'aulas' => $aulas,
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'aulaId' => $aulaId,
            'estado' => $estado,
        ]);
    }

    public function excel(ReporteOcupacionRequest $request, EstadisticasService $estadisticas): BinaryFileResponse
    {
        [$desde, $hasta, $aulaId, $estado] = $this->normalizarFiltros($request);
        $kpis = $estadisticas->kpis($desde, $hasta, $aulaId, null, $estado);
        $detalle = $estadisticas->detalleOcupacion($desde, $hasta, $aulaId, null, $estado);

        $nombre = 'ocupacion-' . $desde->toDateString() . '-a-' . $hasta->toDateString() . '.xlsx';
        $aula = $aulaId ? Aula::query()->find($aulaId) : null;
        $aulaNombre = $aula ? 'Bloque ' . $aula->bloque . ' · ' . $aula->nombre : null;

        return Excel::download(
            new OcupacionExport(
                $kpis,
                $detalle,
                $desde,
                $hasta,
                $aulaId,
                $estado,
                $aulaNombre,
                auth()->user()?->name
            ),
            $nombre
        );
    }

    public function pdf(ReporteOcupacionRequest $request, EstadisticasService $estadisticas)
    {
        [$desde, $hasta, $aulaId, $estado] = $this->normalizarFiltros($request);
        $kpis = $estadisticas->kpis($desde, $hasta, $aulaId, null, $estado);
        $detalle = $estadisticas->detalleOcupacion($desde, $hasta, $aulaId, null, $estado);
        $aula = $aulaId ? Aula::query()->find($aulaId) : null;

        $pdf = Pdf::loadView('reportes.pdf.ocupacion', [
            'kpis' => $kpis,
            'detalle' => $detalle,
            'desde' => $desde,
            'hasta' => $hasta,
            'aula' => $aula,
            'estado' => $estado,
            'generadoPor' => auth()->user()?->name,
        ])->setPaper('a4', 'landscape');

        $nombre = 'ocupacion-' . $desde->toDateString() . '-a-' . $hasta->toDateString() . '.pdf';

        return $pdf->download($nombre);
    }

    private function normalizarFiltros(ReporteOcupacionRequest $request): array
    {
        $desde = Carbon::parse($request->validated('desde'))->startOfDay();
        $hasta = Carbon::parse($request->validated('hasta'))->endOfDay();
        $aulaId = $request->validated('aula_id') ? (int) $request->validated('aula_id') : null;
        $estado = $request->validated('estado') ?: null;

        return [$desde, $hasta, $aulaId, $estado];
    }
}
