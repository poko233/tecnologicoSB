<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarElementoCompetenciaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja por middleware y en el servicio
    }

    /**
     * Reglas de validación para listar elementos de competencia.
     */
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

    /**
     * Mensajes personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'id_grupo_materia_docente.required' => 'El campo id_grupo_materia_docente es obligatorio.',
            'id_grupo_materia_docente.integer' => 'El campo id_grupo_materia_docente debe ser un número entero.',
            'id_grupo_materia_docente.exists' => 'El grupo-materia-docente seleccionado no existe.',
        ];
    }
}