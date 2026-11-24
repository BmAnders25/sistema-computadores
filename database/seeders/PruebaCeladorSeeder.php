<?php

namespace Database\Seeders;

class PruebaCeladorSeeder extends BasePruebaSeeder
{
    public function run(): void
    {
        $this->protegerEntorno();
        $user = $this->usuario('prueba.celador@unipamplona.edu.co', 'Prueba Celador', 'Celador');
        $this->resumen($user, 'Celador', 'Acceso de prueba sin reservas', null);
    }
}
