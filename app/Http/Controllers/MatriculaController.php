<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerarMatriculaRequest;
use App\Services\MatriculaService;
use Illuminate\Http\JsonResponse;

class MatriculaController extends Controller
{
    /**
     * Generar matrícula para un estudiante.
     *
     * @param GenerarMatriculaRequest $request
     * @return JsonResponse
     */
    public function generar(GenerarMatriculaRequest $request): JsonResponse
    {
        try {
            $resultado = MatriculaService::generarMatricula(
                $request->estudiante_id,
                $request->requiere_pago,
                (float) $request->monto,
                $request->observacion,
                $request->user()->id // admin autenticado
            );

            return response()->json([
                'success' => true,
                'message' => 'Matrícula generada exitosamente',
                'data' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar matrícula: ' . $e->getMessage(),
            ], 500);
        }
    }
}