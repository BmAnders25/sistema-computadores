<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\Request;

class AulaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-aulas|crear-aulas|editar-aulas|borrar-aulas', ['only' => ['index']]);
        $this->middleware('permission:crear-aulas', ['only' => ['create','store']]);
        $this->middleware('permission:editar-aulas', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-aulas', ['only' => ['destroy']]);
    }

    public function index()
    {
        // Cargamos la cantidad de computadores asociados para mostrar métricas si se desea
        $aulas = Aula::withCount('computadores')->get();
        return view('aulas.index', compact('aulas'));
    }

    public function create()
    {
        return view('aulas.create');
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        Aula::create($request->all());

        return redirect()->route('aulas.index')
            ->with('success', 'Aula o laboratorio registrado correctamente.');
    }

    public function show(Aula $aula)
    {
        // Cargamos las relaciones para visualizarlas en detalle
        $aula->load('computadores', 'reservas');
        return view('aulas.show', compact('aula'));
    }

    public function edit(Aula $aula)
    {
        return view('aulas.edit', compact('aula'));
    }

    public function update(Request $request, Aula $aula)
    {
        $this->validateRequest($request);

        $aula->update($request->all());

        return redirect()->route('aulas.index')
            ->with('success', 'Aula o laboratorio actualizado correctamente.');
    }

    public function destroy(Aula $aula)
    {
        // Verificamos si el aula tiene computadores asignados antes de eliminar
        if ($aula->computadores()->count() > 0) {
            return back()->with('error', 'No se puede eliminar: el aula tiene equipos de cómputo asociados.');
        }

        $aula->delete();

        return redirect()->route('aulas.index')
            ->with('success', 'El aula ha sido eliminada correctamente.');
    }

    public function validateRequest(Request $request)
    {
        return $request->validate([
            'bloque' => 'required|string|max:50',
            'nombre' => 'required|string|max:100',
            'capacidad' => 'nullable|integer|min:1|max:200',
            'estado' => 'required|string|max:50',
        ], [
            'bloque.required' => 'El bloque es obligatorio.',
            'nombre.required' => 'El nombre del laboratorio o aula es obligatorio.',
            'capacidad.integer' => 'La capacidad debe ser un número entero válido.',
            'estado.required' => 'El estado del aula es obligatorio.',
        ]);
    }
}