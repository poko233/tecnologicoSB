<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Cuota;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    /**
     * POST /api/pagos
     * Registrar un nuevo pago (soporta una o múltiples cuotas).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idUsuario' => 'required|exists:user,id',
            'cuotas' => 'required|array|min:1',
            'cuotas.*' => 'required|integer|exists:Cuota,idCuota',
            'metodo' => 'required|string|in:EFECTIVO,TRANSFERENCIA,TARJETA,QR',
            'monto' => 'nullable|numeric|min:0',
            'comprobante' => 'nullable|string|max:80',
            'observacion' => 'nullable|string',
        ]);

        try {
            // Obtener las cuotas correspondientes y verificar pertenencia al usuario
            $cuotas = Cuota::whereIn('idCuota', $validated['cuotas'])
                ->where('idUsuario', $validated['idUsuario'])
                ->get();

            if ($cuotas->count() !== count(array_unique($validated['cuotas']))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una o más cuotas seleccionadas no son válidas o no pertenecen al estudiante.'
                ], 422);
            }

            // Validar que no hayan sido pagadas anteriormente
            foreach ($cuotas as $cuota) {
                if ($cuota->estadoCuota === 'Pagado') {
                    return response()->json([
                        'success' => false,
                        'message' => "La cuota número {$cuota->numeroCuota} (tipo: {$cuota->tipo}) ya ha sido pagada."
                    ], 422);
                }
            }

            // Calcular el monto total a pagar (monto original menos descuentos) si no es especificado
            $montoTotal = $validated['monto'] ?? $cuotas->sum(function ($c) {
                return max(0, $c->monto - $c->descuento);
            });

            // Registrar el pago
            $pago = PagoService::registrarPago(
                $validated['cuotas'],
                (int) $validated['idUsuario'],
                (float) $montoTotal,
                $validated['metodo'],
                $request->user()->id, // Admin autenticado
                $validated['comprobante'] ?? null,
                $validated['observacion'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente.',
                'data' => $pago
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pagos
     * Listar pagos registrados con filtros opcionales.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'idUsuario' => 'nullable|integer|exists:user,id',
            'metodo' => 'nullable|string|in:EFECTIVO,TRANSFERENCIA,TARJETA,QR',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 15);

        $query = Pago::with(['usuario:id,nombres,apellidoPaterno,apellidoMaterno,ci', 'cuotas', 'registradoPor:id,nombres'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('idUsuario')) {
            $query->where('idUsuario', $request->input('idUsuario'));
        }

        if ($request->filled('metodo')) {
            $query->where('metodo', $request->input('metodo'));
        }

        $pagos = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $pagos
        ]);
    }
}
