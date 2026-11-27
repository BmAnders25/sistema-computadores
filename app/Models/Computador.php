<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Computador extends Model
{
    use HasUuids;

    protected $table = 'computadores';

    protected $fillable = [
        'codigo',
        'serial',
        'aula_id',
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

    /**
     * Relación con el modelo Aula.
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'aula_id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    // Conversión de tipos de datos.
    protected $casts = [
        'aula_id' => 'integer',
        'ram_gb' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
