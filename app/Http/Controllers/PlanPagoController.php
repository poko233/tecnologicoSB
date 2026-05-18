<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrearPlanPagoRequest;
use App\Services\PlanPagoService;
use Illuminate\Http\Request;

class PlanPagoController extends Controller
{
    /**
     * Listar planes de pago de un estudiante.
     */
    public function index(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:user,id',
        ]);

        $planes = PlanPagoService::listarPorEstudiante($request->usuario_id);

        return response()->json([
            'success' => true,
            'data' => $planes,
        ]);
    }

    /**
     * Crear un nuevo plan de pago.
     */
    public function store(CrearPlanPagoRequest $request)
    {
        try {
            $plan = PlanPagoService::crearPlan(
                $request->usuario_id,
                $request->gestion,
                $request->numero_cuotas,
                $request->monto_cuota,
                $request->boolean('con_matricula_especial', false),
                $request->fecha_inicio,
                $request->monto_matricula_especial,
                $request->monto_cuota_promocion,
                $request->matricula_numero
            );

            return response()->json([
                'success' => true,
                'message' => 'Plan de pagos creado exitosamente',
                'data' => $plan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el plan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un plan de pago.
     */
    public function destroy($id)
    {
        try {
            PlanPagoService::eliminarPlan($id);
            return response()->json([
                'success' => true,
                'message' => 'Plan de pagos eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el plan: ' . $e->getMessage(),
            ], 500);
        }
    }
}