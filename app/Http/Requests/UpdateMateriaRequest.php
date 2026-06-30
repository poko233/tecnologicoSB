<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idMateria = $this->route('materia');
        return [
            'nombreMateria'  => 'required|string|max:50',
            'codigo'         => 'required|string|max:50' . $idMateria . ',idMateria',
            'semestre'       => 'required|integer|min:1|max:12',
            'estado'         => 'nullable|in:activo,inactivo',
            'idPrerequisito' => 'nullable|exists:Materia,idMateria',
            'idCarrera'      => 'nullable|exists:Carrera,idCarrera',
        ];
    }
}