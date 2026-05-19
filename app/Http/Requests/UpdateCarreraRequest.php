<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idCarrera = $this->route('carrera'); // nombre del parámetro en la ruta

        return [
            'nombreCarrera'                  => 'sometimes|required|string|max:50',
            'codigo'                         => "sometimes|required|string|max:50|unique:Carrera,codigo,{$idCarrera},idCarrera",
            'duracion'                       => 'sometimes|required|integer|min:1',
            'cargaHoraria'                   => 'sometimes|required|string|max:50',
            'costo'                          => 'sometimes|required|numeric|min:0',
            'denominacionTitutloProfesional' => 'sometimes|required|string',
            'estadoCarrera'                  => 'sometimes|in:activo,inactivo',
            'idArea'                         => 'sometimes|required|exists:Area,idArea',
        ];
    }
}