<?php

namespace App\Http\Controllers;

use App\Exports\CalificacionesExport;
use App\Exports\CalificacionesPdfExport;
use App\Services\ReporteCalificacionesService;
use Illuminate\Http\Request;

class ReporteCalificacionesController extends Controller
{
    public function __construct(
        private ReporteCalificacionesService $service
    ) {}

    public function preview(Request $request)
    {
        $datos = $this->service->obtenerDatos(
            idCarrera: $request->integer('idCarrera') ?: null,
            gestion:   $request->string('gestion') ?: null,
        );

        $resumen = [];
        foreach ($datos as $carrera) {
            foreach ($carrera['grupos'] as $gmd) {
                $stats = $this->service->estadisticasGrupo(
                    collect($gmd['estudiantes'])->toArray()
                );
                $resumen[] = [
                    'carrera'  => $carrera['nombre'],
                    'grupo'    => $gmd['grupo'],
                    'materia'  => $gmd['materia'],
                    'docente'  => $gmd['docente'],
                    'stats'    => $stats,
                ];
            }
        }

        return response()->json([
            'carreras' => count($datos),
            'resumen'  => $resumen,
            'datos'    => $datos,
        ]);
    }

    
    public function xlsx(Request $request)
    {
        $gestion = $request->string('gestion') ?: null;
        $turno = $request->string('turno') ?: null;

        $datos = $this->service->obtenerDatosHorizontal(
            idCarrera: $request->integer('idCarrera') ?: null,
            gestion: $gestion,
            turno: $turno,
        );

        // Reemplazar / por - en el nombre del archivo
        $filenameGestion = $gestion ? str_replace(['/', '\\'], '-', $gestion) : '';
        $filename = 'centralizador_calificaciones'
            . ($filenameGestion ? "_{$filenameGestion}" : '')
            . '_' . now()->format('Ymd_His')
            . '.xlsx';

        return (new CalificacionesExport($datos))->stream($filename);
    }

    
    public function pdf(Request $request)
    {
        $gestion = $request->string('gestion') ?: null;
        $turno = $request->string('turno') ?: null;

        $datos = $this->service->obtenerDatosHorizontal(
            idCarrera: $request->integer('idCarrera') ?: null,
            gestion: $gestion,
            turno: $turno,
        );

        $filename = 'centralizador_calificaciones'
            . ($gestion ? '_' . str_replace(['/', '\\'], '-', $gestion) : '')
            . '_' . now()->format('Ymd_His')
            . '.pdf';

        return (new CalificacionesPdfExport($datos, $gestion))->download($filename);
    }

    public function filtros()
    {
        $filtros = $this->service->obtenerOpcionesFiltros();
        
        return response()->json([
            'data' => $filtros,
        ]);
    }
}