<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanillaRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'id_grupo_materia_docente.required' => 'El ID del grupo-materia-docente es obligatorio.',
            'id_grupo_materia_docente.exists' => 'El grupo-materia-docente no existe.',
        ];
    }
}