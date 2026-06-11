<?php

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;

class InscritosPorCarreraPdfExport
{
    public function __construct(private array $datos) {}

    public function download(string $filename = 'inscritos.pdf'): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('reportes.inscritos_por_carrera', [
            'datos' => $this->datos,
            'fecha' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}