<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'   => 'required|string|max:50',
            'codigo'   => 'required|string|max:50',
            'paralelo' => 'nullable|string|max:50',
            'turno'    => 'required|in:Mañana,Tarde,Noche',
            'gestion'  => 'required|string|max:20',
            'cupos'    => 'required|integer|min:1',
            'tipo'     => 'required|in:Capacitacion,Curso',
            'estado'   => 'nullable|in:activo,inactivo',
            'horarios' => 'nullable|array',
            'horarios.*.horaInicio' => 'required_with:horarios|date_format:H:i',
            'horarios.*.horaFin'    => 'required_with:horarios|date_format:H:i|after:horarios.*.horaInicio',
            'horarios.*.dia'        => 'required_with:horarios|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'  => 'El nombre del grupo es obligatorio.',
            'codigo.required'  => 'El código del grupo es obligatorio.',
            'codigo.unique'    => 'Ya existe un grupo con ese código.',
            'turno.in'         => 'El turno seleccionado no es válido (Mañana, Tarde, Noche).',
            'cupos.min'        => 'Los cupos deben ser al menos 1.',
            'tipo.in'          => 'El tipo debe ser Capacitacion o Curso.',
            'horarios.*.horaFin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
