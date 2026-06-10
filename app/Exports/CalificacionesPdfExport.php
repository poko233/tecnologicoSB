<?php

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;

class CalificacionesPdfExport
{
    public function __construct(
        private array $datos,
        private ?string $gestion = null
    ) {}

    public function download(string $filename = 'centralizador.pdf'): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('reportes.centralizador_calificaciones', [
            'datos'   => $this->datos,
            'gestion' => $this->gestion,
            'fecha'   => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a3', 'landscape');

        return $pdf->download($filename);
    }
}