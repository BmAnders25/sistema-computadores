<?php

namespace App\Http\Controllers;

// use App\Models\Configuracion;

class ConfiguracionController extends Controller
{
    public function index()
    {
        return view('configuracion.index');
    }
}
