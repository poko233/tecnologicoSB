<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\PlanPago;
use Carbon\Carbon;

class CuotaService
{
    /**
     * Crear una cuota para un plan de pago o directamente para un usuario.
     *
     * @param int|null $planPagoId
     * @param int $usuarioId
     * @param string $tipo (MATRICULA o MENSUAL)
     * @param float $monto
     * @param string $numeroCuota
     * @param string|null $fechaVencimiento (Y-m-d)
     * @param float $descuento
     * @return Cuota
     */
    public static function crearCuota(
        ?int $planPagoId,
        int $usuarioId,
        string $tipo,
        float $monto,
        string $numeroCuota,
        ?string $fechaVencimiento = null,
        float $descuento = 0.0
    ): Cuota {
        return Cuota::create([
            'idPlanPago' => $planPagoId,
            'idUsuario' => $usuarioId,
            'tipo' => $tipo,
            'monto' => $monto,
            'numeroCuota' => $numeroCuota,
            'fecha_vencimiento' => $fechaVencimiento ? Carbon::parse($fechaVencimiento) : null,
            'descuento' => $descuento,
            'estadoCuota' => 'Debe',
        ]);
    }
}