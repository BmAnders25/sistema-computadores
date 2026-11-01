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
        Schema::create('computadores', function (Blueprint $table) {
            $table->id();
            $table->uuid('codigo')->unique();
            $table->string('serial', 100);
            $table->string('bloque', 20);
            $table->string('laboratorio_aula', 50);
            $table->enum('tipo_equipo', ['portatil', 'escritorio', 'all_in_one'])->default('escritorio');
            $table->string('marca', 50);
            $table->string('modelo', 50)->nullable();
            $table->string('procesador', 50)->nullable();
            $table->integer('ram_gb')->unsigned()->nullable(); 
            $table->string('sistema_operativo')->nullable();
            $table->string('almacenamiento', 50)->nullable();
            $table->enum('estado', ['disponible', 'prestado', 'mantenimiento', 'inactivo'])->default('disponible');
            $table->enum('condicion', ['excelente', 'bueno', 'regular', 'defectuoso'])->default('bueno');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computadores');
    }
};
