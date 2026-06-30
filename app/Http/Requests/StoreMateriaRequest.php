<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombreMateria'  => 'required|string|max:255',
            'codigo'         => 'required|string|max:50',
            'semestre'       => 'required|integer|min:1|max:12',
            'estado'         => 'nullable|in:activo,inactivo',
            'idPrerequisito' => 'nullable|exists:Materia,idMateria',
            'idCarrera'      => 'required|exists:Carrera,idCarrera',
        ];
    }

    public function messages(): array
    {
        return [
            'nombreMateria.required' => 'El nombre es obligatorio',
            'nombreMateria.max'      => 'El nombre no puede superar los 255 caracteres',
            'codigo.required'        => 'El código es obligatorio',
            'codigo.max'             => 'El código no puede superar los 50 caracteres',
            'semestre.required'      => 'El semestre es obligatorio',
            'semestre.min'           => 'El semestre debe ser al menos 1',
            'semestre.max'           => 'El semestre no puede ser mayor a 12',
            'idPrerequisito.exists'  => 'El prerrequisito seleccionado no existe',
            'idCarrera.required'     => 'La carrera es obligatoria',
            'idCarrera.exists'       => 'La carrera seleccionada no existe',
        ];
    }
}