<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        // Obtener los últimos 3 periodos ordenados por la fecha de fin
        //$ultimosPeriodos = Periodo::orderBy('fecha_fin', 'desc')->take(3)->get();
        
        // Obtener el periodo que está en estado 'Abierto'
        

        return view('home');
    }
}