<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\CodigoVerificacionMail;
use Illuminate\Support\Facades\Mail;
class TwoFactorService
{

    public function generar(User $user): string {
    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    $user->forceFill([
        'two_factor_code'       => Hash::make($codigo),
        'two_factor_expires_at' => now()->addMinutes(5),
    ])->save();

    Mail::to($user->email)->send(new CodigoVerificacionMail($user->name, $codigo));

    return $codigo;
}

    public function verificar(User $user, string $codigo): bool
    {
        if (!$user->two_factor_code || !$user->two_factor_expires_at || !$user->two_factor_expires_at->isFuture()) {
            return false;
        }

        if (!Hash::check($codigo, $user->two_factor_code)) {
            return false;
        }

        $user->forceFill(['two_factor_code' => null, 'two_factor_expires_at' => null])->save();
        return true;
    }
}