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
        $idCarrera = $this->route('carrera');

        return [
            'nombreCarrera'                  => 'sometimes|required|string|max:50',
            'codigo'                         => "sometimes|required|string|max:50,{$idCarrera},idCarrera",
            'tipo'                           => 'sometimes|nullable|string|max:20',
            'regimen'                        => 'sometimes|nullable|in:Anual,Semestral,Mensual,Otro',
            'duracion'                       => 'sometimes|nullable|integer|min:1',
            'duracion_meses'                 => 'sometimes|nullable|integer|min:0',
            'cargaHoraria'                   => 'sometimes|required|string|max:50',
            'costo_matricula'                => 'sometimes|nullable|numeric|min:0',
            'denominacionTitutloProfesional' => 'sometimes|required|string',
            'cuota_mensual'                  => 'sometimes|nullable|numeric|min:0',
            'cuotas_por_anio'                => 'sometimes|nullable|integer|min:1',
            'estadoCarrera'                  => 'sometimes|nullable|in:activo,inactivo',
            'idArea'                         => 'sometimes|required|exists:Area,idArea',
        ];
    }
}