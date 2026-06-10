<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CalificacionesExport
{
    // Paleta
    const AZUL_OSCURO  = 'FF1F3864';
    const AZUL_MEDIO   = 'FF2E75B6';
    const AZUL_CLARO   = 'FFBDD7EE';
    const GRIS_HEADER  = 'FFD6DCE4';
    const VERDE_APRO   = 'FFE2EFDA';
    const ROJO_REPR    = 'FFFCE4D6';
    const AMARILLO_2DA = 'FFFFF2CC';
    const BLANCO       = 'FFFFFFFF';
    const NEGRO        = 'FF000000';

    private Spreadsheet $spreadsheet;

    public function __construct(private array $datos, private ?string $gestion = null)
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0); // elimina hoja por defecto
    }

    public function build(): Spreadsheet
    {
        $this->crearHojaIndice();

        foreach ($this->datos as $carrera) {
            $this->crearHojaCarrera($carrera);
        }

        $this->crearHojaLeyenda();

        // Activar primera hoja al abrir
        $this->spreadsheet->setActiveSheetIndex(0);

        return $this->spreadsheet;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HOJA ÍNDICE
    // ─────────────────────────────────────────────────────────────────────────
    private function crearHojaIndice(): void
    {
        $ws = $this->spreadsheet->createSheet();
        $ws->setTitle('ÍNDICE');
        // $ws->getSheetView()->setShowGridLines(false);
        $ws->setShowGridlines(false);

        foreach (['A' => 5, 'B' => 14, 'C' => 42, 'D' => 18, 'E' => 22, 'F' => 12] as $col => $w) {
            $ws->getColumnDimension($col)->setWidth($w);
        }

        // Título
        $ws->mergeCells('A1:F1');
        $this->estilo($ws, 'A1:F1', [
            'font'      => ['bold' => true, 'color' => self::BLANCO, 'size' => 14],
            'fill'      => self::AZUL_OSCURO,
            'alignment' => 'center',
        ]);
        $ws->setCellValue('A1', 'CENTRALIZADOR DE CALIFICACIONES');
        $ws->getRowDimension(1)->setRowHeight(28);

        $gestion = $this->gestion ? "GESTIÓN {$this->gestion}" : 'TODAS LAS GESTIONES';
        $ws->mergeCells('A2:F2');
        $this->estilo($ws, 'A2:F2', [
            'font'      => ['color' => self::BLANCO, 'size' => 10],
            'fill'      => self::AZUL_MEDIO,
            'alignment' => 'center',
        ]);
        $ws->setCellValue('A2', "$gestion  |  Generado: " . now()->format('d/m/Y H:i'));
        $ws->getRowDimension(2)->setRowHeight(18);

        // Cabeceras
        $headers = ['N°', 'CÓDIGO', 'CARRERA', 'RÉGIMEN', 'PERÍODO/DURACIÓN', 'GRUPOS'];
        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue("{$col}4", $h);
            $this->estilo($ws, "{$col}4", [
                'font'      => ['bold' => true],
                'fill'      => self::GRIS_HEADER,
                'alignment' => 'center',
                'border'    => true,
            ]);
        }
        $ws->getRowDimension(4)->setRowHeight(18);

        foreach ($this->datos as $idx => $car) {
            $fila = 5 + $idx;
            $row  = [$idx + 1, $car['codigo'], $car['nombre'],
                     strtoupper($car['regimen']),
                     "{$car['duracion']} AÑO(S)",
                     count($car['grupos'])];
            foreach ($row as $ci => $val) {
                $col = Coordinate::stringFromColumnIndex($ci + 1);
                $ws->setCellValue("{$col}{$fila}", $val);
                $this->estilo($ws, "{$col}{$fila}", [
                    'alignment' => $ci === 2 ? 'left' : 'center',
                    'border'    => true,
                ]);
            }
            $ws->getRowDimension($fila)->setRowHeight(15);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HOJA POR CARRERA
    // ─────────────────────────────────────────────────────────────────────────
    private function crearHojaCarrera(array $carrera): void
    {
        $titulo = mb_substr($carrera['codigo'] ?: $carrera['nombre'], 0, 28);
        $ws = $this->spreadsheet->createSheet();
        $ws->setTitle($titulo);
        // $ws->getSheetView()->setShowGridLines(false);
        $ws->setShowGridlines(false);

        foreach (['A' => 5, 'B' => 14, 'C' => 40, 'D' => 18,
                  'E' => 16, 'F' => 16, 'G' => 16, 'H' => 16,
                  'I' => 22, 'J' => 16] as $col => $w) {
            $ws->getColumnDimension($col)->setWidth($w);
        }

        // Encabezado carrera
        $ws->mergeCells('A1:J1');
        $this->estilo($ws, 'A1:J1', ['font' => ['bold' => true, 'color' => self::BLANCO, 'size' => 13],
                                      'fill' => self::AZUL_OSCURO, 'alignment' => 'center']);
        $ws->setCellValue('A1', 'CENTRALIZADOR DE CALIFICACIONES');
        $ws->getRowDimension(1)->setRowHeight(26);

        $regimen = strtoupper($carrera['regimen']);
        $ws->mergeCells('A2:J2');
        $this->estilo($ws, 'A2:J2', ['font' => ['bold' => true, 'color' => self::BLANCO, 'size' => 10],
                                      'fill' => self::AZUL_MEDIO, 'alignment' => 'center']);
        $ws->setCellValue('A2', "{$carrera['nombre']}  —  Régimen: {$regimen}  |  Duración: {$carrera['duracion']} año(s)");
        $ws->getRowDimension(2)->setRowHeight(18);

        $fila = 4;

        foreach ($carrera['grupos'] as $gmd) {
            // Sub-encabezado grupo
            $ws->mergeCells("A{$fila}:J{$fila}");
            $this->estilo($ws, "A{$fila}:J{$fila}", ['font' => ['bold' => true, 'size' => 9],
                                                       'fill' => self::AZUL_CLARO, 'alignment' => 'left']);
            $ws->setCellValue("A{$fila}",
                "GRUPO: {$gmd['grupo']}   |   MATERIA: {$gmd['materia']}   " .
                "|   DOCENTE: {$gmd['docente']}   |   HORARIO: {$gmd['horario']}");
            $ws->getRowDimension($fila)->setRowHeight(16);
            $fila++;

            // Cabeceras columnas
            $cols = ['N°', 'CARNET', 'APELLIDOS Y NOMBRES', 'GRUPO',
                     'NOTA ASIST.', 'NOTA ACAD.', 'NOTA FINAL',
                     '2DA INST.', 'OBSERVACIONES', 'ESTADO'];
            foreach ($cols as $ci => $h) {
                $col = Coordinate::stringFromColumnIndex($ci + 1);
                $ws->setCellValue("{$col}{$fila}", $h);
                $this->estilo($ws, "{$col}{$fila}", [
                    'font'      => ['bold' => true],
                    'fill'      => self::GRIS_HEADER,
                    'alignment' => 'center',
                    'border'    => true,
                    'wrap'      => true,
                ]);
            }
            $ws->getRowDimension($fila)->setRowHeight(28);
            $fila++;

            $primeraFila = $fila;
            $n = 1;

            foreach ($gmd['estudiantes'] as $est) {
                $est = (array) $est;
                
                $estado = strtoupper($est['estado'] ?? '');
                $bgFila = str_contains($estado, 'APRO') ? self::VERDE_APRO
                        : (str_contains($estado, 'REPR') ? self::ROJO_REPR
                        : self::AMARILLO_2DA);

                $datos = [
                    $n++,
                    $est['carnet'] ?? '',
                    $est['nombreEstudiante'] ?? '',
                    $gmd['grupo'],
                    $est['nota_asistencia'] ?? '',
                    $est['nota_academica'] ?? '',
                    $est['nota_final'] ?? '',
                    $est['segunda_instancia_nota'] ?? '',
                    $est['observaciones'] ?? '',
                    $estado,
                ];

                foreach ($datos as $ci => $val) {
                    $col = Coordinate::stringFromColumnIndex($ci + 1);
                    $ws->setCellValue("{$col}{$fila}", $val);
                    $isNota = in_array($ci, [4, 5, 6, 7]);
                    $this->estilo($ws, "{$col}{$fila}", [
                        'fill'      => $bgFila,
                        'alignment' => ($ci === 2 || $ci === 8) ? 'left' : 'center',
                        'border'    => true,
                        'font'      => $ci === 9 ? ['bold' => true] : [],
                    ]);
                    if ($isNota && is_numeric($val)) {
                        $ws->getStyle("{$col}{$fila}")->getNumberFormat()
                           ->setFormatCode('0.00');
                    }
                }
                $ws->getRowDimension($fila)->setRowHeight(15);
                $fila++;
            }

            $ultimaFila = $fila - 1;

            // Fila de promedios
            $ws->setCellValue("A{$fila}", '');
            $ws->setCellValue("B{$fila}", '');
            $ws->mergeCells("C{$fila}:D{$fila}");
            $ws->setCellValue("C{$fila}", 'PROMEDIO GRUPO');
            $ws->setCellValue("E{$fila}", "=AVERAGE(E{$primeraFila}:E{$ultimaFila})");
            $ws->setCellValue("F{$fila}", "=AVERAGE(F{$primeraFila}:F{$ultimaFila})");
            $ws->setCellValue("G{$fila}", "=AVERAGE(G{$primeraFila}:G{$ultimaFila})");
            $ws->setCellValue("J{$fila}",
                "=COUNTIF(J{$primeraFila}:J{$ultimaFila},\"APROBADO\")" .
                "&\" APR / \"" .
                "&COUNTIF(J{$primeraFila}:J{$ultimaFila},\"REPROBADO\")" .
                "&\" REP\"");

            foreach (['A', 'B', 'C', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
                $this->estilo($ws, "{$col}{$fila}", [
                    'font'      => ['bold' => true],
                    'fill'      => self::GRIS_HEADER,
                    'alignment' => 'center',
                    'border'    => true,
                ]);
            }
            foreach (['E', 'F', 'G'] as $col) {
                $ws->getStyle("{$col}{$fila}")->getNumberFormat()->setFormatCode('0.00');
            }
            $ws->getRowDimension($fila)->setRowHeight(16);
            $fila += 3; // espacio entre grupos
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HOJA LEYENDA
    // ─────────────────────────────────────────────────────────────────────────
    private function crearHojaLeyenda(): void
    {
        $ws = $this->spreadsheet->createSheet();
        $ws->setTitle('LEYENDA');
        $ws->setShowGridlines(false);  
        // $ws->getSheetView()->setShowGridLines(false);
        $ws->getColumnDimension('B')->setWidth(25);
        $ws->getColumnDimension('C')->setWidth(42);

        $ws->mergeCells('B2:C2');
        $this->estilo($ws, 'B2:C2', ['font' => ['bold' => true, 'color' => self::BLANCO, 'size' => 12],
                                      'fill' => self::AZUL_OSCURO, 'alignment' => 'center']);
        $ws->setCellValue('B2', 'LEYENDA DE COLORES');

        $items = [
            [self::VERDE_APRO,   'APROBADO',       'Nota final ≥ 51'],
            [self::ROJO_REPR,    'REPROBADO',       'Nota final < 51'],
            [self::AMARILLO_2DA, '2DA INSTANCIA',   'Habilitado a segunda instancia'],
        ];

        foreach ($items as $i => [$bg, $label, $desc]) {
            $fila = 4 + $i;
            $ws->setCellValue("B{$fila}", $label);
            $ws->setCellValue("C{$fila}", $desc);
            foreach (['B', 'C'] as $col) {
                $this->estilo($ws, "{$col}{$fila}", [
                    'fill'      => $bg,
                    'font'      => $col === 'B' ? ['bold' => true] : [],
                    'alignment' => $col === 'C' ? 'left' : 'center',
                    'border'    => true,
                ]);
            }
            $ws->getRowDimension($fila)->setRowHeight(16);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper de estilos
    // ─────────────────────────────────────────────────────────────────────────
    private function estilo($ws, string $rango, array $opts): void
    {
        $style = $ws->getStyle($rango);

        if (!empty($opts['fill'])) {
            $style->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB($opts['fill']);
        }

        $fontOpts = $opts['font'] ?? [];
        $f = $style->getFont();
        $f->setName('Arial')->setSize($fontOpts['size'] ?? 9);
        if (!empty($fontOpts['bold']))  $f->setBold(true);
        if (!empty($fontOpts['color'])) $f->getColor()->setARGB($fontOpts['color']);

        $align = $opts['alignment'] ?? 'center';
        $style->getAlignment()
              ->setHorizontal($align)
              ->setVertical(Alignment::VERTICAL_CENTER)
              ->setWrapText($opts['wrap'] ?? false);

        if (!empty($opts['border'])) {
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF888888'],
                    ],
                ],
            ];
            $ws->getStyle($rango)->applyFromArray($borderStyle);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar en stream (para response()->streamDownload)
    // ─────────────────────────────────────────────────────────────────────────
    public function stream(string $filename = 'centralizador.xlsx'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = $this->build();

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}