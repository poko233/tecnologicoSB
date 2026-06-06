<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanillaResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     */
    public function toArray(Request $request): array
    {
        return [
            'grupo' => $this->resource['grupo'],
            'materia' => $this->resource['materia'],
            'elementos_competencia' => $this->resource['elementos_competencia'],
            'estudiantes' => $this->resource['estudiantes'],
        ];
    }
}