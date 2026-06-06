<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ElementoCompetencia;
use App\Models\GrupoMateriaDocente;
use App\Models\Inscripcion;
use App\Models\NotaElementoCompetencia;
use App\Models\NotaFinal;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotaFinalService
{
    public function guardarNotas(int $idGrupoMateriaDocente, array $notas, int $userId): array
    {
        $gmd = GrupoMateriaDocente::findOrFail($idGrupoMateriaDocente);
        $this->verificarAcceso($gmd, $userId);

        $inscripcionesValidas = Inscripcion::where('idGrupo', $gmd->idGrupo)
            ->pluck('idInscripcion')
            ->toArray();

        $ecsActivos = ElementoCompetencia::activos()
            ->where('id_grupo_materia_docente', $idGrupoMateriaDocente)
            ->pluck('id')
            ->toArray();

        $notasEcData = [];
        $notasFinalData = [];
        $errores = [];

        foreach ($notas as $nota) {
            $idInscripcion = $nota['id_inscripcion'];

            // Validar inscripción
            if (!in_array($idInscripcion, $inscripcionesValidas)) {
                $errores[] = "La inscripción {$idInscripcion} no pertenece al grupo.";
                continue;
            }

            // Validar ECs
            $ecIdsEnviados = array_column($nota['ecs'], 'id_elemento_competencia');
            if (array_diff($ecIdsEnviados, $ecsActivos)) {
                $errores[] = "Estudiante {$idInscripcion}: algunos ECs no están activos o no pertenecen a este grupo.";
                continue;
            }

            // Calcular y validar coherencia
            $puntajes = array_map(fn($ec) => (float) $ec['puntaje'], $nota['ecs']);
            $promedioEc = count($puntajes) > 0 ? array_sum($puntajes) / count($puntajes) : 0;
            $notaAcademica = round($promedioEc * 0.9, 2);
            $notaAsistencia = (float) $nota['nota_asistencia'];
            $notaFinalCalculada = round($notaAcademica + $notaAsistencia, 2);

            if (abs($notaFinalCalculada - (float) $nota['nota_final']) > 0.01) {
                $errores[] = "Estudiante {$idInscripcion}: la nota final enviada ({$nota['nota_final']}) no coincide con la calculada ({$notaFinalCalculada}).";
                continue;
            }

            $estadoEsperado = $notaFinalCalculada >= 51 ? 'Aprobado' : 'Reprobado';
            if ($nota['estado'] !== $estadoEsperado) {
                $errores[] = "Estudiante {$idInscripcion}: el estado enviado ({$nota['estado']}) no coincide con el calculado ({$estadoEsperado}).";
                continue;
            }

            // Preparar datos para upsert de NotaElementoCompetencia
            foreach ($nota['ecs'] as $ecData) {
                $notasEcData[] = [
                    'id_elemento_competencia' => $ecData['id_elemento_competencia'],
                    'id_inscripcion' => $idInscripcion,
                    'puntaje' => $ecData['puntaje'],
                    'observaciones' => $ecData['observaciones'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Preparar datos para upsert de NotaFinal
            $notasFinalData[] = [
                'id_inscripcion' => $idInscripcion,
                'id_grupo_materia_docente' => $gmd->idGrupoMateriaDocente,
                'nota_asistencia' => $notaAsistencia,
                'nota_academica' => $notaAcademica,
                'nota_final' => $notaFinalCalculada,
                'estado' => $nota['estado'],
                'calificado_por' => $userId,
                'observaciones' => $nota['observaciones'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Ejecutar upserts masivos en una sola transacción
        if (!empty($notasEcData)) {
            DB::transaction(function () use ($notasEcData, $notasFinalData) {
                NotaElementoCompetencia::upsert(
                    $notasEcData,
                    ['id_elemento_competencia', 'id_inscripcion'],
                    ['puntaje', 'observaciones', 'updated_at']
                );

                NotaFinal::upsert(
                    $notasFinalData,
                    ['id_inscripcion', 'id_grupo_materia_docente'],
                    ['nota_asistencia', 'nota_academica', 'nota_final', 'estado', 'calificado_por', 'observaciones', 'updated_at']
                );
            });
        }

        return [
            'procesados' => count($notasFinalData),
            'errores' => $errores,
        ];
    }

    private function verificarAcceso(GrupoMateriaDocente $gmd, int $userId): void
    {
        $user = request()->user();

        if ($user->roles()->pluck('rol')->contains('Administrador')) {
            return;
        }

        if ($gmd->idDocente !== $userId) {
            throw new HttpException(403, 'No tienes permiso para guardar notas en este grupo.');
        }
    }
}