<?php

namespace App\Http\Controllers;

use App\Exports\InscritosPorCarreraExport;
use App\Exports\InscritosPorCarreraPdfExport;
use App\Services\ReporteInscritosService;
use Illuminate\Http\Request;

class ReporteInscritosController extends Controller
{
    public function __construct(private ReporteInscritosService $service) {}

    public function xlsx(Request $request)
    {
        $datos = $this->service->obtenerInscritosPorCarrera(
            idCarrera:   $request->integer('idCarrera') ?: null,
            fechaInicio: $request->string('fechaInicio') ?: null,
            fechaFin:    $request->string('fechaFin') ?: null,
        );

        $filename = 'inscritos_por_carrera_' . now()->format('Ymd_His') . '.xlsx';

        return (new InscritosPorCarreraExport($datos))->stream($filename);
    }

    public function pdf(Request $request)
    {
        $datos = $this->service->obtenerInscritosPorCarrera(
            idCarrera:   $request->integer('idCarrera') ?: null,
            fechaInicio: $request->string('fechaInicio') ?: null,
            fechaFin:    $request->string('fechaFin') ?: null,
        );

        $filename = 'inscritos_por_carrera_' . now()->format('Ymd_His') . '.pdf';

        return (new InscritosPorCarreraPdfExport($datos))->download($filename);
    }

    public function filtros()
    {
        $carreras = \App\Models\Carrera::where('estadoCarrera', 'activo')
            ->select('idCarrera', 'nombreCarrera', 'codigo')
            ->orderBy('nombreCarrera')
            ->get();

        return response()->json([
            'data' => ['carreras' => $carreras],
        ]);
    }
}