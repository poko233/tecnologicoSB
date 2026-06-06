<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementoCompetenciaResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para la respuesta JSON.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_grupo_materia_docente' => $this->id_grupo_materia_docente,
            'nombre' => $this->nombre,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}