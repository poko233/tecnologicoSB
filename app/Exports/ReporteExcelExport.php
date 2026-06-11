<?php

declare(strict_types=1);

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteExcelExport
{
    private const COLOR_WHITE = 'FFFFFFFF';
    private const COLOR_BLACK = 'FF000000';
    private const COLOR_GRAY  = 'FFEEEEEE';
    private const COLOR_DARK  = 'FFDDDDDD';

    private Spreadsheet $spreadsheet;

    public function __construct(private readonly array $datos) {}

    public function download(string $filename = 'planilla.xlsx'): StreamedResponse
    {
        $this->build();

        return new StreamedResponse(function () {
            (new Xlsx($this->spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function build(): void
    {
        $this->spreadsheet = new Spreadsheet();
        $ws  = $this->spreadsheet->getActiveSheet();
        $ws->setTitle('Planilla de Notas');

        $ecs     = collect($this->datos['elementos_competencia'])->toArray();
        $alumnos = collect($this->datos['estudiantes'])->toArray();
        $nEc     = count($ecs);
        $lastCol = 6 + $nEc;

        $this->logoYTitulo($ws, $lastCol);
        $this->metadatos($ws);
        $this->headers($ws, $ecs, $nEc);
        $this->filasDatos($ws, $ecs, $alumnos, $nEc);
        $this->resumen($ws, $alumnos, $nEc);
        $this->anchos($ws, $nEc);
        $this->configurarPagina($ws);
    }

    // ═══ LOGO + TÍTULO (filas 1-3) ═══
    private function logoYTitulo(Worksheet $ws, int $lastCol): void
    {
        $end = Coordinate::stringFromColumnIndex($lastCol);

        // Altura de las 3 filas
        $ws->getRowDimension(1)->setRowHeight(20);
        $ws->getRowDimension(2)->setRowHeight(20);
        $ws->getRowDimension(3)->setRowHeight(20);

        // Logo
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

        // Título centrado (columna C en adelante)
        $ws->mergeCells("C1:{$end}3");
        $ws->setCellValue('C1', 'PLANILLA DE CALIFICACIONES');
        $ws->getStyle("C1:{$end}3")->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 16, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Borde exterior del bloque logo+título
        $ws->getStyle("A1:{$end}3")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::COLOR_BLACK]]],
        ]);
    }

    // ═══ METADATOS (fila 4) ═══
    private function metadatos(Worksheet $ws): void
    {
        $items = [
            ['col' => 'A', 'label' => 'Grupo:',   'value' => $this->datos['grupo']['nombre']],
            ['col' => 'C', 'label' => 'Gestión:', 'value' => $this->datos['grupo']['gestion']],
            ['col' => 'E', 'label' => 'Materia:', 'value' => $this->datos['materia']['nombre']],
            ['col' => 'G', 'label' => 'Carrera:', 'value' => $this->datos['carrera'] ?? '—'],
        ];

        foreach ($items as $item) {
            $col     = $item['col'];
            $label   = $item['label'];
            $value   = $item['value'];
            $nextCol = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($col) + 1);

            $ws->setCellValue("{$col}4", $label);
            $ws->getStyle("{$col}4")->getFont()->setBold(true)->setName('Arial')->setSize(10);

            $ws->setCellValue("{$nextCol}4", $value);
            $ws->getStyle("{$nextCol}4")->getFont()->setName('Arial')->setSize(10);
        }

        $ws->getStyle("A4:{$nextCol}4")->applyFromArray([
            'fill' => $this->fill(self::COLOR_GRAY),
            'borders' => $this->borders(),
        ]);
        $ws->getRowDimension(4)->setRowHeight(18);
    }

    // ═══ CABECERAS (fila 6) ═══
    private function headers(Worksheet $ws, array $ecs, int $nEc): void
    {
        $fila = 6;
        $headers = array_merge(
            ['#', 'Apellidos y Nombres', 'Nota Asistencia'],
            array_column($ecs, 'nombre'),
            ['Nota Académica', 'Nota Final', 'Estado']
        );

        foreach ($headers as $idx => $titulo) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $ws->setCellValue("{$col}{$fila}", $titulo);
            $ws->getStyle("{$col}{$fila}")->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 9, 'bold' => true],
                'fill'      => $this->fill(self::COLOR_DARK),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => $this->borders(),
            ]);
        }

        $ws->getRowDimension($fila)->setRowHeight(36);
    }

    // ═══ DATOS ESTUDIANTES ═══
    private function filasDatos(Worksheet $ws, array $ecs, array $alumnos, int $nEc): void
    {
        foreach ($alumnos as $i => $est) {
            $row      = 7 + $i;
            $notasMap = collect($est['notas_ec'])->pluck('puntaje', 'id_elemento_competencia')->toArray();

            $celdas = array_merge(
                [$i + 1, $est['nombre_completo'], $est['nota_asistencia']],
                array_map(fn($ec) => $notasMap[$ec['id']] ?? null, $ecs),
                [$est['nota_academica'] ?? null, $est['nota_final'] ?? null, $est['estado'] ?? '']
            );

            foreach ($celdas as $colIdx => $valor) {
                $col   = Coordinate::stringFromColumnIndex($colIdx + 1);
                $coord = "{$col}{$row}";

                $ws->setCellValue($coord, $valor);
                $ws->getStyle($coord)->applyFromArray([
                    'font'      => ['name' => 'Arial', 'size' => 9],
                    'alignment' => [
                        'horizontal' => $colIdx === 1 ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => $this->borders(),
                ]);
            }

            $ws->getRowDimension($row)->setRowHeight(18);
        }
    }

    // ═══ RESUMEN ═══
    private function resumen(Worksheet $ws, array $alumnos, int $nEc): void
    {
        $row       = 7 + count($alumnos) + 1;
        $lastCol   = Coordinate::stringFromColumnIndex(6 + $nEc);
        $aprobados = count(array_filter($alumnos, fn($e) => ($e['estado'] ?? '') === 'Aprobado'));
        $total     = count($alumnos);

        $ws->mergeCells("B{$row}:{$lastCol}{$row}");
        $ws->setCellValue("B{$row}", "Total: {$total}   |   Aprobados: {$aprobados}   |   Reprobados: " . ($total - $aprobados));

        $ws->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'fill'      => $this->fill(self::COLOR_DARK),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => $this->borders(),
        ]);

        $ws->getRowDimension($row)->setRowHeight(20);
    }

    // ═══ ANCHOS ═══
    private function anchos(Worksheet $ws, int $nEc): void
    {
        $ws->getColumnDimension('A')->setWidth(10);
        $ws->getColumnDimension('B')->setWidth(34);
        $ws->getColumnDimension('C')->setWidth(16);

        for ($i = 0; $i < $nEc; $i++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex(4 + $i))->setWidth(22);
        }

        $ws->getColumnDimension(Coordinate::stringFromColumnIndex(4 + $nEc))->setWidth(16);
        $ws->getColumnDimension(Coordinate::stringFromColumnIndex(5 + $nEc))->setWidth(12);
        $ws->getColumnDimension(Coordinate::stringFromColumnIndex(6 + $nEc))->setWidth(12);
    }

    // ═══ CONFIGURAR PÁGINA ═══
    private function configurarPagina(Worksheet $ws): void
    {
        $ws->getPageSetup()
           ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
           ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
           ->setFitToPage(true)
           ->setFitToWidth(1)
           ->setFitToHeight(0);
    }

    // ═══ HELPERS ═══
    private function fill(string $argb): array
    {
        return ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb]];
    }

    private function borders(): array
    {
        return ['allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color'       => ['argb' => self::COLOR_BLACK],
        ]];
    }
}