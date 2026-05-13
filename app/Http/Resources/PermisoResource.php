<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermisoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'id_rol'       => $this->id_rol,
            'id_formulario'=> $this->id_formulario,
            'ver'          => $this->ver,
            'crear'        => $this->crear,
            'editar'       => $this->editar,
            'eliminar'     => $this->eliminar,
            // Relaciones opcionales
            'rol'          => $this->whenLoaded('rol', fn() => [
                'id'  => $this->rol->id,
                'rol' => $this->rol->rol,
            ]),
            'formulario'   => $this->whenLoaded('formulario', fn() => [
                'id'         => $this->formulario->id,
                'formulario' => $this->formulario->formulario,
                'ruta'       => $this->formulario->ruta,
            ]),
        ];
    }
}
