<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Cuota;
use Carbon\Carbon;

class PagoService
{
    /**
     * Registrar un pago para una cuota.
     *
     * @param int $cuotaId
     * @param int $usuarioId (estudiante)
     * @param float $monto
     * @param string $metodo (EFECTIVO, TRANSFERENCIA, TARJETA, QR)
     * @param int $registradoPor (ID del admin)
     * @param string|null $comprobante
     * @param string|null $observacion
     * @return Pago
     */
    public static function registrarPago(
        int $cuotaId,
        int $usuarioId,
        float $monto,
        string $metodo,
        int $registradoPor,
        ?string $comprobante = null,
        ?string $observacion = null
    ): Pago {
        // Crear el pago
        $pago = Pago::create([
            'idCuota' => $cuotaId,
            'idUsuario' => $usuarioId,
            'monto' => $monto,
            'metodo' => $metodo,
            'comprobante' => $comprobante,
            'observacion' => $observacion,
            'registrado_por' => $registradoPor,
        ]);

        // Actualizar estado de la cuota a Pagado y registrar fecha de pago
        Cuota::where('idCuota', $cuotaId)->update([
            'estadoCuota' => 'Pagado',
            'fecha_pago' => Carbon::now(),
        ]);

        return $pago;
    }
}