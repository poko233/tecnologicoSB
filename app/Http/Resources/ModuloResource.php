<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModuloResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'modulo'      => $this->modulo,
            'descripcion' => $this->descripcion,
            'icono'       => $this->icono,
            'formularios' => FormularioResource::collection(
                $this->whenLoaded('formularios')
            ),
        ];
    }
}
