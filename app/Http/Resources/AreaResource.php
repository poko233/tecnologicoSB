<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idArea'       => $this->idArea,
            'nombre'       => $this->nombre,
            'descripccion' => $this->descripccion,
            'estado'       => $this->estado,
            'creadoEn'     => $this->created_at?->toDateTimeString(),
            'actualizadoEn' => $this->updated_at?->toDateTimeString(),
        ];
    }
}