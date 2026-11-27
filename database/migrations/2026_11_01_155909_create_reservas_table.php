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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('computador_id')->nullable()->constrained('computadores')->cascadeOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('bloque_horario_id')->constrained('bloques_horarios');
            $table->date('fecha');
            $table->enum('estado', ['pendiente', 'entregada', 'devuelta', 'cancelada', 'no_show'])
                ->default('pendiente');
            $table->timestamp('entregado_at')->nullable();
            $table->timestamp('devuelto_at')->nullable();
            $table->string('clave_unica')->nullable()->unique();   // NULL cuando la reserva ya no está activa
            $table->timestamps();

            $table->index(['fecha', 'bloque_horario_id']);
            $table->index(['user_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
