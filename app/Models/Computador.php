<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Computador extends Model
{
    use HasUuids;
    protected $table = 'computadores'; 

    protected $fillable = [
        'codigo',
        'serial',
        'bloque',
        'laboratorio_aula',
        'tipo_equipo',
        'marca',
        'modelo',
        'procesador',
        'ram_gb',
        'almacenamiento',
        'sistema_operativo',
        'estado',
        'condicion',
        'observaciones',
    ];

    public function uniqueIds(): array
    {
        return ['codigo']; 
    }

    // Conversión de tipos de datos.
     
    protected $casts = [
        'ram_gb' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

