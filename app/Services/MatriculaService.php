<?php

namespace App\Services;

use App\Models\User;
use App\Helpers\MatriculaHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MatriculaService
{
    /**
     * Generar matrícula para un estudiante (sin PlanPago).
     *
     * @param int $estudianteId
     * @param bool $requierePago
     * @param string|null $observacionPago
     * @param int $adminId
     * @return array
     * @throws \Exception
     */
    // app/Services/MatriculaService.php

    public static function generarMatricula(
        int $estudianteId,
        bool $requierePago,
        float $monto,          // ← nuevo parámetro
        ?string $observacionPago,
        int $adminId
    ): array {
        return DB::transaction(function () use ($estudianteId, $requierePago, $monto, $observacionPago, $adminId) {
            $estudiante = User::findOrFail($estudianteId);
            $codigoMatricula = MatriculaHelper::generarCodigo($estudiante);

            // Crear cuota con el monto recibido
            $cuota = CuotaService::crearCuota(
                null,
                $estudianteId,
                'MATRICULA',
                $monto,                       // ← dinámico
                'MATRICULA',
                Carbon::now()->addDays(30)->toDateString()
            );

            // Registrar pago si requiere pago (con el monto real), sino monto 0
            $montoPago = $requierePago ? $monto : 0;
            $pago = PagoService::registrarPago(
                $cuota->idCuota,
                $estudianteId,
                $montoPago,
                'EFECTIVO',
                $adminId,
                null,
                $observacionPago
            );

            $estudiante->matricula = $codigoMatricula;
            $estudiante->save();

            return [
                'codigo_matricula' => $codigoMatricula,
                'cuota' => $cuota,
                'pago' => $pago,
            ];
        });
    }
}