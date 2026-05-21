<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HorarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idHorario'     => $this->idHorario,
            'horaInicio'    => $this->horaInicio,
            'horaFin'       => $this->horaFin,
            'dia'           => $this->dia,
            'creadoEn'      => $this->created_at?->toDateTimeString(),
            'actualizadoEn' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
