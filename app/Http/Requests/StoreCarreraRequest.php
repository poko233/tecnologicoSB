<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El middleware ya protege el endpoint
    }

    public function rules(): array
    {
        return [
            'nombreCarrera'                  => 'required|string|max:50',
            'codigo'                         => 'required|string|max:50|unique:Carrera,codigo',
            'duracion'                       => 'required|integer|min:1',
            'cargaHoraria'                   => 'required|string|max:50',
            'costo'                          => 'required|numeric|min:0',
            'denominacionTitutloProfesional' => 'required|string',
            'estadoCarrera'                  => 'in:activo,inactivo',
            'idArea'                         => 'required|exists:Area,idArea',
        ];
    }

    public function messages(): array
    {
        return [
            'nombreCarrera.required'                  => 'El nombre de la carrera es obligatorio.',
            'codigo.unique'                            => 'Ya existe una carrera con ese código.',
            'costo.numeric'                            => 'El costo debe ser un número.',
            'idArea.exists'                            => 'El área seleccionada no existe.',
            'denominacionTitutloProfesional.required'  => 'La denominación del título es obligatoria.',
        ];
    }
}