<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:ver-roles|crear-roles|editar-roles|borrar-roles', ['only' => ['index']]);
         $this->middleware('permission:crear-roles', ['only' => ['create','store']]);
         $this->middleware('permission:editar-roles', ['only' => ['edit','update']]);
         $this->middleware('permission:borrar-roles', ['only' => ['destroy']]);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::all();
        return view('roles.index',compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permission = Permission::get();
        return view('roles.create',compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar la entrada de datos
        $this->validateRequest($request);
    
        $role = Role::create(['name' => $request->input('name')]);
        //$role->syncPermissions($request->input('permission'));
        $permissions = [];
        $post_permissions = $request->input('permission');
        foreach ($post_permissions as $key => $val) {
            $permissions[intval($val)] = intval($val);
        }
        $role->syncPermissions($permissions);
    
        return redirect()->route('roles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
        ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
        ->all();

        return view('roles.show',compact('role','permission','rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();
    
        return view('roles.edit',compact('role','permission','rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validar la entrada de datos
        $this->validateRequest($request, $id);
    
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->save();
    
        $permissions = [];
        $post_permissions = $request->input('permission');
        foreach ($post_permissions as $key => $val) {
            $permissions[intval($val)] = intval($val);
        }
        $role->syncPermissions($permissions);
    
        return redirect()->route('roles.index'); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::table("roles")->where('id',$id)->delete();
        return redirect()->route('roles.index');
    }

    /**
     * Método privado para validar el request.
     */
    private function validateRequest(Request $request, $id = null)
    {
        $uniqueRule = 'unique:roles,name';
        if ($id) {
            $uniqueRule .= ",$id";
        }

        $request->validate([
            'name' => "required|$uniqueRule",
            'permission' => 'required|array',
        ]);
    }

}