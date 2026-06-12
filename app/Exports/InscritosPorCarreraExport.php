<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InscritosPorCarreraExport
{
    const BLANCO = 'FFFFFFFF';
    const NEGRO  = 'FF000000';
    const GRIS   = 'FFEEEEEE';

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
            $ws->setCellValue('A1', 'No se encontraron inscritos.');
            $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            return $this->spreadsheet;
        }

        foreach ($this->datos as $index => $data) {
            $ws = ($index === 0) ? $this->spreadsheet->getActiveSheet() : $this->spreadsheet->createSheet();
            $this->crearHoja($data, $ws);
        }

        $this->spreadsheet->setActiveSheetIndex(0);
        return $this->spreadsheet;
    }

    private function crearHoja(array $data, $ws): void
    {
        $carrera = $data['carrera'];
        $estudiantes = $data['estudiantes'];

        $nombreHoja = mb_substr($carrera['codigo'] ?: $carrera['nombre'], 0, 28);
        $ws->setTitle($nombreHoja);

        $ws->getColumnDimension('A')->setWidth(6);
        $ws->getColumnDimension('B')->setWidth(16);
        $ws->getColumnDimension('C')->setWidth(45);
        $ws->getColumnDimension('D')->setWidth(22);
        $ws->getColumnDimension('E')->setWidth(14);
        $ws->getColumnDimension('F')->setWidth(12);

        $borderThin = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::NEGRO]]],
        ];
        $borderMedium = [
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::NEGRO]]],
        ];

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

        $ws->mergeCells('C1:F3');
        $ws->setCellValue('C1', 'INSCRITOS POR CARRERA');
        $ws->getStyle('C1:F3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BLANCO]],
        ]);

        $ws->getStyle('A1:F3')->applyFromArray($borderMedium);

        $fila = 4;

        $ws->mergeCells("A{$fila}:F{$fila}");
        $ws->setCellValue("A{$fila}", 'CARRERA: ' . strtoupper($carrera['nombre']) . '     |     TOTAL: ' . count($estudiantes) . ' INSCRITOS');
        $ws->getStyle("A{$fila}:F{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GRIS]],
        ]);
        $ws->getStyle("A{$fila}:F{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(20);
        $fila++;
        $fila++;

        $headers = ['N°', 'CARNET', 'ESTUDIANTE', 'FECHA INSCRIPCIÓN', 'GESTIÓN', 'TURNO'];
        foreach ($headers as $ci => $h) {
            $col = Coordinate::stringFromColumnIndex($ci + 1);
            $ws->setCellValue("{$col}{$fila}", $h);
        }
        $ws->getStyle("A{$fila}:F{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GRIS]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getStyle("A{$fila}:F{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(20);
        $fila++;

        $n = 1;
        foreach ($estudiantes as $est) {
            $row = [
                $n++,
                $est['carnet'] ?? '',
                $est['estudiante'] ?? '',
                $est['fecha_inscripcion'] ?? '',
                $est['gestion'] ?? '',
                $est['turno'] ?? '',
            ];
            foreach ($row as $ci => $val) {
                $col = Coordinate::stringFromColumnIndex($ci + 1);
                $ws->setCellValue("{$col}{$fila}", $val);
            }
            $ws->getStyle("A{$fila}:F{$fila}")->applyFromArray([
                'font' => ['size' => 9, 'name' => 'Arial'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BLANCO]],
                'alignment' => [
                    'horizontal' => ($ci === 2) ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $ws->getStyle("A{$fila}:F{$fila}")->applyFromArray($borderThin);
            $ws->getRowDimension($fila)->setRowHeight(16);
            $fila++;
        }

        $ws->getPageSetup()
           ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
           ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
           ->setFitToPage(true)
           ->setFitToWidth(1)
           ->setFitToHeight(0);
    }

    public function stream(string $filename = 'inscritos.xlsx'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = $this->build();
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}