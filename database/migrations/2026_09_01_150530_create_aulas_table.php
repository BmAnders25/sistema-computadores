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
    Schema::create('aulas', function (Blueprint $table) {
        $table->id();
        $table->string('bloque', 20);
        $table->string('nombre', 50);
        $table->unsignedSmallInteger('capacidad')->default(0);
        $table->enum('estado', ['activo', 'inactivo'])->default('activo');
        $table->timestamps();
    
        $table->unique(['bloque', 'nombre']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};
