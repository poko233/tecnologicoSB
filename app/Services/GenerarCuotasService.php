<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Carrera;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerarCuotasService
{
    /**
     * Genera todas las cuotas para un usuario al inscribirse en una carrera.
     * Por cada año de duración se crea una cuota de matrícula (con vencimiento al inicio de ese año).
     * Las cuotas mensuales se generan secuencialmente desde la fecha de inicio,
     * una por mes hasta completar duración * cuotas_por_anio.
     *
     * @param int $usuarioId
     * @param int $carreraId
     * @param string|null $fechaInicio (formato YYYY-MM-DD) – si es null, usa la fecha actual en Bolivia.
     * @return array
     * @throws ValidationException
     */
    public static function generarCuotasPorCarrera(int $usuarioId, int $carreraId, ?string $fechaInicio = null): array
    {
        $usuario = User::findOrFail($usuarioId);
        $carrera = Carrera::findOrFail($carreraId);

        if ($carrera->duracion <= 0) {
            throw ValidationException::withMessages(['duracion' => 'La duración de la carrera debe ser mayor a cero.']);
        }
        if ($carrera->cuotas_por_anio <= 0) {
            throw ValidationException::withMessages(['cuotas_por_anio' => 'El número de cuotas por año debe ser mayor a cero.']);
        }
        if ($carrera->cuota_mensual < 0) {
            throw ValidationException::withMessages(['cuota_mensual' => 'La cuota mensual no puede ser negativa.']);
        }

        $totalAnios = (int) $carrera->duracion;
        $cuotasPorAnio = (int) $carrera->cuotas_por_anio;
        $totalCuotasMensuales = $totalAnios * $cuotasPorAnio;

        $fechaInicioCarbon = $fechaInicio
            ? Carbon::parse($fechaInicio, 'America/La_Paz')->startOfDay()
            : Carbon::now('America/La_Paz')->startOfDay();

        DB::beginTransaction();
        try {
            $cuotasGeneradas = [];

            // 1. Generar una cuota de matrícula por cada año de duración
            for ($anio = 1; $anio <= $totalAnios; $anio++) {
                // Fecha de vencimiento de la matrícula: inicio del año correspondiente
                $fechaMatricula = $fechaInicioCarbon->copy()->addMonths(($anio - 1) * 12);

                $cuotaMatricula = Cuota::create([
                    'idUsuario' => $usuarioId,
                    'idCarrera' => $carreraId,
                    'tipo' => 'MATRICULA',
                    'monto' => $carrera->costo_matricula,
                    'numeroCuota' => 'MATRICULA ' . ($fechaMatricula->year), // Ej: "MATRICULA 2026"
                    'fecha_vencimiento' => $fechaMatricula,
                    'descuento' => 0,
                    'estadoCuota' => 'Debe',
                    'fecha_pago' => null,
                ]);
                $cuotasGeneradas[] = $cuotaMatricula;
            }

            // 2. Generar cuotas mensuales (una por cada mes de duración total)
            for ($i = 1; $i <= $totalCuotasMensuales; $i++) {
                // Número de cuota con formato: "1/N", "2/N", ...
                $numeroCuota = $i . '/' . $totalCuotasMensuales;
                // Vencimiento: la primera cuota vence el mismo día de inicio, la segunda +1 mes, etc.
                $fechaVencimiento = $fechaInicioCarbon->copy()->addMonths($i - 1);

                $cuotaMensual = Cuota::create([
                    'idUsuario' => $usuarioId,
                    'idCarrera' => $carreraId,
                    'tipo' => 'MENSUAL',
                    'monto' => $carrera->cuota_mensual,
                    'numeroCuota' => $numeroCuota,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'descuento' => 0,
                    'estadoCuota' => 'Debe',
                    'fecha_pago' => null,
                ]);
                $cuotasGeneradas[] = $cuotaMensual;
            }

            DB::commit();
            return $cuotasGeneradas;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}