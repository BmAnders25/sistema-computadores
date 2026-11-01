<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    // database/seeders/UserSeeder.php
public function run(): void
{
    $usuarios = [
        ['Administración',        'admin@gmail.com',                 'Administracion', null,        false, 'activo'],
        ['Celador Prueba',        'celador@unipamplona.edu.co',      'Celador',        null,        false, 'activo'],
        ['Docente Prueba',        'docente@unipamplona.edu.co',      'Docente',        null,        false, 'activo'],
        ['Estudiante Activo',     'estudiante@unipamplona.edu.co',   'Estudiante',     '1098000001', true,  'activo'],
        ['Estudiante Inactivo',   'inactivo@unipamplona.edu.co',     'Estudiante',     '1098000002', false, 'inactivo'],
    ];

    foreach ($usuarios as [$nombre, $email, $rol, $codigo, $dosPasos, $estado]) {
        User::updateOrCreate(['email' => $email], [
            'name' => $nombre,
            'password' => 'Password123',     
            'codigo_institucional' => $codigo,
            'programa' => $rol === 'Estudiante' ? 'Ingeniería de Sistemas' : null,
            'estado' => $estado,
            'two_factor_enabled' => $dosPasos,
        ])->syncRoles([$rol]);
    }
}

}
