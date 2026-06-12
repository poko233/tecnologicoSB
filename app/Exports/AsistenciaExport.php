<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AsistenciaExport
{
    const BLANCO      = 'FFFFFFFF';
    const NEGRO       = 'FF000000';
    const GRIS_HEADER = 'FFD9D9D9';
    const ROJO        = 'FFB22222';

    private Spreadsheet $spreadsheet;

    public function __construct(private array $datos)
    {
        $this->spreadsheet = new Spreadsheet();
    }

    public function build(): Spreadsheet
    {
        $data = $this->datos;

        if (empty($data)) {
            $ws = $this->spreadsheet->getActiveSheet();
            $ws->setTitle('SIN DATOS');
            $ws->setCellValue('A1', 'No se encontraron resultados.');
            $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $ws->getColumnDimension('A')->setWidth(50);
            return $this->spreadsheet;
        }

        $ws = $this->spreadsheet->getActiveSheet();
        $this->crearHoja($data, $ws);

        $this->spreadsheet->setActiveSheetIndex(0);
        return $this->spreadsheet;
    }

    private function crearHoja(array $data, $ws): void
    {
        $carrera    = $data['carrera']    ?? '';
        $asignatura = $data['asignatura'] ?? '';
        $docente    = $data['docente']    ?? '';
        $paralelo   = $data['paralelo']   ?? '';
        $periodo    = $data['periodo']    ?? '';
        $fechas     = $data['fechas']     ?? [];
        $filas      = $data['filas']      ?? [];

        $totalFechas = count($fechas);
        $colPorcentaje = 3 + $totalFechas;
        $ultimaCol     = Coordinate::stringFromColumnIndex($colPorcentaje);

        $nombreHoja = mb_substr($paralelo ?: 'Asistencia', 0, 28);
        $ws->setTitle($nombreHoja);
        $ws->setShowGridlines(true);

        $ws->getColumnDimension('A')->setWidth(12);  
        $ws->getColumnDimension('B')->setWidth(32);  

        for ($i = 0; $i < $totalFechas; $i++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex(3 + $i))->setWidth(4);
        }
        $ws->getColumnDimension(Coordinate::stringFromColumnIndex($colPorcentaje))->setWidth(8);

        $borderThin = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::NEGRO]]],
        ];
        $borderMedium = [
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::NEGRO]]],
        ];
        $fillBlanco = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BLANCO]];
        $fillGris   = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GRIS_HEADER]];

        $styleLabel = [
            'font'      => ['bold' => true,  'size' => 9, 'name' => 'Arial', 'color' => ['argb' => self::NEGRO]],
            'fill'      => $fillBlanco,
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $styleValue = [
            'font'      => ['bold' => false, 'size' => 9, 'name' => 'Arial', 'color' => ['argb' => self::NEGRO]],
            'fill'      => $fillBlanco,
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        
        $alturaHeader = 22;
        $ws->getRowDimension(1)->setRowHeight($alturaHeader);
        $ws->getRowDimension(2)->setRowHeight($alturaHeader);
        $ws->getRowDimension(3)->setRowHeight($alturaHeader);

        $ws->mergeCells('A1:B3');
        $ws->getStyle('A1:B3')->applyFromArray([
            'fill'    => $fillBlanco,
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::NEGRO]]],
        ]);

        
        $anchoDisponible = (12 + 32) * 7 - 8;
        $altoDisponible  = ($alturaHeader * 3) - 8;

        $logoPath = public_path('empresa/logo_largo.png');
        if (file_exists($logoPath)) {
            [$logoW, $logoH] = getimagesize($logoPath);
            $ratio = min($altoDisponible / $logoH, $anchoDisponible / $logoW);

            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight((int) ($logoH * $ratio));
            $drawing->setWidth((int) ($logoW * $ratio));
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(4);
            $drawing->setOffsetY(4);
            $drawing->setWorksheet($ws);
        }

        $colD = Coordinate::stringFromColumnIndex(3);
        $ws->mergeCells("{$colD}1:{$ultimaCol}3");
        $ws->setCellValue("{$colD}1", 'REGISTRO DE ASISTENCIA ESTUDIANTIL');
        $ws->getStyle("{$colD}1:{$ultimaCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'name' => 'Arial', 'color' => ['argb' => self::ROJO], 'underline' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill'      => $fillBlanco,
        ]);
        $ws->getStyle("A1:{$ultimaCol}3")->applyFromArray($borderMedium);

        
        $fila = 4;

        $ws->mergeCells("B{$fila}:E{$fila}");
        $ws->mergeCells("G{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'INSTITUCIÓN:');
        $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", 'INSTITUTO TECNOLÓGICO DEL SUR');
        $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->setCellValue("F{$fila}", 'PARALELO:');
        $ws->getStyle("F{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("G{$fila}", strtoupper($paralelo));
        $ws->getStyle("G{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        $ws->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'PERÍODO:');
        $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", strtoupper($periodo));
        $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        $ws->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'CARRERA:');
        $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", strtoupper($carrera));
        $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        $ws->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'ASIGNATURA:');
        $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", strtoupper($asignatura));
        $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        $ws->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
        $ws->setCellValue("A{$fila}", 'DOCENTE:');
        $ws->getStyle("A{$fila}")->applyFromArray($styleLabel);
        $ws->setCellValue("B{$fila}", strtoupper($docente));
        $ws->getStyle("B{$fila}")->applyFromArray($styleValue);
        $ws->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(15);
        $fila++;

        
        $styleCabecera = array_merge($borderThin, $fillGris, [
            'font'      => ['bold' => true, 'size' => 8, 'name' => 'Arial', 'color' => ['argb' => self::NEGRO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $styleFechaVertical = array_merge($borderThin, $fillGris, [
            'font'      => ['bold' => true, 'size' => 8, 'name' => 'Arial', 'color' => ['argb' => self::NEGRO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'textRotation' => 90, 'wrapText' => true],
        ]);

        $ws->setCellValue("A{$fila}", 'N°');
        $ws->getStyle("A{$fila}")->applyFromArray($styleCabecera);
        $ws->setCellValue("B{$fila}", 'ESTUDIANTE');
        $ws->getStyle("B{$fila}")->applyFromArray($styleCabecera);

        foreach ($fechas as $idx => $fecha) {
            $col = Coordinate::stringFromColumnIndex(3 + $idx);
            $ws->setCellValue("{$col}{$fila}", $fecha);
            $ws->getStyle("{$col}{$fila}")->applyFromArray($styleFechaVertical);
        }

        $colPorcentajeL = Coordinate::stringFromColumnIndex($colPorcentaje);
        $ws->setCellValue("{$colPorcentajeL}{$fila}", '% ASIST.');
        $ws->getStyle("{$colPorcentajeL}{$fila}")->applyFromArray(array_merge($styleCabecera, [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'textRotation' => 90, 'wrapText' => true],
        ]));

        $ws->getRowDimension($fila)->setRowHeight(55);
        $fila++;

        
        $styleEst = array_merge($borderThin, $fillBlanco, [
            'font'      => ['name' => 'Arial', 'size' => 8, 'color' => ['argb' => self::NEGRO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        foreach ($filas as $i => $filaData) {
            $ws->setCellValue("A{$fila}", $i + 1);
            $ws->getStyle("A{$fila}")->applyFromArray($styleEst);

            $ws->setCellValue("B{$fila}", $filaData['nombre']);
            $ws->getStyle("B{$fila}")->applyFromArray(array_merge($styleEst, [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER]
            ]));

            

            foreach ($fechas as $j => $fecha) {
                $col = Coordinate::stringFromColumnIndex(3 + $j);
                $valor = $filaData['asistencias'][$fecha] ?? '-';
                $ws->setCellValue("{$col}{$fila}", $valor);
                $ws->getStyle("{$col}{$fila}")->applyFromArray($styleEst);
            }

            $ws->setCellValue("{$colPorcentajeL}{$fila}", $filaData['porcentaje'] . '%');
            $ws->getStyle("{$colPorcentajeL}{$fila}")->applyFromArray(array_merge($styleEst, [
                'font' => ['bold' => true, 'size' => 8]
            ]));

            $ws->getRowDimension($fila)->setRowHeight(15);
            $fila++;
        }

        
        $ws->getPageSetup()
           ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
           ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
           ->setFitToPage(true)
           ->setFitToWidth(1)
           ->setFitToHeight(0);
    }

    public function stream(string $filename = 'reporte_asistencia.xlsx'): \Symfony\Component\HttpFoundation\StreamedResponse
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