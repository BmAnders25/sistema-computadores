<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\EntregaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntregaController extends Controller
{
    public function __construct(private EntregaService $entregaService)
    {
        $this->middleware('permission:ver-entregas', ['only' => ['index']]);
        $this->middleware('permission:registrar-entregas', ['only' => ['registrarEntrega']]);
        $this->middleware('permission:registrar-devoluciones', ['only' => ['registrarDevolucion']]);
    }

    public function index(Request $request): View
    {
        $baseQuery = Reserva::query()
            ->whereNotNull('computador_id')
            ->whereIn('estado', ['confirmada', 'entregada', 'devuelta']);

        $totalReservas = (clone $baseQuery)->count();
        $porEntregar = (clone $baseQuery)->where('estado', 'confirmada')->count();
        $enUso = (clone $baseQuery)->where('estado', 'entregada')->count();
        $finalizadas = (clone $baseQuery)->where('estado', 'devuelta')->count();

        $query = clone $baseQuery;
        $estado = $request->query('estado');
        if ($estado === 'confirmada' || $estado === 'entregada') {
            $query->where('estado', $estado);
        } elseif ($estado === 'finalizadas') {
            $query->where('estado', 'devuelta');
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($query) use ($like) {
                $query->whereHas('user', function ($users) use ($like) {
                    $users->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })->orWhereHas('computador', function ($computadores) use ($like) {
                    $computadores->where('marca', 'like', $like)
                        ->orWhere('modelo', 'like', $like)
                        ->orWhere('serial', 'like', $like)
                        ->orWhereHas('aula', function ($aulas) use ($like) {
                            $aulas->where('nombre', 'like', $like)
                                ->orWhere('bloque', 'like', $like);
                        });
                });
            });
        }

        $reservas = $query->with(['user', 'computador.aula', 'bloque'])
            ->latest('fecha')
            ->paginate($this->catalogPerPage($request))
            ->withQueryString();

        return view('entregas.index', compact(
            'reservas',
            'totalReservas',
            'porEntregar',
            'enUso',
            'finalizadas'
        ));
    }

    public function registrarEntrega(Reserva $reserva): RedirectResponse
    {
        $this->entregaService->registrarEntrega($reserva);

        return back()->with('success', 'Entrega registrada correctamente.');
    }

    public function registrarDevolucion(Reserva $reserva): RedirectResponse
    {
        $this->entregaService->registrarDevolucion($reserva);

        return back()->with('success', 'Devolución registrada correctamente.');
    }
}
