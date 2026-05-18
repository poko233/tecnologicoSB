<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearPlanPagoRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() !== null && $this->user()->hasAnyRole([
            'Administrador',
            'Personal',
            'Rector',
            'Director Administrativo',
            'Director Academico',
            'Fundador'
        ]);
    }

    public function rules()
    {
        return [
            'usuario_id' => 'required|exists:user,id',
            'gestion' => 'required|integer|min:2000|max:2100',
            'numero_cuotas' => 'required|integer|min:1|max:36',
            'monto_cuota' => 'required|numeric|min:0|max:999999.99',
            'monto_cuota_promocion' => 'nullable|numeric|min:0|max:999999.99',
            'matricula_numero' => 'nullable|string|max:15',
            'con_matricula_especial' => 'boolean',
            'monto_matricula_especial' => 'nullable|numeric|min:0|max:999999.99',
            'fecha_inicio' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'usuario_id.required' => 'El ID del estudiante es obligatorio.',
            'usuario_id.exists' => 'El estudiante no existe.',
            'numero_cuotas.min' => 'Debe haber al menos 1 cuota.',
        ];
    }
}