<?php

namespace App\Http\Controllers;

//use App\Models\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller {
    function index () {
    return view('dashboard.index');
}
}