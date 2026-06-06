<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoMateriaDocenteResource extends JsonResource
{
    /**
     * Transforma el recurso en un array para la respuesta JSON.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_grupo_materia_docente' => $this->resource['id_grupo_materia_docente'],
            'grupo' => $this->resource['grupo'],
            'paralelo' => $this->resource['paralelo'],
            'turno' => $this->resource['turno'],
            'gestion' => $this->resource['gestion'],
            'materia' => $this->resource['materia'],
            'inscritos' => $this->resource['inscritos'],
        ];
    }
}