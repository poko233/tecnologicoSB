<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarElementoCompetenciaRequest;
use App\Http\Requests\CrearElementoCompetenciaRequest;
use App\Http\Requests\ListarElementoCompetenciaRequest;
use App\Http\Resources\ElementoCompetenciaResource;
use App\Services\ElementoCompetenciaService;
use Illuminate\Http\JsonResponse;

class ElementoCompetenciaController extends Controller
{
    public function __construct(
        protected ElementoCompetenciaService $ecService
    ) {
    }

    /**
     * POST /api/elementos-competencia/listar
     */
    public function listar(ListarElementoCompetenciaRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $idGrupoMateriaDocente = (int) $request->validated('id_grupo_materia_docente');

        $elementos = $this->ecService->listar($idGrupoMateriaDocente, $userId);

        return response()->json([
            'data' => ElementoCompetenciaResource::collection($elementos),
            'message' => 'Success',
        ], 200);
    }

    /**
     * POST /api/elementos-competencia/crear
     */
    public function crear(CrearElementoCompetenciaRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        $ec = $this->ecService->crear($data, $userId);

        return response()->json([
            'data' => new ElementoCompetenciaResource($ec),
            'message' => 'Elemento de competencia creado correctamente.',
        ], 201);
    }

    /**
     * POST /api/elementos-competencia/actualizar
     */
    public function actualizar(ActualizarElementoCompetenciaRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        $ec = $this->ecService->actualizar($data, $userId);

        return response()->json([
            'data' => new ElementoCompetenciaResource($ec),
            'message' => 'Elemento de competencia actualizado correctamente.',
        ], 200);
    }
}