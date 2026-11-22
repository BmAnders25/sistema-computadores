<?php

namespace App\Http\Controllers;

use App\Models\BloqueHorario;
use Illuminate\Http\Request;

class BloqueHorarioController extends Controller
{
    public function __construct()
    {
        // Sincronizado exactamente con los nombres del RoleSeeder
        $this->middleware('permission:ver-bloques-horarios|crear-bloques-horarios|editar-bloques-horarios|borrar-bloques-horarios', ['only' => ['index']]);
        $this->middleware('permission:crear-bloques-horarios', ['only' => ['create','store']]);
        $this->middleware('permission:editar-bloques-horarios', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-bloques-horarios', ['only' => ['destroy']]);
    }

    public function index()
    {
        $bloques = BloqueHorario::all();
        return view('bloques.index', compact('bloques'));
    }

    public function create()
    {
        return view('bloques.create');
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        BloqueHorario::create([
            'nombre'      => $request->nombre,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin'    => $request->hora_fin,
            'activo'      => $request->has('activo'),
        ]);

        // Apunta a la ruta 'bloques-horarios.index' según tu menú AdminLTE
        return redirect()->route('bloques-horarios.index')
            ->with('success', 'Bloque horario registrado correctamente.');
    }

    public function show(BloqueHorario $bloqueHorario)
    {
        return view('bloques.show', compact('bloqueHorario'));
    }

    public function edit(BloqueHorario $bloqueHorario)
    {
        return view('bloques.edit', compact('bloqueHorario'));
    }

    public function update(Request $request, BloqueHorario $bloqueHorario)
    {
        $this->validateRequest($request);

        $bloqueHorario->update([
            'nombre'      => $request->nombre,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin'    => $request->hora_fin,
            'activo'      => $request->has('activo'),
        ]);

        return redirect()->route('bloques-horarios.index')
            ->with('success', 'Bloque horario actualizado correctamente.');
    }

    public function destroy(BloqueHorario $bloqueHorario)
    {
        $bloqueHorario->delete();

        return redirect()->route('bloques-horarios.index')
            ->with('success', 'El bloque horario ha sido eliminado correctamente.');
    }

    public function validateRequest(Request $request)
    {
        return $request->validate([
            'nombre'      => 'required|string|max:100',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'activo'      => 'nullable|boolean',
        ], [
            'nombre.required'        => 'El nombre del bloque horario es obligatorio.',
            'hora_inicio.required'   => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format'=> 'El formato de hora de inicio debe ser HH:MM.',
            'hora_fin.required'      => 'La hora de fin es obligatoria.',
            'hora_fin.date_format'   => 'El formato de hora de fin debe ser HH:MM.',
            'hora_fin.after'         => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);
    }
}