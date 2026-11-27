<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $fillable = ['bloque', 'nombre', 'capacidad', 'estado'];

    public function computadores()
    {
        return $this->hasMany(Computador::class);
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    public function getEtiquetaAttribute(): string
    {
        return "{$this->bloque} - {$this->nombre}";
    }
}
