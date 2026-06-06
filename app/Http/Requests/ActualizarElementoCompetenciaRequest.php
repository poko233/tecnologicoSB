<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarElementoCompetenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:ElementoCompetencia,id',
            ],
            'nombre' => [
                'sometimes',
                'string',
                'max:150',
            ],
            'observaciones' => [
                'sometimes',
                'nullable',
                'string',
                'max:65535',
            ],
            'estado' => [
                'sometimes',
                Rule::in(['activo', 'inactivo']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El ID del elemento de competencia es obligatorio.',
            'id.exists' => 'El elemento de competencia no existe.',
            'nombre.max' => 'El nombre no debe exceder los 150 caracteres.',
            'estado.in' => 'El estado debe ser "activo" o "inactivo".',
        ];
    }
}