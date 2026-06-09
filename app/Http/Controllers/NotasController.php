<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GuardarNotasRequest;
use App\Http\Requests\PlanillaRequest;
use App\Http\Resources\GrupoMateriaDocenteResource;
use App\Http\Resources\PlanillaResource;
use App\Services\GrupoMateriaDocenteService;
use App\Services\NotaFinalService;
use App\Services\PlanillaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotasController extends Controller
{
    public function __construct(
        protected GrupoMateriaDocenteService $grupoService,
        protected PlanillaService $planillaService,
        protected NotaFinalService $notaFinalService
    ) {
    }

    /**
     * GET /api/notas/mis-grupos
     *
     * Devuelve los grupos activos asignados al docente autenticado
     * junto con el número de estudiantes inscritos.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function misGrupos(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('Administrador')) {
            $grupos = $this->grupoService->getTodosLosGrupos();
        } else {
            $grupos = $this->grupoService->getGruposDelDocente($user->id);
        }

        return response()->json([
            'data' => GrupoMateriaDocenteResource::collection($grupos),
            'message' => 'Success',
        ], 200);
    }

    /**
     * POST /api/planilla
     */
    public function planilla(PlanillaRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $idGrupoMateriaDocente = (int) $request->validated('id_grupo_materia_docente');

        $datos = $this->planillaService->obtenerPlanilla($idGrupoMateriaDocente, $userId);

        return response()->json([
            'data' => new PlanillaResource($datos),
            'message' => 'Success',
        ], 200);
    }



    /**
     * POST /api/planilla/guardar
     */
    public function guardarNotas(GuardarNotasRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $idGrupoMateriaDocente = (int) $request->validated('id_grupo_materia_docente');
        $notas = $request->validated('notas');

        $resultado = $this->notaFinalService->guardarNotas(
            $idGrupoMateriaDocente,
            $notas,
            $userId
        );

        $mensaje = $resultado['procesados'] . ' estudiante(s) procesados.';
        if (!empty($resultado['errores'])) {
            $mensaje .= ' Con errores: ' . implode('; ', $resultado['errores']);
        }

        return response()->json([
            'message' => $mensaje,
            'procesados' => $resultado['procesados'],
            'errores' => $resultado['errores'],
        ], 200);
    }
}