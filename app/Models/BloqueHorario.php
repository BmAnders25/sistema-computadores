<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BloqueHorario extends Model
{
    protected $table = 'bloques_horarios';
    protected $fillable = ['nombre', 'hora_inicio', 'hora_fin', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function inicioEn(string $fecha): Carbon
    {
        return Carbon::parse("{$fecha} {$this->hora_inicio}");
    }
}
