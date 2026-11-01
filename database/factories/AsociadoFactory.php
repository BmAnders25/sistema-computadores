<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asociado>
 */
class AsociadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cedula' => fake()->text(10),
            'nombre' => fake()->text(80),
            'direccion' => fake()->text(80),
            'telefono' => fake()->text(30),
            'email' => fake()->text(80),
            'estado' => fake()->randomElement(['Activo', 'Suspendido', 'Retirado']),
            'fecha_afiliacion' => fake()->date()
        ];
    }
}