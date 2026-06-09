<?php

declare(strict_types=1);

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportePdfExport
{
    public function __construct(private readonly array $datos) {}

    /** Descarga directa como StreamedResponse. */
    public function download(string $filename = 'planilla.pdf'): \Illuminate\Http\Response
    {
        return $this->buildPdf()->download($filename);
    }

    /** Muestra el PDF en el navegador (inline). */
    public function stream(string $filename = 'planilla.pdf'): \Illuminate\Http\Response
    {
        return $this->buildPdf()->stream($filename);
    }

    /** Guarda en disco. */
    public function guardar(string $rutaAbsoluta): void
    {
        $this->buildPdf()->save($rutaAbsoluta);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Construcción del PDF
    // ─────────────────────────────────────────────────────────────────────────

    private function buildPdf(): \Barryvdh\DomPDF\PDF
    {
        $ecs     = $this->datos['elementos_competencia'];
        $alumnos = $this->datos['estudiantes'];

        // Precalcular resumen
        $aprobados  = count(array_filter($alumnos, fn($e) => ($e['estado'] ?? '') === 'Aprobado'));
        $reprobados = count($alumnos) - $aprobados;

        $html = View::make('reportes.planilla_pdf', [
            'grupo'                  => $this->datos['grupo'],
            'materia'                => $this->datos['materia'],
            'carrera'                => $this->datos['carrera'] ?? '—',
            'elementos_competencia'  => $ecs,
            'estudiantes'            => $alumnos,
            'total_estudiantes'      => count($alumnos),
            'aprobados'              => $aprobados,
            'reprobados'             => $reprobados,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'       => 'Arial',
                'isRemoteEnabled'   => false,
                'isHtml5ParserEnabled' => true,
            ]);
    }
}