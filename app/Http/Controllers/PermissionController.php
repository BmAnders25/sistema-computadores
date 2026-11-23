<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Permission::query()->withCount('roles');
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $estado = $request->query('estado');
        if ($estado === 'asignado') {
            $query->has('roles');
        } elseif ($estado === 'sin_asignar') {
            $query->doesntHave('roles');
        }

        $totalPermisos = Permission::count();
        $asignados = Permission::has('roles')->count();
        $sinAsignar = $totalPermisos - $asignados;

        $permissions = $query->orderBy('name')
            ->paginate($this->catalogPerPage($request))
            ->withQueryString();

        return view('permissions.index', compact('permissions', 'totalPermisos', 'asignados', 'sinAsignar'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('permissions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar la entrada de datos
        $this->validateRequest($request);

        Permission::create(['name' => $request->name]);

        return redirect()->route('permissions.index')->with('success', 'Permiso creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        return view('permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->validateRequest($request, $permission->id);

        $permission->update(['name' => $request->name]);

        return redirect()->route('permissions.index')->with('success', 'Permiso actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permiso eliminado exitosamente.');
    }

    /**
     * Método privado para validar el request.
     */
    private function validateRequest(Request $request, $id = null)
    {
        $uniqueRule = 'unique:permissions,name';
        if ($id) {
            $uniqueRule .= ",$id";
        }

        $request->validate([
            'name' => "required|$uniqueRule|max:255",
        ]);
    }
}
