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
            'tipo'                           => $this->tipo,
            'regimen'                        => $this->regimen,
            'duracion'                       => $this->duracion,
            'duracion_meses'                 => $this->duracion_meses,
            'cargaHoraria'                   => $this->cargaHoraria,
            'costo'                          => (float) (
                                                    (($this->costo_matricula ?? 0) * ($this->duracion ?? 0)) +
                                                    (($this->duracion_meses ?? 0) * ($this->cuota_mensual ?? 0))
                                                ),
            'costo_matricula'                => (float) $this->costo_matricula,
            'denominacionTitutloProfesional' => $this->denominacionTitutloProfesional,
            'cuota_mensual'                  => (float) $this->cuota_mensual,
            'cuotas_por_anio'                => $this->cuotas_por_anio,
            'estadoCarrera'                  => $this->estadoCarrera,
            'area'                           => $this->whenLoaded('area', fn() => [
                'idArea' => $this->area->idArea,
                'nombre' => $this->area->nombre,
            ]),
            'creadoEn'    => $this->created_at?->toDateTimeString(),
            'actualizadoEn' => $this->updated_at?->toDateTimeString(),
        ];
    }
}