<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->timestamp('recordatorio_inicio_enviado_at')->nullable();
            $table->timestamp('recordatorio_fin_enviado_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn([
                'recordatorio_inicio_enviado_at',
                'recordatorio_fin_enviado_at',
            ]);
        });
    }
};
