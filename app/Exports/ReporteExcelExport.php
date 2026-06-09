<?php

declare(strict_types=1);

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Genera la planilla de calificaciones en formato .xlsx.
 * Compatible con PhpSpreadsheet 2.x (sin getCellByColumnAndRow).
 *
 * Uso:
 *   return (new ReporteExcelExport($datos))->download("planilla.xlsx");
 */
class ReporteExcelExport
{
    private const COLOR_HEADER_DARK  = 'FF1F3864';
    private const COLOR_HEADER_MID   = 'FF2E75B6';
    private const COLOR_HEADER_LIGHT = 'FFD9E1F2';
    private const COLOR_APROBADO     = 'FFE2EFDA';
    private const COLOR_REPROBADO    = 'FFFCE4D6';
    private const COLOR_NOTA_FINAL   = 'FFFFF2CC';
    private const COLOR_WHITE        = 'FFFFFFFF';
    private const COLOR_BLACK        = 'FF000000';

    private Spreadsheet $spreadsheet;

    public function __construct(private readonly array $datos) {}

    // ── API pública ───────────────────────────────────────────────────────────

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

    public function guardar(string $ruta): void
    {
        $this->build();
        (new Xlsx($this->spreadsheet))->save($ruta);
    }

    // ── Construcción ─────────────────────────────────────────────────────────

    private function build(): void
    {
        $this->spreadsheet = new Spreadsheet();
        $ws  = $this->spreadsheet->getActiveSheet();
        $ws->setTitle('Planilla de Notas');

        $ecs     = collect($this->datos['elementos_competencia'])->toArray();
        $alumnos = collect($this->datos['estudiantes'])->toArray();
        $nEc     = count($ecs);
        $lastCol = 6 + $nEc;   // #, Nombre, Asist, [ECs], Acad, Final, Estado

        $this->titulo($ws, $lastCol);
        $this->metadatos($ws);
        $this->headers($ws, $ecs, $nEc);
        $this->filasDatos($ws, $ecs, $alumnos, $nEc);
        $this->resumen($ws, $alumnos, $nEc);
        $this->anchos($ws, $nEc);

        $ws->freezePane('C5');
    }

    // ── Secciones ─────────────────────────────────────────────────────────────

    /** Fila 1: título fusionado */
    private function titulo(Worksheet $ws, int $lastCol): void
    {
        $end = Coordinate::stringFromColumnIndex($lastCol);
        $ws->mergeCells("A1:{$end}1");
        $ws->setCellValue('A1', 'PLANILLA DE CALIFICACIONES');
        $ws->getRowDimension(1)->setRowHeight(26);
        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 14, 'bold' => true,
                            'color' => ['argb' => self::COLOR_WHITE]],
            'fill'      => $this->fill(self::COLOR_HEADER_DARK),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
    }

    /** Fila 2: Grupo, Gestión, Materia, Carrera */
    private function metadatos(Worksheet $ws): void
    {
        $items = [
            ['col' => 'A', 'label' => 'Grupo:',   'value' => $this->datos['grupo']['nombre']],
            ['col' => 'C', 'label' => 'Gestión:', 'value' => $this->datos['grupo']['gestion']],
            ['col' => 'E', 'label' => 'Materia:', 'value' => $this->datos['materia']['nombre']],
            ['col' => 'G', 'label' => 'Carrera:', 'value' => $this->datos['carrera'] ?? '—'],
        ];

        foreach ($items as $item) {
            $col      = $item['col'];
            $label    = $item['label'];
            $value    = $item['value'];
            $nextCol  = Coordinate::stringFromColumnIndex(
                            Coordinate::columnIndexFromString($col) + 1
                        );

            $ws->setCellValue("{$col}2", $label);
            $ws->getStyle("{$col}2")->getFont()->setBold(true)->setName('Arial')->setSize(10);

            $ws->setCellValue("{$nextCol}2", $value);
            $ws->getStyle("{$nextCol}2")->getFont()->setName('Arial')->setSize(10);
        }

        $ws->getRowDimension(2)->setRowHeight(18);
    }

    /** Fila 4: encabezados de columnas */
    private function headers(Worksheet $ws, array $ecs, int $nEc): void
    {
        $headers = array_merge(
            ['#', 'Apellidos y Nombres', 'Nota Asistencia'],
            array_column($ecs, 'nombre'),
            ['Nota Académica', 'Nota Final', 'Estado']
        );

        foreach ($headers as $idx => $titulo) {
            $col   = Coordinate::stringFromColumnIndex($idx + 1);
            $isEc  = $idx >= 3 && $idx < (3 + $nEc);
            $color = $isEc ? self::COLOR_HEADER_MID : self::COLOR_HEADER_DARK;

            $ws->setCellValue("{$col}4", $titulo);
            $ws->getStyle("{$col}4")->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true,
                                'color' => ['argb' => self::COLOR_WHITE]],
                'fill'      => $this->fill($color),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                                'wrapText'   => true],
                'borders'   => $this->borders(),
            ]);
        }

        $ws->getRowDimension(4)->setRowHeight(36);
    }

    /** Filas 5+: una fila por estudiante */
    private function filasDatos(Worksheet $ws, array $ecs, array $alumnos, int $nEc): void
    {
        $colNotaFinal = 4 + $nEc + 1;   // 1-indexed

        foreach ($alumnos as $i => $est) {
            $row        = 5 + $i;
            $aprobado   = ($est['estado'] ?? '') === 'Aprobado';
            $colorFila  = $aprobado ? self::COLOR_APROBADO : self::COLOR_REPROBADO;
            $notasMap   = collect($est['notas_ec'])
                            ->pluck('puntaje', 'id_elemento_competencia')
                            ->toArray();

            $celdas = array_merge(
                [$i + 1, $est['nombre_completo'], $est['nota_asistencia']],
                array_map(fn($ec) => $notasMap[$ec['id']] ?? null, $ecs),
                [$est['nota_academica'] ?? null, $est['nota_final'] ?? null, $est['estado'] ?? '']
            );

            foreach ($celdas as $colIdx => $valor) {
                $colNum = $colIdx + 1;
                $col    = Coordinate::stringFromColumnIndex($colNum);
                $coord  = "{$col}{$row}";

                $ws->setCellValue($coord, $valor);

                $colorCelda = ($colNum === $colNotaFinal)
                    ? self::COLOR_NOTA_FINAL
                    : $colorFila;

                $ws->getStyle($coord)->applyFromArray([
                    'font'      => ['name' => 'Arial', 'size' => 10,
                                    'color' => ['argb' => self::COLOR_BLACK]],
                    'fill'      => $this->fill($colorCelda),
                    'alignment' => [
                        'horizontal' => $colNum === 2
                            ? Alignment::HORIZONTAL_LEFT
                            : Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => $this->borders(),
                ]);
            }

            $ws->getRowDimension($row)->setRowHeight(18);
        }
    }

    /** Fila resumen debajo de los datos */
    private function resumen(Worksheet $ws, array $alumnos, int $nEc): void
    {
        $row       = 5 + count($alumnos) + 1;
        $lastCol   = Coordinate::stringFromColumnIndex(6 + $nEc);
        $aprobados = count(array_filter($alumnos, fn($e) => ($e['estado'] ?? '') === 'Aprobado'));
        $total     = count($alumnos);

        $ws->mergeCells("B{$row}:{$lastCol}{$row}");
        $ws->setCellValue("B{$row}",
            "Total: {$total}   |   Aprobados: {$aprobados}   |   Reprobados: " . ($total - $aprobados));

        $ws->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 10, 'bold' => true],
            'fill'      => $this->fill(self::COLOR_HEADER_LIGHT),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);

        $ws->getRowDimension($row)->setRowHeight(18);
    }

    /** Anchos de columna */
    private function anchos(Worksheet $ws, int $nEc): void
    {
        $ws->getColumnDimension('A')->setWidth(4);
        $ws->getColumnDimension('B')->setWidth(34);
        $ws->getColumnDimension('C')->setWidth(16);

        for ($i = 0; $i < $nEc; $i++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex(4 + $i))->setWidth(22);
        }

        $ws->getColumnDimension(Coordinate::stringFromColumnIndex(4 + $nEc))->setWidth(16);
        $ws->getColumnDimension(Coordinate::stringFromColumnIndex(5 + $nEc))->setWidth(12);
        $ws->getColumnDimension(Coordinate::stringFromColumnIndex(6 + $nEc))->setWidth(12);
    }

    // ── Helpers de estilo ─────────────────────────────────────────────────────

    private function fill(string $argb): array
    {
        return ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb]];
    }

    private function borders(): array
    {
        return ['allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color'       => ['argb' => 'FF888888'],
        ]];
    }
}