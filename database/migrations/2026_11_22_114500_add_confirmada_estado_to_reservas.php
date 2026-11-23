<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE reservas MODIFY estado ENUM('pendiente', 'confirmada', 'entregada', 'devuelta', 'cancelada', 'no_show') NOT NULL DEFAULT 'pendiente'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('reservas')->where('estado', 'confirmada')->update(['estado' => 'pendiente']);
        DB::statement(
            "ALTER TABLE reservas MODIFY estado ENUM('pendiente', 'entregada', 'devuelta', 'cancelada', 'no_show') NOT NULL DEFAULT 'pendiente'"
        );
    }
};
