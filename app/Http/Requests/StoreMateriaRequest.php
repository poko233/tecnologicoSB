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
            'nombreMateria'  => 'required|string|max:50',
            'codigo'         => 'required|string|max:50|unique:Materia,codigo',
            'semestre'       => 'required|integer|min:1|max:12',
            'estado'         => 'nullable|in:activo,inactivo',
            'idPrerequisito' => 'nullable|exists:Materia,idMateria',
            'idCarrera'      => 'required|exists:Carrera,idCarrera',
        ];
    }
}