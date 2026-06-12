<?php

namespace App\Exports;

use Mpdf\Mpdf;

class CalificacionesPdfExport
{
    public function __construct(
        private array $datos,
        private ?string $gestion = null
    ) {}

    public function download(string $filename = 'centralizador.pdf'): \Illuminate\Http\Response
    {
        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_top'    => 10,
            'margin_bottom' => 8,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'default_font'  => 'arial',
        ]);

        $mpdf->WriteHTML($this->buildHTML());

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function getLogoBase64(): string
    {
        $path = public_path('empresa/logo_largo.png');
        if (!file_exists($path)) return '';
        $data = base64_encode(file_get_contents($path));
        return 'data:image/png;base64,' . $data;
    }

    private function buildHTML(): string
    {
        $css = '
<style>
body {
    font-family: Arial, sans-serif;
    font-size: 8pt;
    color: #000;
    margin: 0;
    padding: 0;
}
.titulo {
    text-align: center;
    font-size: 13pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    margin: 0;
    padding: 0;
}

.info-tabla {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4pt;
    margin-bottom: 2pt;
}
.info-tabla td {
    border: 1.2pt solid #000;
    padding: 2pt 4pt;
    font-size: 7pt;
    vertical-align: middle;
    height: 12pt;
    line-height: 1.2;
}
.info-tabla td.lbl {
    font-weight: bold;
    text-align: left;
    white-space: nowrap;
    background: #f0f0f0;
    width: 12%;
}
.info-tabla td.val {
    text-align: left;
    text-transform: uppercase;
    width: 21%;
}

table.main {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-top: 2pt;
}
table.main th,
table.main td {
    border: 1.2pt solid #000;
    text-align: center;
    vertical-align: middle;
    padding: 1pt 1pt;
    font-size: 7pt;
    box-sizing: border-box;
}
table.main th {
    font-weight: bold;
    background: #fff;
}
table.main td {
    height: 14pt;
}
table.main td.nombre {
    text-align: left;
    padding-left: 3pt;
    text-transform: uppercase;
    font-size: 6.5pt;
}

th.vert {
    width: 20pt;
    height: 110pt;
    vertical-align: bottom;
    padding: 0 0 2pt 0;
}
th.vert div {
    writing-mode: tb-rl;
    white-space: nowrap;
    font-size: 6.5pt;
    font-weight: bold;
    text-align: left;
    overflow: hidden;
    padding: 0;
    margin: 0;
}

.tf {
    display: block;
    font-size: 7pt;
    font-weight: bold;
    line-height: 1.1;
    word-wrap: break-word;
    padding: 0 1pt;
}

.page-break {
    page-break-after: always;
}
</style>';

        $html  = $css;
        $total = count($this->datos);
        $i     = 0;

        $logoSrc  = $this->getLogoBase64();
        $logoHtml = $logoSrc
            ? '<img src="' . $logoSrc . '" style="height:38pt; max-width:130pt;" />'
            : '';

        foreach ($this->datos as $data) {
            $carrera     = (array)($data['carrera']);
            $materias    = array_map(fn($m) => (array)$m, $data['materias']);
            $estudiantes = array_map(fn($e) => (array)$e, $data['estudiantes']);
            $turno       = strtoupper($data['turno']   ?? 'MAÑANA');
            $gestion     = strtoupper($data['gestion'] ?? ($this->gestion ?? ''));

            $html .= '
<table style="width:100%; border:none; border-collapse:collapse; margin-bottom:3pt;">
  <tr>
    <td style="width:25%; border:none; vertical-align:middle; text-align:left;">
      ' . $logoHtml . '
    </td>
    <td style="border:none; vertical-align:middle; text-align:center;">
      <div class="titulo">CENTRALIZADOR DE CALIFICACIONES</div>
    </td>
    <td style="width:25%; border:none;"></td>
  </tr>
</table>';

           
$html .= '
<table class="info-tabla">
  <colgroup>
    <col style="width:13%;">
    <col style="width:20%;">
    <col style="width:20%;">
    <col style="width:13%;">
    <col style="width:21%;">
  </colgroup>
  <tr>
    <td class="lbl">INSTITUCIÓN:</td>
    <td class="val" colspan="2" style="font-weight:bold;">INSTITUTO TECNOLÓGICO DEL SUR</td>
    <td class="lbl">TURNO:</td>
    <td class="val">' . $turno . '</td>
  </tr>
  <tr>
    <td class="lbl">CARRERA:</td>
    <td class="val" colspan="2" style="font-weight:bold;">' . strtoupper($carrera['nombre']) . '</td>
    <td class="lbl">CARÁCTER:</td>
    <td class="val">PRIVADO</td>
  </tr>
  <tr>
    <td class="lbl">GESTIÓN:</td>
    <td class="val">' . $gestion . '</td>
    <td class="val"></td>
    <td class="lbl">NIVEL:</td>
    <td class="val">TÉCNICO SUPERIOR</td>
  </tr>
  <tr>
    <td class="lbl">CURSO:</td>
    <td class="val">PRIMERO</td>
    <td class="val"></td>
    <td class="lbl">RÉGIMEN:</td>
    <td class="val">' . strtoupper($carrera['regimen']) . '</td>
  </tr>
</table>';

            $html .= '<table class="main"><thead><tr>';
            $html .= '<th width="3%" rowspan="2"><div class="tf">N°</div></th>';
            $html .= '<th width="22%" rowspan="2"><div class="tf">NÓMINA DE ESTUDIANTES</div></th>';
            $html .= '<th width="8%" rowspan="2"><div class="tf">CÉDULA DE IDENTIDAD</div></th>';

            foreach ($materias as $mat) {
                $html .= '<th style="font-size:6pt;height:14pt;font-weight:bold;padding:1pt;">'
                       . htmlspecialchars($mat['codigo'] ?? '') . '</th>';
            }

            $html .= '<th width="7%" rowspan="2"><div class="tf">ESTADO</div></th>';
            $html .= '<th width="7%" rowspan="2"><div class="tf">OBSERVACIONES</div></th>';
            $html .= '</tr><tr>';

            foreach ($materias as $mat) {
                $nombre = strtoupper(trim($mat['nombreMateria'] ?? ''));
                $html  .= '<th class="vert"><div>' . htmlspecialchars($nombre) . '</div></th>';
            }

            $html .= '</tr></thead><tbody>';

            $n = 1;
            foreach ($estudiantes as $est) {
                $estado = strtoupper($est['estado'] ?? '');
                $color  = $estado === 'REPROBADO' ? 'color:#c00;' : '';

                $html .= '<tr>';
                $html .= '<td>' . $n++ . '</td>';
                $html .= '<td class="nombre">' . htmlspecialchars($est['nombreEstudiante'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($est['carnet'] ?? '') . '</td>';

                foreach ($materias as $mat) {
                    $codigo  = $mat['codigo'] ?? ('M' . ($mat['idMateria'] ?? ''));
                    $nota    = $est[$codigo] ?? '';
                    $display = is_numeric($nota)
                        ? number_format((float)$nota, 2)
                        : ($nota ?: '--');
                    $html .= '<td style="font-weight:bold;">' . $display . '</td>';
                }

                $html .= '<td><strong style="' . $color . '">' . $estado . '</strong></td>';
                $html .= '<td></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            if (++$i < $total) {
                $html .= '<div class="page-break"></div>';
            }
        }

        return $html;
    }
}