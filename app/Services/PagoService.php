<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Cuota;
use Carbon\Carbon;

class PagoService
{
    /**
     * Registrar un pago para una o varias cuotas.
     *
     * @param int|array $cuotaIds
     * @param int $usuarioId (estudiante)
     * @param float $monto
     * @param string $metodo (EFECTIVO, TRANSFERENCIA, TARJETA, QR)
     * @param int $registradoPor (ID del admin)
     * @param string|null $comprobante
     * @param string|null $observacion
     * @return Pago
     */
    public static function registrarPago(
        $cuotaIds,
        int $usuarioId,
        float $monto,
        string $metodo,
        int $registradoPor,
        ?string $comprobante = null,
        ?string $observacion = null
    ): Pago {
        $ids = (array) $cuotaIds;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($ids, $usuarioId, $monto, $metodo, $registradoPor, $comprobante, $observacion) {
            // Crear el pago
            $pago = Pago::create([
                'idUsuario' => $usuarioId,
                'monto' => $monto,
                'metodo' => $metodo,
                'comprobante' => $comprobante,
                'observacion' => $observacion,
                'registrado_por' => $registradoPor,
            ]);

            // Obtener las cuotas correspondientes
            $cuotas = Cuota::whereIn('idCuota', $ids)->get();

            foreach ($cuotas as $cuota) {
                // Actualizar estado de la cuota a Pagado y registrar fecha de pago
                $cuota->update([
                    'estadoCuota' => 'Pagado',
                    'fecha_pago' => Carbon::now(),
                ]);

                // Registrar en la tabla pivote pago_cuota
                $pago->cuotas()->attach($cuota->idCuota, [
                    'monto_pagado' => $cuota->monto,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            return $pago->load('cuotas');
        });
    }
}