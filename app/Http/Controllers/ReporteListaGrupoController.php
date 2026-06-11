<?php

namespace App\Http\Controllers;

use App\Exports\ListaGrupoExport;
use App\Services\ReporteListaGrupoService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteListaGrupoController extends Controller
{
    public function __construct(private ReporteListaGrupoService $service) {}

    public function xlsx(Request $request)
    {
        $datos = $this->service->obtenerListaPorGrupo(
            $request->integer('idGrupoMateriaDocente') ?: null
        );

        $filename = 'lista_grupo_' . now()->format('Ymd_His') . '.xlsx';
        return (new ListaGrupoExport($datos))->stream($filename);
    }

    public function pdf(Request $request)
    {
        $datos = $this->service->obtenerListaPorGrupo(
            $request->integer('idGrupoMateriaDocente') ?: null
        );

        $pdf = Pdf::loadView('reportes.lista_grupo', [
            'datos' => $datos,
            'fecha' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('lista_grupo_' . now()->format('Ymd_His') . '.pdf');
    }

    public function filtros()
    {
        $filtros = $this->service->obtenerFiltros();
        return response()->json(['data' => $filtros]);
    }
}