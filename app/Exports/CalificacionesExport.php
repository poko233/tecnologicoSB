<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class CalificacionesExport
{
    const BLANCO      = 'FFFFFFFF';
    const NEGRO       = 'FF000000';
    const GRIS_HEADER = 'FFD9D9D9';

    private Spreadsheet $spreadsheet;

    public function __construct(private array $datos)
    {
        $this->spreadsheet = new Spreadsheet();
    }

    public function build(): Spreadsheet
    {
        if (empty($this->datos)) {
            $ws = $this->spreadsheet->getActiveSheet();
            $ws->setTitle('SIN DATOS');
            $ws->setCellValue('A1', 'No se encontraron resultados.');
            $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $ws->getColumnDimension('A')->setWidth(50);
            return $this->spreadsheet;
        }

        foreach ($this->datos as $index => $data) {
            $ws = ($index === 0)
                ? $this->spreadsheet->getActiveSheet()
                : $this->spreadsheet->createSheet();
            $this->crearHoja($data, $ws);
        }

        $this->spreadsheet->setActiveSheetIndex(0);
        return $this->spreadsheet;
    }

    private function crearHoja(array $data, $ws): void
    {
        // ── DATOS ────────────────────────────────────────────────────
        $carrera     = (array) $data['carrera'];
        $materias    = array_map(fn($m) => (array) $m, $data['materias']);
        $estudiantes = array_map(fn($e) => (array) $e, $data['estudiantes']);
        $turno       = strtoupper($data['turno']   ?? '');
        $gestion     = strtoupper($data['gestion'] ?? '');

        $nombreHoja = mb_substr($carrera['codigo'] ?: $carrera['nombre'], 0, 28);
        $ws->setTitle($nombreHoja);
        $ws->setShowGridlines(false);

        // ── DIMENSIONES ───────────────────────────────────────────────
        $totalMaterias  = count($materias);
        $colEstado      = 3 + $totalMaterias + 1;
        $colObservacion = $colEstado + 1;
        $ultimaCol      = Coordinate::stringFromColumnIndex($colObservacion);

        // ── ANCHOS ────────────────────────────────────────────────────
        $ws->getColumnDimension('A')->setWidth(13);
        $ws->getColumnDimension('B')->setWidth(28);
        $ws->getColumnDimension('C')->setWidth(16);
        $ws->getColumnDimension('F')->setWidth(8);
        $ws->getColumnDimension('G')->setWidth(12);

        for ($i = 0; $i < $totalMaterias; $i++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex(4 + $i))->setWidth(5.5);
        }
        $ws->getColumnDimension(Coordinate::stringFromColumnIndex($colEstado))->setWidth(11);
        $ws->getColumnDimension(Coordinate::stringFromColumnIndex($colObservacion))->setWidth(14);

        // ── ESTILOS ───────────────────────────────────────────────────
        $borderThin = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::NEGRO]]],
        ];
        $borderMedium = [
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::NEGRO]]],
        ];
        $fillBlanco = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BLANCO]];
        $fillGris   = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GRIS_HEADER]];

        $styleLabel = [
            'font'      => ['bold' => true,  'size' => 9, 'name' => 'Arial'],
            'fill'      => $fillBlanco,
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $styleValue = [
            'font'      => ['bold' => false, 'size' => 9, 'name' => 'Arial'],
            'fill'      => $fillBlanco,
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        // ══════════════════════════════════════════════════════════════
        //  LOGO + TÍTULO (Filas 1-3)
        // ══════════════════════════════════════════════════════════════
        $ws->getRowDimension(1)->setRowHeight(20);
        $ws->getRowDimension(2)->setRowHeight(20);
        $ws->getRowDimension(3)->setRowHeight(20);

        $logoPath = public_path('empresa/logo_largo.png');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(55);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(3);
            $drawing->setOffsetY(3);
            $drawing->setWorksheet($ws);
        }

        $colD = Coordinate::stringFromColumnIndex(4);
        $ws->mergeCells("{$colD}1:{$ultimaCol}3");
        $ws->setCellValue("{$colD}1", 'CENTRALIZADOR DE CALIFICACIONES');
        $ws->getStyle("{$colD}1:{$ultimaCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'name' => 'Arial', 'color' => ['argb' => self::NEGRO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => $fillBlanco,
        ]);
        $ws->getStyle("A1:{$ultimaCol}3")->applyFromArray($borderMedium);

        // ══════════════════════════════════════════════════════════════
        //  INFO (Filas 4-8)
        // ══════════════════════════════════════════════════════════════
        $fila = 4;

        // ── FILA 4: INSTITUCIÓN | TURNO ──────────────────────────────
        $ws->mergeCells("B{$fila}:E{$fila}");
        $ws->mergeCells("G{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'INSTITUCIÓN:');                  $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", 'INSTITUTO TECNOLÓGICO DEL SUR'); $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->setCellValue("F{$fila}", 'TURNO:');                        $ws->getStyle("F{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("G{$fila}", $turno);                          $ws->getStyle("G{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        // ── FILA 5: GESTIÓN ───────────────────────────────────────────
        $ws->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'GESTIÓN:'); $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", $gestion);   $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        // ── FILA 6: NIVEL ─────────────────────────────────────────────
        $ws->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'NIVEL:');           $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", 'TÉCNICO SUPERIOR'); $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        // ── FILA 7: CARRERA ───────────────────────────────────────────
        $ws->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'CARRERA:');                     $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", strtoupper($carrera['nombre'])); $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        // ── FILA 8: RÉGIMEN | CURSO ───────────────────────────────────
        $ws->mergeCells("B{$fila}:C{$fila}");
        $ws->mergeCells("E{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'RÉGIMEN:');                          $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", strtoupper($carrera['regimen']));     $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->setCellValue("D{$fila}", 'CURSO:');                            $ws->getStyle("D{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("E{$fila}", strtoupper($carrera['curso'] ?? '')); $ws->getStyle("E{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        // ══════════════════════════════════════════════════════════════
        //  CABECERA TABLA NOTAS
        // ══════════════════════════════════════════════════════════════
        $styleCabecera = array_merge($borderThin, $fillGris, [
            'font'      => ['bold' => true, 'size' => 7, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $styleMateria = array_merge($styleCabecera, [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_BOTTOM, 'wrapText' => true, 'textRotation' => 90],
        ]);

        $ws->setCellValue("A{$fila}", 'N°');                    $ws->getStyle("A{$fila}")->applyFromArray($styleCabecera);
        $ws->setCellValue("B{$fila}", 'NÓMINA DE ESTUDIANTES'); $ws->getStyle("B{$fila}")->applyFromArray($styleCabecera);
        $ws->setCellValue("C{$fila}", 'CÉDULA DE IDENTIDAD');   $ws->getStyle("C{$fila}")->applyFromArray($styleCabecera);

        foreach ($materias as $idx => $mat) {
            $col    = Coordinate::stringFromColumnIndex(4 + $idx);
            $codigo = $mat['codigo'] ?? ('M' . ($mat['idMateria'] ?? ''));
            $nombre = strtoupper(trim($mat['nombreMateria'] ?? ''));
            $ws->setCellValue("{$col}{$fila}", $codigo . "\n" . $nombre);
            $ws->getStyle("{$col}{$fila}")->applyFromArray($styleMateria);
        }

        $colEstadoL = Coordinate::stringFromColumnIndex($colEstado);
        $ws->setCellValue("{$colEstadoL}{$fila}", 'ESTADO');
        $ws->getStyle("{$colEstadoL}{$fila}")->applyFromArray($styleCabecera);

        $colObsL = Coordinate::stringFromColumnIndex($colObservacion);
        $ws->setCellValue("{$colObsL}{$fila}", 'OBSERVACIONES');
        $ws->getStyle("{$colObsL}{$fila}")->applyFromArray($styleMateria);

        $ws->getRowDimension($fila)->setRowHeight(120);
        $fila++;

        // ══════════════════════════════════════════════════════════════
        //  ESTUDIANTES
        // ══════════════════════════════════════════════════════════════
        $styleEst = array_merge($borderThin, $fillBlanco, [
            'font'      => ['name' => 'Arial', 'size' => 8],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $n = 1;
        foreach ($estudiantes as $est) {
            $estado = strtoupper($est['estado'] ?? '');
            $row = [$n++, $est['nombreEstudiante'] ?? '', $est['carnet'] ?? ''];

            foreach ($materias as $mat) {
                $codigo = $mat['codigo'] ?? ('M' . ($mat['idMateria'] ?? ''));
                $nota   = $est[$codigo] ?? '';
                $row[]  = is_numeric($nota) ? number_format((float)$nota, 2) : ($nota ?: '--');
            }
            $row[] = $estado;
            $row[] = $est['observaciones'] ?? '';

            foreach ($row as $ci => $val) {
                $col = Coordinate::stringFromColumnIndex($ci + 1);
                $ws->setCellValue("{$col}{$fila}", $val);
                $ws->getStyle("{$col}{$fila}")->applyFromArray(array_merge($styleEst, [
                    'font'      => ['bold' => ($ci >= 3 && $ci < 3 + $totalMaterias), 'size' => 8],
                    'alignment' => ['horizontal' => ($ci === 1) ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]));
            }
            $ws->getRowDimension($fila)->setRowHeight(14);
            $fila++;
        }

        // ══════════════════════════════════════════════════════════════
        //  PÁGINA
        // ══════════════════════════════════════════════════════════════
        $ws->getPageSetup()
           ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
           ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
           ->setFitToPage(true)
           ->setFitToWidth(1)
           ->setFitToHeight(0);
    }

    public function stream(string $filename = 'centralizador.xlsx'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = $this->build();
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}