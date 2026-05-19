<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarreraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idCarrera'                      => $this->idCarrera,
            'nombreCarrera'                  => $this->nombreCarrera,
            'codigo'                         => $this->codigo,
            'duracion'                       => $this->duracion,
            'cargaHoraria'                   => $this->cargaHoraria,
            'costo'                          => (float) $this->costo,
            'denominacionTitutloProfesional' => $this->denominacionTitutloProfesional,
            'estadoCarrera'                  => $this->estadoCarrera,
            'area'                           => $this->whenLoaded('area', fn() => [
                'idArea' => $this->area->idArea,
                'nombre' => $this->area->nombre,
            ]),
            'totalMaterias'                  => $this->when(
                $this->relationLoaded('materias'),
                fn() => $this->materias->count()
            ),
            'creadoEn'                       => $this->created_at?->toDateTimeString(),
            'actualizadoEn'                  => $this->updated_at?->toDateTimeString(),
        ];
    }
}