<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ListaGrupoExport
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
            $ws->setCellValue('A1', 'No se encontraron estudiantes.');
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
        $grupo = $data['grupo'];
        $estudiantes = $data['estudiantes'];

        $nombreHoja = mb_substr($grupo['grupo'] ?? 'Grupo', 0, 28);
        $ws->setTitle($nombreHoja);

        $ws->getColumnDimension('A')->setWidth(9);    
        $ws->getColumnDimension('B')->setWidth(55);   
        $ws->getColumnDimension('C')->setWidth(18);   
        $ws->getColumnDimension('D')->setWidth(18);  
        $ws->getColumnDimension('E')->setWidth(35);  

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

        $ws->mergeCells('C1:E3');
        $ws->setCellValue('C1', 'LISTA OFICIAL POR GRUPO');
        $ws->getStyle('C1:E3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getStyle('A1:E3')->applyFromArray($borderMedium);

        $fila = 4;
        $info = [
            ['CARRERA:', $grupo['carrera'] ?? ''],
            ['GRUPO:', $grupo['grupo'] ?? ''],
            ['MATERIA:', $grupo['materia'] ?? ''],
            ['DOCENTE:', $grupo['docente'] ?? ''],
            ['GESTIÓN:', ($grupo['gestion'] ?? '') . '  |  TURNO: ' . ($grupo['turno'] ?? '')],
        ];

        foreach ($info as [$label, $valor]) {
            $ws->setCellValue("A{$fila}", $label);
            $ws->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(10);
            $ws->mergeCells("B{$fila}:E{$fila}");
            $ws->setCellValue("B{$fila}", $valor);
            $ws->getStyle("A{$fila}:E{$fila}")->applyFromArray($borderThin);
            $ws->getStyle("A{$fila}:E{$fila}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GRIS]],
                'font' => ['size' => 10, 'name' => 'Arial'],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $ws->getRowDimension($fila)->setRowHeight(18);
            $fila++;
        }
        $fila++;

        $headers = ['N°', 'NOMBRES Y APELLIDOS', 'CARNET', 'CELULAR', 'OBSERVACIÓN'];
        foreach ($headers as $ci => $h) {
            $col = Coordinate::stringFromColumnIndex($ci + 1);
            $ws->setCellValue("{$col}{$fila}", $h);
        }
        $ws->getStyle("A{$fila}:E{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::GRIS]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getStyle("A{$fila}:E{$fila}")->applyFromArray($borderThin);
        $ws->getRowDimension($fila)->setRowHeight(22);
        $fila++;

        $n = 1;
        foreach ($estudiantes as $est) {
            $row = [
                $n++,
                $est['estudiante'] ?? '',
                $est['carnet'] ?? '',
                $est['celular'] ?? '',
                $est['observacion'] ?? '',
            ];
            foreach ($row as $ci => $val) {
                $col = Coordinate::stringFromColumnIndex($ci + 1);
                $ws->setCellValue("{$col}{$fila}", $val);
            }
            $ws->getStyle("A{$fila}:E{$fila}")->applyFromArray($borderThin);
            $ws->getStyle("A{$fila}:E{$fila}")->applyFromArray([
                'font' => ['size' => 10, 'name' => 'Arial'],
                'alignment' => [
                    'horizontal' => ($ci === 1) ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $ws->getRowDimension($fila)->setRowHeight(18);
            $fila++;
        }

        $ws->getPageSetup()
           ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
           ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
           ->setFitToPage(true)
           ->setFitToWidth(1)
           ->setFitToHeight(0);
    }

    public function stream(string $filename = 'lista_grupo.xlsx'): \Symfony\Component\HttpFoundation\StreamedResponse
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