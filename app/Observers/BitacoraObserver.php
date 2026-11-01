<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Bitacora;

class BitacoraObserver
{
    public function created($model)
    {
        Log::info('Observer ejecutado - CREATED para modelo: ' . class_basename($model));
        $this->log('creó', $model);
    }

    public function updated($model)
    {
        Log::info('Observer ejecutado - UPDATED para modelo: ' . class_basename($model));

        $detalles = $this->getChangesDetail($model);
        $this->log('actualizó', $model, $detalles);
    }

    public function deleted($model)
    {
        Log::info('Observer ejecutado - DELETED para modelo: ' . class_basename($model));
        $this->log('eliminó', $model);
    }

    protected function log($accion, $model, $detalles = null)
    {
        try {
            Bitacora::create([
                'fecha'   => now()->toDateString(),
                'hora'    => now()->toTimeString(),
                'usuario' => Auth::check() ? Auth::user()->name : 'Sistema',
                'accion'  => 'El usuario ' . (Auth::check() ? Auth::user()->name : 'Sistema') .
                             " $accion un registro en " . class_basename($model) .
                             " (ID: " . ($model->id ?? 'sin ID') . ")" .
                             ($detalles ? " con cambios: $detalles" : '')
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar en Bitácora: ' . $e->getMessage());
        }
    }

    protected function getChangesDetail($model)
    {
        $detalles = [];

        foreach ($model->getChanges() as $campo => $nuevoValor) {
            if ($campo === 'updated_at') continue;

            $valorAnterior = $model->getOriginal($campo);
            $detalles[] = "$campo: '$valorAnterior' → '$nuevoValor'";
        }

        return implode('; ', $detalles);
    }
}
