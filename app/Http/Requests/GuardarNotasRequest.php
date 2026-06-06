<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarNotasRequest extends FormRequest
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
            'notas' => ['required', 'array', 'min:1'],
            'notas.*.id_inscripcion' => [
                'required',
                'integer',
                'exists:Inscripcion,idInscripcion',
            ],
            'notas.*.nota_asistencia' => [
                'required',
                'numeric',
                'min:0',
                'max:10',
            ],
            'notas.*.nota_final' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'notas.*.estado' => [
                'required',
                'string',
                'in:Aprobado,Reprobado',
            ],
            'notas.*.ecs' => ['required', 'array', 'min:1'],
            'notas.*.ecs.*.id_elemento_competencia' => [
                'required',
                'integer',
                'exists:ElementoCompetencia,id',
            ],
            'notas.*.ecs.*.puntaje' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_grupo_materia_docente.required' => 'El ID del grupo-materia-docente es obligatorio.',
            'id_grupo_materia_docente.exists' => 'El grupo-materia-docente no existe.',
            'notas.required' => 'Debe enviar al menos un estudiante.',
            'notas.*.id_inscripcion.required' => 'Cada estudiante debe tener un id_inscripcion.',
            'notas.*.id_inscripcion.exists' => 'Una inscripción no existe.',
            'notas.*.nota_asistencia.required' => 'Falta la nota de asistencia de un estudiante.',
            'notas.*.nota_asistencia.max' => 'La nota de asistencia no puede superar 10.',
            'notas.*.nota_final.required' => 'Falta la nota final de un estudiante.',
            'notas.*.estado.required' => 'Falta el estado de un estudiante.',
            'notas.*.ecs.*.id_elemento_competencia.exists' => 'Un elemento de competencia no existe.',
            'notas.*.ecs.*.puntaje.required' => 'Falta el puntaje de un EC.',
            'notas.*.ecs.*.puntaje.max' => 'El puntaje de un EC no puede superar 100.',
        ];
    }
}