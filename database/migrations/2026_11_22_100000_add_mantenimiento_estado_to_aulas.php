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
            "ALTER TABLE aulas MODIFY estado ENUM('activo', 'inactivo', 'mantenimiento') NOT NULL DEFAULT 'activo'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('aulas')->where('estado', 'mantenimiento')->update(['estado' => 'inactivo']);
        DB::statement("ALTER TABLE aulas MODIFY estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'");
    }
};
