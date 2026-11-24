<?php

namespace Database\Seeders;

class PruebasSeeder extends BasePruebaSeeder
{
    public function run(): void
    {
        $this->protegerEntorno();
        $this->call([
            PruebaRecordatorioInicioSeeder::class,
            PruebaRecordatorioFinSeeder::class,
            PruebaRecordatorioAulaSeeder::class,
            PruebaLimiteDiarioSeeder::class,
            PruebaEstadosSeeder::class,
            PruebaCeladorSeeder::class,
        ]);
    }
}
