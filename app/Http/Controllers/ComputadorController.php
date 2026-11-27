<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Computador; // <-- Importante para cargar el listado de aulas
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComputadorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-computadores|crear-computadores|editar-computadores|borrar-computadores', ['only' => ['index']]);
        $this->middleware('permission:crear-computadores', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-computadores', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-computadores', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = Computador::with('aula');
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('marca', 'like', $like)
                    ->orWhere('modelo', 'like', $like)
                    ->orWhere('serial', 'like', $like)
                    ->orWhere('procesador', 'like', $like)
                    ->orWhere('sistema_operativo', 'like', $like)
                    ->orWhereHas('aula', function ($aulas) use ($like) {
                        $aulas->where('nombre', 'like', $like)
                            ->orWhere('bloque', 'like', $like);
                    });
            });
        }

        $estado = $request->query('estado');
        if ($estado === 'disponible') {
            $query->where('estado', 'disponible');
        } elseif ($estado === 'en_uso') {
            $query->whereIn('estado', ['prestado', 'reservado']);
        } elseif ($estado === 'mantenimiento') {
            $query->where(function ($query) {
                $query->where('estado', 'mantenimiento')
                    ->orWhere('condicion', 'defectuoso');
            });
        }

        $aulaId = $request->query('aula_id');
        if (is_string($aulaId) && ctype_digit($aulaId)) {
            $query->where('aula_id', (int) $aulaId);
        }

        $totalEquipos = Computador::count();
        $disponibles = Computador::where('estado', 'disponible')->count();
        $enUso = Computador::whereIn('estado', ['prestado', 'reservado'])->count();
        $mantenimiento = Computador::where(function ($query) {
            $query->where('estado', 'mantenimiento')
                ->orWhere('condicion', 'defectuoso');
        })->count();
        $ubicaciones = Aula::orderBy('bloque')->orderBy('nombre')->get(['id', 'bloque', 'nombre']);

        $computadores = $query->orderBy('marca')->orderBy('serial')
            ->paginate($this->catalogPerPage($request))
            ->withQueryString();

        return view('computadores.index', compact(
            'computadores',
            'totalEquipos',
            'disponibles',
            'enUso',
            'mantenimiento',
            'ubicaciones'
        ));
    }

    public function create()
    {
        // Obtenemos todas las aulas para mostrarlas en un select
        $aulas = Aula::all();

        return view('computadores.create', compact('aulas'));
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        $computador = new Computador;

        // Asignación campo por campo incluyendo aula_id
        $computador->serial = $request->serial;
        $computador->aula_id = $request->aula_id; // <-- Nuevo
        $computador->tipo_equipo = $request->tipo_equipo;
        $computador->marca = $request->marca;
        $computador->modelo = $request->modelo;
        $computador->procesador = $request->procesador;
        $computador->ram_gb = $request->ram_gb;
        $computador->almacenamiento = $request->almacenamiento;
        $computador->sistema_operativo = $request->sistema_operativo;
        $computador->estado = $request->estado;
        $computador->condicion = $request->condicion;
        $computador->observaciones = $request->observaciones;

        $computador->save();

        return redirect()->route('computadores.index')
            ->with('success', 'Equipo de cómputo registrado correctamente.');
    }

    public function show(Computador $computador)
    {
        return view('computadores.show', compact('computador'));
    }

    public function edit(Computador $computador)
    {
        // Pasamos el equipo y el listado de aulas a la vista de edición
        $aulas = Aula::all();

        return view('computadores.edit', compact('computador', 'aulas'));
    }

    public function update(Request $request, Computador $computador)
    {
        $this->validateRequest($request);

        // Asignación campo por campo incluyendo aula_id
        $computador->serial = $request->serial;
        $computador->aula_id = $request->aula_id; // <-- Nuevo
        $computador->tipo_equipo = $request->tipo_equipo;
        $computador->marca = $request->marca;
        $computador->modelo = $request->modelo;
        $computador->procesador = $request->procesador;
        $computador->ram_gb = $request->ram_gb;
        $computador->almacenamiento = $request->almacenamiento;
        $computador->sistema_operativo = $request->sistema_operativo;
        $computador->estado = $request->estado;
        $computador->condicion = $request->condicion;
        $computador->observaciones = $request->observaciones;

        $computador->save();

        return redirect()->route('computadores.index')
            ->with('success', 'Equipo de cómputo actualizado correctamente.');
    }

    public function destroy(Computador $computador)
    {
        if ($computador->estado === 'prestado') {
            return back()->with('error', 'No se puede inactivar: el equipo está prestado en este momento.');
        }

        $computador->update(['estado' => 'inactivo']);

        return redirect()->route('computadores.index')
            ->with('success', 'El equipo ha sido marcado como inactivo correctamente.');
    }

    public function validateRequest(Request $request)
    {
        $computador = $request->route('computador');

        return $request->validate([
            'serial' => [
                'required',
                'string',
                'max:100',
                Rule::unique('computadores', 'serial')->ignore($computador->id ?? null),
            ],
            'aula_id' => 'required|exists:aulas,id', // <-- Validamos que el aula exista en la BD
            'tipo_equipo' => 'required|in:portatil,escritorio,all_in_one',
            'marca' => 'required|string|max:50',
            'modelo' => 'nullable|string|max:50',
            'procesador' => 'nullable|string|max:50',
            'ram_gb' => 'nullable|integer|min:1|max:512',
            'almacenamiento' => 'nullable|string|max:50',
            'sistema_operativo' => 'nullable|string|max:255',
            'estado' => 'required|in:disponible,prestado,mantenimiento,inactivo',
            'condicion' => 'required|in:excelente,bueno,regular,defectuoso',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'serial.required' => 'El número de serial es obligatorio.',
            'serial.unique' => 'Ya existe un equipo registrado con este número de serial.',
            'aula_id.required' => 'Debes seleccionar el laboratorio o aula correspondiente.',
            'aula_id.exists' => 'El aula seleccionada no es válida.',
            'tipo_equipo.required' => 'Selecciona un tipo de equipo válido.',
            'tipo_equipo.in' => 'El tipo de equipo no es válido.',
            'marca.required' => 'La marca del equipo es obligatoria.',
            'ram_gb.integer' => 'La memoria RAM debe ser un número entero en GB.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'condicion.in' => 'La condición seleccionada no es válida.',
        ]);
    }
}
