<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GrupoMateriaDocente;


class PlanillaReporteService
{
    public function __construct(
        private readonly PlanillaService $planillaService
    ) {}
    public function obtenerDatosReporte(int $idGrupoMateriaDocente, int $userId): array
    {
        $planilla = $this->planillaService->obtenerPlanilla($idGrupoMateriaDocente, $userId);

        // Enriquecer cada estudiante con nota_academica, nota_final y estado
        $planilla['estudiantes'] = $planilla['estudiantes']->map(function (array $est): array {
            $puntajes = collect($est['notas_ec'])
                ->pluck('puntaje')
                ->filter(fn($p) => $p !== null)
                ->values();

            $promedioEc    = $puntajes->isNotEmpty() ? $puntajes->average() : 0.0;
            $notaAcademica = round($promedioEc * 0.9, 2);
            $notaFinal     = round($notaAcademica + $est['nota_asistencia'], 2);
            $estado        = $notaFinal >= 51 ? 'Aprobado' : 'Reprobado';

            return array_merge($est, [
                'nota_academica' => $notaAcademica,
                'nota_final'     => $notaFinal,
                'estado'         => $estado,
            ]);
        })->values()->toArray();

        $planilla['carrera'] = '—';

        return $planilla;
    }
}