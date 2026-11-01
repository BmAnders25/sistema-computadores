<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ROLES_INSTITUCIONALES = ['Estudiante', 'Docente'];

    public function __construct()
    {
        $this->middleware('permission:ver-usuarios|crear-usuarios|editar-usuarios|borrar-usuarios', ['only' => ['index']]);
        $this->middleware('permission:crear-usuarios', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-usuarios', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-usuarios', ['only' => ['destroy']]);
    }

    public function index()
    {
        $users = User::with('roles')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::pluck('name', 'name')->all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $datos = $this->validateRequest($request);

        $user = User::create([
            'name'                 => $datos['name'],
            'email'                => $datos['email'],
            'password'             => $datos['password'],   // el cast 'hashed' lo encripta
            'codigo_institucional' => $datos['codigo_institucional'] ?? null,
            'programa'             => $datos['programa'] ?? null,
            'estado'               => $datos['estado'],
            'two_factor_enabled'   => $request->boolean('two_factor_enabled'),
        ]);

        $user->syncRoles([$datos['roles']]);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function edit(string $id)
    {
        $user  = User::with('roles')->findOrFail($id);
        $roles = Role::pluck('name', 'name')->all();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, string $id)
    {
        $user  = User::findOrFail($id);
        $datos = $this->validateRequest($request, $user->id);

        // Evita que un administrador se bloquee a sí mismo
        if ($user->id === auth()->id()) {
            if ($datos['estado'] !== 'activo') {
                return back()->withInput()->with('error', 'No puedes desactivar tu propia cuenta.');
            }
            if ($datos['roles'] !== 'Administracion') {
                return back()->withInput()->with('error', 'No puedes quitarte tu propio rol de Administración.');
            }
        }

        $campos = [
            'name'                 => $datos['name'],
            'email'                => $datos['email'],
            'codigo_institucional' => $datos['codigo_institucional'] ?? null,
            'programa'             => $datos['programa'] ?? null,
            'estado'               => $datos['estado'],
            'two_factor_enabled'   => $request->boolean('two_factor_enabled'),
        ];

        if (!empty($datos['password'])) {
            $campos['password'] = $datos['password'];
        }

        $user->update($campos);
        $user->syncRoles([$datos['roles']]);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        if ((int) $id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        User::findOrFail($id)->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    private function validateRequest(Request $request, ?int $id = null): array
    {
        $dominios = config('institucional.dominios', ['unipamplona.edu.co']);
        $regexDominios = '/@(' . implode('|', array_map('preg_quote', $dominios)) . ')$/i';

        $rol = $request->input('roles');
        $requiereDominio = in_array($rol, self::ROLES_INSTITUCIONALES, true);
        $esEstudiante = $rol === 'Estudiante';

        $emailRules = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)];
        if ($requiereDominio) {
            $emailRules[] = 'regex:' . $regexDominios;
        }

        return $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => $emailRules,
            'password'             => [$id ? 'nullable' : 'required', 'min:8', 'same:confirm-password'],
            'roles'                => ['required', 'string', Rule::exists('roles', 'name')],
            'codigo_institucional' => [Rule::requiredIf($esEstudiante), 'nullable', 'alpha_dash', 'max:30',
                                       Rule::unique('users', 'codigo_institucional')->ignore($id)],
            'programa'             => [Rule::requiredIf($esEstudiante), 'nullable', 'string', 'max:100'],
            'estado'               => ['required', Rule::in(['activo', 'inactivo', 'suspendido'])],
        ], [
            'email.regex'                    => 'Los usuarios con rol Estudiante o Docente deben usar un correo institucional (' . implode(', ', $dominios) . ').',
            'password.min'                   => 'La contraseña debe tener al menos 8 caracteres.',
            'password.same'                  => 'La confirmación de la contraseña no coincide.',
            'codigo_institucional.required'  => 'El código institucional es obligatorio para estudiantes.',
            'codigo_institucional.unique'    => 'Ese código institucional ya está registrado.',
            'programa.required'              => 'El programa académico es obligatorio para estudiantes.',
        ]);
    }
}