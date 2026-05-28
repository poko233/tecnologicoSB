<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HorarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idHorario' => $this->idHorario,
            'horaInicio' => substr((string) $this->horaInicio, 0, 5),
            'horaFin' => substr((string) $this->horaFin, 0, 5),
            'dia' => $this->dia,
            'creadoEn' => $this->created_at?->toDateTimeString(),
            'actualizadoEn' => $this->updated_at?->toDateTimeString(),
        ];
    }
}