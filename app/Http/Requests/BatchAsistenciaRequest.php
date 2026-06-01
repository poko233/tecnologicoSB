<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización de rol se maneja en el middleware
    }

    public function rules(): array
    {
        return [
            'asistencias' => 'required|array|min:1|max:500',
            'asistencias.*.id_inscripcion' => 'required|integer|exists:Inscripcion,idInscripcion',
            'asistencias.*.id_lista_asistencia' => 'required|integer|exists:ListaAsistencia,idListaAsistencia',
            'asistencias.*.tipo' => 'required|string|in:Presente,Permiso,Falta,Atraso',
            'asistencias.*.observacion' => 'nullable|string|max:255',
            'asistencias.*.fecha' => 'nullable|date_format:Y-m-d',
            'asistencias.*.idHorario' => 'nullable|integer|exists:Horario,idHorario',
        ];
    }

    public function messages(): array
    {
        return [
            'asistencias.required' => 'El campo asistencias es obligatorio.',
            'asistencias.array' => 'El campo asistencias debe ser un arreglo.',
            'asistencias.min' => 'Debe enviar al menos un registro de asistencia.',
            'asistencias.max' => 'No puede enviar más de 500 registros a la vez.',
            'asistencias.*.id_inscripcion.required' => 'El ID de inscripción es obligatorio.',
            'asistencias.*.id_inscripcion.exists' => 'La inscripción no existe.',
            'asistencias.*.id_lista_asistencia.required' => 'El ID de la lista de asistencia es obligatorio.',
            'asistencias.*.id_lista_asistencia.exists' => 'La lista de asistencia no existe.',
            'asistencias.*.tipo.required' => 'El tipo de asistencia es obligatorio.',
            'asistencias.*.tipo.in' => 'El tipo debe ser Presente, Permiso, Falta o Atraso.',
            'asistencias.*.fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'asistencias.*.idHorario.exists' => 'El horario seleccionado no existe.',
        ];
    }
}