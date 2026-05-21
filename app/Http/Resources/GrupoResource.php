<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idGrupo'        => $this->idGrupo,
            'nombre'         => $this->nombre,
            'codigo'         => $this->codigo,
            'paralelo'       => $this->paralelo,
            'turno'          => $this->turno,
            'gestion'        => $this->gestion,
            'cupos'          => $this->cupos,
            'tipo'           => $this->tipo,
            'estado'         => $this->estado,
            'horarios'       => $this->whenLoaded('horarios', fn() => $this->horarios->map(fn($horario) => [
                'idHorario'  => $horario->idHorario,
                'horaInicio' => $horario->horaInicio,
                'horaFin'    => $horario->horaFin,
                'dia'        => $horario->dia,
            ])),
            'creadoEn'       => $this->created_at?->toDateTimeString(),
            'actualizadoEn'  => $this->updated_at?->toDateTimeString(),
        ];
    }
}
