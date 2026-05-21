<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'nombreCarrera'                  => 'required|string|max:50',
            'codigo'                         => 'required|string|max:50|unique:Carrera,codigo',
            'tipo'                           => 'nullable|string|max:20',
            'regimen'                        => 'nullable|in:Anual,Semestral,Mensual,Otro',
            'duracion'                       => 'nullable|integer|min:1',
            'duracion_meses'                 => 'nullable|integer|min:0',
            'cargaHoraria'                   => 'required|string|max:50',
            'costo_matricula'                => 'nullable|numeric|min:0',
            'denominacionTitutloProfesional' => 'required|string',
            'cuota_mensual'                  => 'nullable|numeric|min:0',
            'cuotas_por_anio'                => 'nullable|integer|min:1',
            'estadoCarrera'                  => 'nullable|in:activo,inactivo',
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