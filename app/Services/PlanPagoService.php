<?php

namespace App\Services;

use App\Models\PlanPago;
use App\Models\User;
use App\Models\Cuota;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanPagoService
{
    /**
     * Listar planes de pago de un estudiante con resumen de cuotas.
     *
     * @param int $usuarioId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function listarPorEstudiante(int $usuarioId)
    {
        $planes = PlanPago::with(['cuotas.pago'])
            ->where('idUsuario', $usuarioId)
            ->orderBy('gestion', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Añadir resumen calculado a cada plan
        $planes->each(function ($plan) {
            $totalCuotas = $plan->cuotas->count();
            $cuotasPagadas = $plan->cuotas->where('estadoCuota', 'Pagado')->count();
            $montoTotal = $plan->cuotas->sum('monto');
            $montoPagado = $plan->cuotas->filter(function ($cuota) {
                return $cuota->estadoCuota === 'Pagado';
            })->sum('monto');
            $plan->resumen = [
                'total_cuotas' => $totalCuotas,
                'cuotas_pagadas' => $cuotasPagadas,
                'monto_total' => $montoTotal,
                'monto_pagado' => $montoPagado,
                'porcentaje_pagado' => $totalCuotas > 0 ? round(($cuotasPagadas / $totalCuotas) * 100, 2) : 0,
            ];
        });

        return $planes;
    }

    /**
     * Crear un nuevo plan de pago para un estudiante.
     *
     * @param int $usuarioId
     * @param int $gestion
     * @param int $numeroCuotas
     * @param float $montoCuota
     * @param bool $conMatriculaEspecial
     * @param string|null $fechaInicio (Y-m-d)
     * @param float|null $montoMatriculaEspecial (opcional, si es null usa el mismo $montoCuota)
     * @param float|null $montoCuotaPromocion (opcional, monto de la primera cuota mensual)
     * @param string|null $matriculaNumero (opcional, número de matrícula)
     * @return PlanPago
     * @throws ValidationException
     */
    public static function crearPlan(
        int $usuarioId,
        int $gestion,
        int $numeroCuotas,
        float $montoCuota,
        bool $conMatriculaEspecial = false,
        ?string $fechaInicio = null,
        ?float $montoMatriculaEspecial = null,
        ?float $montoCuotaPromocion = null,
        ?string $matriculaNumero = null
    ): PlanPago {
        $estudiante = User::findOrFail($usuarioId);

        if ($numeroCuotas < 1) {
            throw ValidationException::withMessages(['numero_cuotas' => 'El número de cuotas debe ser al menos 1.']);
        }

        $fechaInicioCarbon = $fechaInicio ? Carbon::parse($fechaInicio) : Carbon::now();

        DB::beginTransaction();
        try {
            // Crear el plan con los campos adicionales
            $plan = PlanPago::create([
                'idUsuario' => $usuarioId,
                'gestion' => $gestion,
                'matricula_economica' => $conMatriculaEspecial ? ($montoMatriculaEspecial ?? $montoCuota) : 0,
                'numero_cuotas' => $numeroCuotas,
                'monto_cuota_normal' => $montoCuota,
                'monto_cuota_promocion' => $montoCuotaPromocion ?? 0,
                'matricula_numero' => $matriculaNumero,
                'estado' => 'activo',
            ]);

            // Crear cuotas mensuales
            for ($i = 1; $i <= $numeroCuotas; $i++) {
                // Determinar el monto de esta cuota: si es la primera y hay promoción, usarla
                $montoActual = ($i === 1 && $montoCuotaPromocion && $montoCuotaPromocion > 0)
                    ? $montoCuotaPromocion
                    : $montoCuota;

                $numeroCuotaStr = $i . '/' . $numeroCuotas;
                $fechaVencimiento = $fechaInicioCarbon->copy()->addMonths($i - 1);
                self::crearCuotaParaPlan(
                    $plan->id,
                    $usuarioId,
                    'MENSUAL',
                    $montoActual,
                    $numeroCuotaStr,
                    $fechaVencimiento
                );
            }

            // Si hay matrícula especial, agregar cuota extra de tipo MATRICULA
            if ($conMatriculaEspecial) {
                self::crearCuotaParaPlan(
                    $plan->id,
                    $usuarioId,
                    'MATRICULA',
                    $montoMatriculaEspecial ?? $montoCuota,
                    'MATRICULA',
                    $fechaInicioCarbon->copy()->addDays(30)
                );
            }

            DB::commit();
            return $plan->load('cuotas');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar un plan de pago (y sus cuotas/pagos por cascade).
     *
     * @param int $planId
     * @return bool
     */
    public static function eliminarPlan(int $planId): bool
    {
        $plan = PlanPago::findOrFail($planId);
        return $plan->delete();
    }

    /**
     * Helper para crear una cuota asociada a un plan.
     */
    private static function crearCuotaParaPlan(int $planId, int $usuarioId, string $tipo, float $monto, string $numeroCuota, Carbon $fechaVencimiento)
    {
        Cuota::create([
            'idPlanPago' => $planId,
            'idUsuario' => $usuarioId,
            'tipo' => $tipo,
            'monto' => $monto,
            'numeroCuota' => $numeroCuota,
            'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            'descuento' => 0,
            'estadoCuota' => 'Debe',
            'fecha_pago' => null,
        ]);
    }
}