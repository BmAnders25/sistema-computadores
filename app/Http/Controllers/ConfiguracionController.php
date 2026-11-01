<?php

namespace App\Http\Controllers;

//use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfiguracionController extends Controller {
    function index () {
    return view('configuracion.index');
}
}