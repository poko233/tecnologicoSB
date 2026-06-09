<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ReporteExcelExport;
use App\Exports\ReportePdfExport;
use App\Services\PlanillaReporteService;
use Illuminate\Http\Request;

class PlanillaReporteController extends Controller
{
    public function __construct(
        private readonly PlanillaReporteService $reporteService
    ) {}

    /**
     * GET /reportes/{idGrupoMateriaDocente}/excel
     * Descarga la planilla como archivo .xlsx
     */
    public function excel(Request $request, int $idGrupoMateriaDocente)
    {
        $userId = $request->user()->id;
        $datos  = $this->reporteService->obtenerDatosReporte($idGrupoMateriaDocente, $userId);

        $filename = $this->buildFilename($datos, 'xlsx');
        return (new ReporteExcelExport($datos))->download($filename);
    }

    /**
     * GET /reportes/{idGrupoMateriaDocente}/pdf
     * Descarga la planilla como archivo .pdf
     */
    public function pdf(Request $request, int $idGrupoMateriaDocente)
    {
        $userId = $request->user()->id;
        $datos  = $this->reporteService->obtenerDatosReporte($idGrupoMateriaDocente, $userId);

        $filename = $this->buildFilename($datos, 'pdf');
        return (new ReportePdfExport($datos))->download($filename);
    }

    /**
     * GET /reportes/{idGrupoMateriaDocente}/pdf/ver
     * Muestra el PDF directamente en el navegador (inline).
     */
    public function pdfVer(Request $request, int $idGrupoMateriaDocente)
    {
        $userId = $request->user()->id;
        $datos  = $this->reporteService->obtenerDatosReporte($idGrupoMateriaDocente, $userId);

        $filename = $this->buildFilename($datos, 'pdf');
        return (new ReportePdfExport($datos))->stream($filename);
    }


    private function buildFilename(array $datos, string $ext): string
    {
        $grupo   = $datos['grupo']['nombre'] ?? 'grupo';
        $materia = $datos['materia']['nombre'] ?? 'materia';
        $gestion = $datos['grupo']['gestion'] ?? '';

        $clean = fn(string $s): string => preg_replace('/[^A-Za-z0-9\-\_]/', '_', $s);

        return "Planilla_{$clean($grupo)}_{$clean($materia)}_{$clean($gestion)}.{$ext}";
    }
}