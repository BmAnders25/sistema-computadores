<?php

namespace App\Http\Controllers;

use App\Models\Computador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComputadorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-computadores|crear-computadores|editar-computadores|borrar-computadores', ['only' => ['index']]);
        $this->middleware('permission:crear-computadores', ['only' => ['create','store']]);
        $this->middleware('permission:editar-computadores', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-computadores', ['only' => ['destroy']]);
    }
    public function index()
    {
        $computadores = Computador::all();
        return view('computadores.index',compact('computadores'));
    }

    public function create()
    {
        return view('computadores.create');
    }

    public function store(Request $request)
{
    
    $this->validateRequest($request);

    $computador = new Computador();
    
    // Asignacion campo por campo
    $computador->serial            = $request->serial;
    $computador->bloque            = $request->bloque;
    $computador->laboratorio_aula  = $request->laboratorio_aula;
    $computador->tipo_equipo       = $request->tipo_equipo;
    $computador->marca             = $request->marca;
    $computador->modelo            = $request->modelo;
    $computador->procesador        = $request->procesador;
    $computador->ram_gb            = $request->ram_gb;
    $computador->almacenamiento    = $request->almacenamiento;
    $computador->sistema_operativo = $request->sistema_operativo;
    $computador->estado            = $request->estado;
    $computador->condicion         = $request->condicion;
    $computador->observaciones     = $request->observaciones;

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
        return view('computadores.edit', compact('computador'));
    }

    public function update(Request $request, Computador $computador)
{
    $this->validateRequest($request);

    // Asignación campo por campo
    $computador->serial            = $request->serial;
    $computador->bloque            = $request->bloque;
    $computador->laboratorio_aula  = $request->laboratorio_aula;
    $computador->tipo_equipo       = $request->tipo_equipo;
    $computador->marca             = $request->marca;
    $computador->modelo            = $request->modelo;
    $computador->procesador        = $request->procesador;
    $computador->ram_gb            = $request->ram_gb;
    $computador->almacenamiento    = $request->almacenamiento;
    $computador->sistema_operativo = $request->sistema_operativo;
    $computador->estado            = $request->estado;
    $computador->condicion         = $request->condicion;
    $computador->observaciones     = $request->observaciones;
    $computador->save();

    return redirect()->route('computadores.index')
        ->with('success', 'Equipo de cómputo actualizado correctamente.');
}
    public function destroy(Computador $computador)
{
    if ($computador->estado === 'prestado') {
        return back()->with('error', 'No se puede inactivar: el equipo está prestado en este momento.');
    }
    // Cuando exista la relación reservas():
    // if ($computador->reservas()->whereNotNull('clave_unica')->where('fecha', '>=', today())->exists()) { ... }

    $computador->update(['estado' => 'inactivo']);
    return redirect()->route('computadores.index')
        ->with('success', 'El equipo ha sido marcado como inactivo correctamente.');
}
    public function validateRequest (Request $request){
        
        $computador = $request->route('computador');
        return $request->validate([
        'serial' => [
            'required',
            'string',
            'max:100',
            // Ignora el ID del computador de la ruta (si es update) o pasa null (si es store)
            Rule::unique('computadores', 'serial')->ignore($computador->id ?? null),
        ],
        'bloque' => 'required|string|max:20',
        'laboratorio_aula' => 'required|string|max:50',
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
        'bloque.required' => 'Debes indicar el bloque donde se ubica el equipo.',
        'laboratorio_aula.required' => 'Debes especificar el laboratorio o aula.',
        'tipo_equipo.required' => 'Selecciona un tipo de equipo válido.',
        'tipo_equipo.in' => 'El tipo de equipo no es válido.',
        'marca.required' => 'La marca del equipo es obligatoria.',
        'ram_gb.integer' => 'La memoria RAM debe ser un número entero en GB.',
        'estado.in' => 'El estado seleccionado no es válido.',
        'condicion.in' => 'La condición seleccionada no es válida.',
    ]);
    }
}