<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class ReporteOcupacionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'desde' => $this->input('desde', now()->subDays(29)->toDateString()),
            'hasta' => $this->input('hasta', now()->toDateString()),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'aula_id' => ['nullable', 'integer', 'exists:aulas,id'],
            'estado' => ['nullable', 'in:pendiente,confirmada,entregada,devuelta,cancelada,no_show'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->has('desde') && $this->has('hasta') && is_string($this->input('desde')) && is_string($this->input('hasta'))) {
                    try {
                        $desde = Carbon::parse($this->input('desde'))->startOfDay();
                        $hasta = Carbon::parse($this->input('hasta'))->startOfDay();

                        if ($desde->lte($hasta) && $desde->diffInDays($hasta) + 1 > 366) {
                            $validator->errors()->add('hasta', 'El rango seleccionado no puede superar 366 días.');
                        }
                    } catch (\Throwable) {
                        // La regla date maneja formatos inválidos.
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'desde.required' => 'La fecha inicial es obligatoria.',
            'desde.date' => 'La fecha inicial no es válida.',
            'hasta.required' => 'La fecha final es obligatoria.',
            'hasta.date' => 'La fecha final no es válida.',
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'aula_id.exists' => 'El aula seleccionada no existe.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
