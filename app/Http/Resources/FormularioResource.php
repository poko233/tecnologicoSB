<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FormularioResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'formulario'  => $this->formulario,
            'descripcion' => $this->descripcion,
            'ruta'        => $this->ruta,
            'componente'  => $this->componente,
            'icono'       => $this->icono,
            'orden'       => $this->orden,
        ];
    }
}