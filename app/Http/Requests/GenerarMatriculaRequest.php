<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerarMatriculaRequest extends FormRequest
{
    public function authorize()
    {
        // Solo usuarios autenticados con rol de administrador o similar
        return $this->user() !== null && $this->user()->hasAnyRole(['Administrador', 'Personal', 'Rector', 'Director Administrativo', 'Director Academico', 'Fundador']);
    }

    public function rules()
    {
        return [
            'estudiante_id' => 'required|exists:user,id',
            'requiere_pago' => 'required|boolean',
            'monto' => 'required_if:requiere_pago,true|numeric|min:0|max:999999.99',
            'observacion' => 'nullable|string|max:255',
        ];
    }
}