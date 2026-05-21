<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idGrupo = $this->route('grupo');

        return [
            'nombre'   => 'sometimes|required|string|max:50',
            'codigo'   => "sometimes|required|string|max:50|unique:Grupo,codigo,{$idGrupo},idGrupo",
            'paralelo' => 'sometimes|nullable|string|max:50',
            'turno'    => 'sometimes|required|in:Mañana,Tarde,Noche',
            'gestion'  => 'sometimes|required|string|max:20',
            'cupos'    => 'sometimes|required|integer|min:1',
            'tipo'     => 'sometimes|required|in:Capacitacion,Curso',
            'estado'   => 'sometimes|nullable|in:activo,inactivo',
            'horarios' => 'sometimes|nullable|array',
            'horarios.*.horaInicio' => 'required_with:horarios|date_format:H:i',
            'horarios.*.horaFin'    => 'required_with:horarios|date_format:H:i|after:horarios.*.horaInicio',
            'horarios.*.dia'        => 'required_with:horarios|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique'            => 'Ya existe un grupo con ese código.',
            'horarios.*.horaFin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
