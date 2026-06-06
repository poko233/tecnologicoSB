<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearElementoCompetenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_grupo_materia_docente' => [
                'required',
                'integer',
                'exists:GrupoMateriaDocente,idGrupoMateriaDocente',
            ],
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:65535',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_grupo_materia_docente.required' => 'El ID del grupo-materia-docente es obligatorio.',
            'id_grupo_materia_docente.exists' => 'El grupo-materia-docente no existe.',
            'nombre.required' => 'El nombre del elemento de competencia es obligatorio.',
            'nombre.max' => 'El nombre no debe exceder los 150 caracteres.',
        ];
    }
}