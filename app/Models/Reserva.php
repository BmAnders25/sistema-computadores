<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Reserva extends Model
{
    public const ACTIVAS = ['pendiente', 'entregada'];

    protected $fillable = [
        'user_id', 'computador_id', 'aula_id', 'bloque_horario_id', 'fecha',
        'estado', 'entregado_at', 'devuelto_at', 'clave_unica',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'entregado_at' => 'datetime',
        'devuelto_at'  => 'datetime',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function computador() { return $this->belongsTo(Computador::class); }
    public function aula()       { return $this->belongsTo(Aula::class); }
    public function bloque()     { return $this->belongsTo(BloqueHorario::class, 'bloque_horario_id'); }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', self::ACTIVAS);
    }

    public function esDeAula(): bool
    {
        return $this->aula_id !== null;
    }

    /** Cierra la reserva y libera su clave única. */
    public function liberar(string $estado): void
    {
        $this->update(['estado' => $estado, 'clave_unica' => null]);
    }

    public static function claveEquipo(int $id, string $fecha, int $bloque): string
    {
        return "pc:{$id}:{$fecha}:{$bloque}";
    }

    public static function claveAula(int $id, string $fecha, int $bloque): string
    {
        return "aula:{$id}:{$fecha}:{$bloque}";
    }
}
