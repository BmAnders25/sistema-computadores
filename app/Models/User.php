<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'theme',
        'codigo_institucional', 'programa', 'estado', 'two_factor_enabled',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_code',
    ];

    protected $casts = [
        'email_verified_at'     => 'datetime',
        'password'              => 'hashed',
        'two_factor_enabled'    => 'boolean',
        'two_factor_expires_at' => 'datetime',
    ];

    public function esActivo(): bool
    {
        return $this->estado === 'activo';
    }
    public function esInstitucional(): bool
{
    return in_array(
        strtolower(\Illuminate\Support\Str::after($this->email, '@')),
        config('institucional.dominios', []),
        true
    );
}

    public function requiereDosPasos(): bool{
    return $this->esInstitucional() || $this->two_factor_enabled;
    }

    /*public function reservas()
    {
        return $this->hasMany(Reserva::class); 
}*/
}