<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Aula;
use App\Models\Computador;
use App\Models\BloqueHorario;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    protected $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;

        $this->middleware('permission:ver-reservas', ['only' => ['index', 'show']]);
        $this->middleware('permission:crear-reservas', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-reservas', ['only' => ['edit', 'update']]);
        $this->middleware('permission:cancelar-reservas', ['only' => ['destroy']]);
    }

    public function index()
    {
        $user = Auth::user();

        // Si es Administración o Celador, ven todas las reservas; si es Estudiante/Docente, solo las suyas.
        if ($user->hasRole(['Administracion', 'Celador'])) {
            $reservas = Reserva::with(['user', 'aula', 'computador.aula', 'bloque'])->latest()->get();
        } else {
            $reservas = Reserva::with(['user', 'aula', 'computador.aula', 'bloque'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return view('reservas.index', compact('reservas'));
    }

    public function create(Request $request)
    {
        $tipo = $request->get('tipo', 'equipo'); // 'equipo' o 'aula'
        $aulas = Aula::where('estado', 'activa')->get();
        $computadores = Computador::with('aula')->where('estado', 'disponible')->get();
        $bloques = BloqueHorario::where('activo', true)->get();

        return view('reservas.create', compact('tipo', 'aulas', 'computadores', 'bloques'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo'              => 'required|in:equipo,aula',
            'fecha'             => 'required|date|after_or_equal:today',
            'bloque_horario_id' => 'required|exists:bloques_horarios,id',
            'computador_id'     => 'required_if:tipo,equipo|nullable|exists:computadores,id',
            'aula_id'           => 'required_if:tipo,aula|nullable|exists:aulas,id',
        ]);

        $user = Auth::user();

        try {
            if ($request->tipo === 'aula') {
                // Validar que tenga el permiso explícito para reservar aulas completas
                if (!$user->can('reservar-aulas')) {
                    throw ValidationException::withMessages(['reserva' => 'No tienes permisos para reservar aulas completas.']);
                }
                $this->reservaService->reservarAula($user, $request->aula_id, $request->fecha, $request->bloque_horario_id);
            } else {
                $this->reservaService->reservarEquipo($user, $request->computador_id, $request->fecha, $request->bloque_horario_id);
            }

            return redirect()->route('reservas.index')
                ->with('success', '¡Reserva registrada con éxito!');

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function show(Reserva $reserva)
    {
        $user = Auth::user();
        // Verificar si puede verla (Admin/Celador o dueño de la reserva)
        if (!$user->hasRole(['Administracion', 'Celador']) && $reserva->user_id !== $user->id) {
            abort(403);
        }

        $reserva->load(['user', 'aula', 'computador.aula', 'bloque']);
        return view('reservas.show', compact('reserva'));
    }

    public function edit(Reserva $reserva)
    {
        $user = Auth::user();
        // Solo administración o el dueño de la reserva pueden editar/revisar su estado administrativo
        if (!$user->hasRole('Administracion') && $reserva->user_id !== $user->id) {
            abort(403);
        }

        return view('reservas.edit', compact('reserva'));
    }

    public function update(Request $request, Reserva $reserva)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,completada,cancelada',
        ]);

        $reserva->update([
            'estado' => $request->estado,
        ]);

        return redirect()->route('reservas.index')
            ->with('success', '¡Reserva actualizada correctamente!');
    }

    public function destroy(Reserva $reserva)
    {
        try {
            $this->reservaService->cancelar(Auth::user(), $reserva);
            return redirect()->route('reservas.index')
                ->with('success', 'La reserva ha sido cancelada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('reservas.index')
                ->with('error', $e->getMessage());
        }
    }

    public function confirmarIngreso(Reserva $reserva)
{
    
    abort_unless(auth()->user()->hasAnyRole(['Celador', 'Administracion']), 403);

    if ($reserva->estado !== 'pendiente') {
        return back()->with('error', 'Solo se pueden confirmar reservas pendientes.');
    }

    $reserva->update([
        'estado' => 'entregada'
    ]);

    return back()->with('success', '¡Ingreso confirmado con éxito!');
}
}