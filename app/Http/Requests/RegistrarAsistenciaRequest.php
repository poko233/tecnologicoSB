<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_inscripcion' => 'required|integer|exists:Inscripcion,idInscripcion',
            'id_lista_asistencia' => 'required|integer|exists:ListaAsistencia,idListaAsistencia',
            'tipo' => 'required|string|in:Presente,Permiso,Falta,Atraso',
            'observacion' => 'nullable|string|max:255',
            'fecha' => 'nullable|date_format:Y-m-d',
            'idHorario' => 'nullable|integer|exists:Horario,idHorario',
        ];
    }

    public function messages(): array
    {
        return [
            'id_inscripcion.required' => 'El ID de la inscripción es obligatorio.',
            'id_inscripcion.exists' => 'La inscripción no existe.',
            'id_lista_asistencia.required' => 'El ID de la lista de asistencia es obligatorio.',
            'id_lista_asistencia.exists' => 'La lista de asistencia no existe.',
            'tipo.required' => 'El tipo de asistencia es obligatorio.',
            'tipo.in' => 'El tipo debe ser Presente, Permiso, Falta o Atraso.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'idHorario.exists' => 'El horario seleccionado no existe.',
        ];
    }
}