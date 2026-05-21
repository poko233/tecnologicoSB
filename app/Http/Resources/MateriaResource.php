<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MateriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idMateria'      => $this->idMateria,
            'nombreMateria'  => $this->nombreMateria,
            'codigo'         => $this->codigo,
            'semestre'       => $this->semestre,
            'estado'         => $this->estado,
            'idPrerequisito' => $this->idPrerequisito,
            'prerequisito'   => $this->whenLoaded('prerequisito', fn() => [
                'idMateria'     => $this->prerequisito->idMateria,
                'nombreMateria' => $this->prerequisito->nombreMateria,
            ]),
            'carreras'       => CarreraResource::collection($this->whenLoaded('carreras')),
            'creadoEn'       => $this->created_at?->toDateTimeString(),
            'actualizadoEn'  => $this->updated_at?->toDateTimeString(),
        ];
    }
}