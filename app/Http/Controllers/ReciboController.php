<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\EmpresaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;

class ReciboController extends Controller
{
    /**
     * Generar y descargar el recibo en PDF (Original y Copia) usando Dompdf.
     */
    public function descargar(int $idPago, EmpresaService $empresaService)
    {
        // CONTROL CRÍTICO: Verificar si el servidor tiene la extensión GD instalada
        if (!extension_loaded('gd')) {
            return response()->json([
                'success' => false,
                'message' => 'Error de configuración del servidor: La extensión PHP-GD no está activa. Comuníquese con el administrador del sistema.'
            ], 500);
        }

        // ── 1. Obtener el pago con sus relaciones ─────────────────────────────
        $pago = Pago::with([
            'cuotas',
            'usuario:id,nombres,apellidoPaterno,apellidoMaterno,ci,matricula',
        ])->findOrFail($idPago);

        if ($pago->cuotas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Este pago no tiene cuotas asociadas en la base de datos.'
            ], 422);
        }

        $cuota      = $pago->cuotas->first();
        $estudiante = $pago->usuario;
        $empresa    = $empresaService->obtener();

        // ── 2. Procesar el Logo en Base64 para Dompdf ──────────────────────────
        $logoBase64 = null;
        $nombreLogo = $empresa->LOGO_CUADRADO ?? 'logo_cuadrado.png'; 
        $pathLogo = public_path("empresa/{$nombreLogo}");

        if (file_exists($pathLogo)) {
            $type       = pathinfo($pathLogo, PATHINFO_EXTENSION);
            $data       = file_get_contents($pathLogo);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // ── 3. Obtener Carrera ────────────────────────────────────────────────
        $idCarrera = $cuota->idCarrera ?? DB::table('CarreraUsuario')->where('idUsuario', $estudiante->id)->value('idCarrera');
        
        $carrera = DB::table('Carrera')
            ->where('idCarrera', $idCarrera)
            ->value('nombreCarrera');

        // ── 4. Número de boleta correlativo ───────────────────────────────────
        $numeroBoleta = Pago::where('id', '<=', $pago->id)->count();

        // ── 5. Formatear Datos ────────────────────────────────────────────────
        $fecha    = Carbon::parse($pago->created_at)->format('d/m/Y');
        $hora     = Carbon::parse($pago->created_at)->format('H:i');
        $metodo   = strtoupper($pago->metodo);
        $total    = (float) $pago->monto;
        $enLetras = strtoupper(self::numeroALetras($total));

        $nombreEstudiante = strtoupper(trim(
            $estudiante->nombres . ' ' .
            $estudiante->apellidoPaterno . ' ' .
            $estudiante->apellidoMaterno
        ));

        // Construir filas de conceptos
        $filasPago = $pago->cuotas->map(function ($c) {
            $mes = $c->fecha_vencimiento
                ? strtoupper(Carbon::parse($c->fecha_vencimiento)->locale('es')->isoFormat('MMMM/YYYY'))
                : '';
            $concepto = $c->tipo === 'MATRICULA'
                ? 'PAGO MATRÍCULA ' . Carbon::parse($c->fecha_vencimiento)->year
                : "PAGO MENSUALIDAD MES {$mes}";
            return ['concepto' => $concepto, 'monto' => (float) $c->monto];
        });

        $filasHtml = '';
        foreach ($filasPago as $fila) {
            $montoFmt  = number_format($fila['monto'], 2, '.', ',');
            $filasHtml .= "
            <tr>
                <td class='concepto'>{$fila['concepto']}</td>
                <td class='monto'>{$montoFmt} Bs.</td>
            </tr>";
        }

        $totalFmt = number_format($total, 2, '.', ',');

        // ── 6. Definir el Bloque HTML de cada lado ────────────────────────────
        $bloqueHtml = function (string $tipo) use (
            $empresa, $numeroBoleta, $fecha, $hora, $metodo,
            $nombreEstudiante, $estudiante, $carrera,
            $filasHtml, $totalFmt, $enLetras, $logoBase64
        ): string {
            $nombreEmpresa  = strtoupper($empresa->EMPRESA ?? 'INSTITUCIÓN');
            $siglaEmpresa   = strtoupper($empresa->SIGLA  ?? '');
            $dirEmpresa     = strtoupper($empresa->DIRECCION ?? '');
            $telEmpresa     = $empresa->TELEFONO ?? $empresa->CELULAR ?? '';
            $ciudad         = 'COCHABAMBA';
            $codigo         = $estudiante->matricula ?? '—';
            $ci             = $estudiante->ci ?? '—';

            $logoHtml = $logoBase64 
                ? "<img src='{$logoBase64}' class='logo-img' alt='Logo'>" 
                : "<div class='logo-circle'>LOGO</div>";

            return "
            <div class='recibo-box'>
                <table class='header-table'>
                    <tr>
                        <td class='logo-cell'>{$logoHtml}</td>
                        <td class='empresa-cell'>
                            <strong>{$nombreEmpresa}</strong><br>
                            <span class='sigla'>\"{$siglaEmpresa}\"</span><br>
                            Telf: {$telEmpresa}
                        </td>
                        <td class='titulo-cell'>
                            <div class='titulo-ingreso'>INGRESO</div>
                            <div class='titulo-boleta'>BOLETA</div>
                            <div class='titulo-num'>{$numeroBoleta}</div>
                            <div class='titulo-tipo'>{$tipo}</div>
                        </td>
                    </tr>
                </table>

                <hr class='divisor'>

                <table class='datos-table'>
                    <tr>
                        <td class='label'>Estudiante:</td>
                        <td class='valor' colspan='3'>{$nombreEstudiante}</td>
                    </tr>
                    <tr>
                        <td class='label'>C.I.:</td>
                        <td class='valor'>{$ci}</td>
                        <td class='label'>Código:</td>
                        <td class='valor'>{$codigo}</td>
                    </tr>
                    <tr>
                        <td class='label'>Carrera:</td>
                        <td class='valor' colspan='3'>" . strtoupper($carrera ?? '—') . "</td>
                    </tr>
                </table>

                <table class='info-table'>
                    <thead>
                        <tr>
                            <th>Lugar y Fecha</th>
                            <th>Tipo Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{$ciudad} - {$fecha} {$hora}</td>
                            <td class='metodo'>{$metodo}</td>
                        </tr>
                    </tbody>
                </table>

                <table class='pagos-table'>
                    <thead>
                        <tr>
                            <th class='concepto'>Concepto</th>
                            <th class='monto'>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$filasHtml}
                        <tr class='total-row'>
                            <td class='concepto'>TOTAL</td>
                            <td class='monto'>{$totalFmt} Bs.</td>
                        </tr>
                    </tbody>
                </table>

                <p class='son'>Son: {$enLetras} Bolivianos.</p>

                <table class='firmas-table'>
                    <tr>
                        <td class='firma'>
                            <div class='linea-firma'></div>
                            RECIBI CONFORME
                        </td>
                        <td class='firma'>
                            <div class='linea-firma'></div>
                            ENTREGUE CONFORME
                        </td>
                    </tr>
                </table>
            </div>";
        };

        // ── 7. CSS Calibrado - Sin Bordes de Caja y Anchura Segura ────────────
        $html = '<!DOCTYPE html>
        <html lang="es">
        <head>
        <meta charset="UTF-8">
        <style>
            /* Reseteo absoluto de márgenes internos */
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            body { 
                font-family: Helvetica, Arial, sans-serif; 
                font-size: 6.2pt;
                color: #000;
                background-color: #fff;
            }
            
            /* Definimos la página vertical con márgenes globales seguros */
            // @page { size: letter portrait; margin: 6mm 4mm 0 4mm; }
            @page { size: letter portrait; margin: 8mm 6mm 4mm 6mm; }

            .page-container {
                width: 100%;
                height: 100%;
            }

            /* Bloque maestro superior que delimita la media hoja horizontal */
            .seccion-superior {
                width: 100%;
                height: 128mm;
                border-bottom: 1.2pt dashed #999; /* Línea de puntos central de corte */
                display: block;
            }

            /* Reducimos el ancho a 46% para empujar la copia hacia adentro */
            .columna-recibo-izq {
                float: left;
                width: 48%;
                box-sizing: border-box;
            }

            .columna-recibo-der {
                float: right;
                width: 48%;
                box-sizing: border-box;
                margin-right: 4mm; /* Sobre espacio de seguridad para que entre completo */
            }

            /* QUITADOS LOS BORDES EXTERNOS DE AQUÍ PARA QUE QUEDE LIMPIO */
            .recibo-box { 
                width: 100%;
                height: 122mm;
                padding: 2mm 2mm;
                box-sizing: border-box;
                background-color: #fff;
            }

            .clearfix {
                clear: both;
            }

            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
            .logo-cell { width: 13mm; vertical-align: middle; text-align: center; }
            .logo-img { max-width: 12mm; max-height: 12mm; object-fit: contain; }
            
            .empresa-cell { vertical-align: top; padding-left: 1.5mm; line-height: 1.2; font-size: 5.8pt; }
            .empresa-cell strong { font-size: 7.2pt; color: #1a387a; }
            .sigla { font-size: 6.2pt; font-weight: bold; }
            
            .titulo-cell { text-align: right; vertical-align: top; }
            .titulo-ingreso { font-size: 9.5pt; font-weight: bold; color: #1a387a; }
            .titulo-boleta  { font-size: 6pt; color: #555; }
            .titulo-num     { font-size: 13pt; font-weight: bold; }
            .titulo-tipo    { font-size: 7pt; font-weight: bold; color: #1a387a; }
            
            hr.divisor { border: none; border-top: 0.6pt solid #333; margin: 1mm 0; }
            
            .datos-table { width: 100%; border-collapse: collapse; margin-bottom: 2mm; background-color: #fcfcfc; border: 0.5pt solid #bbb; }
            .datos-table td { padding: 1mm 1.2mm; }
            .label { font-weight: bold; white-space: nowrap; width: 18%; color: #333; }
            .valor { padding-left: 0.5mm; }
            
            .info-table { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
            .info-table th, .info-table td { border: 0.5pt solid #333; text-align: center; padding: 1mm; }
            .info-table th { background-color: #e5e5e5; font-size: 6.2pt; font-weight: bold; }
            .metodo { font-weight: bold; }
            
            .pagos-table { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
            .pagos-table th, .pagos-table td { border: 0.5pt solid #333; padding: 1.5mm 1.8mm; }
            .pagos-table thead th { background-color: #e5e5e5; text-align: center; font-weight: bold; }
            .pagos-table td.concepto { text-align: left; }
            .pagos-table td.monto    { text-align: center; font-weight: bold; }
            .pagos-table th.concepto { width: 72%; }
            .pagos-table th.monto    { width: 28%; }
            
            .total-row td { background-color: #1a387a; color: #fff; font-weight: bold; font-size: 7.5pt; }
            
            .son { font-style: italic; font-size: 5.8pt; margin: 2mm 0 3mm 0; }
            
            .firmas-table { width: 100%; border-collapse: collapse; margin-top: 10mm; }
            .firmas-table .firma { text-align: center; width: 50%; font-size: 5.8pt; color: #333; }
            .linea-firma { border-top: 0.6pt solid #000; width: 60%; margin: 0 auto 1mm auto; }
        </style>
        </head>
        <body>
        <div class="page-container">
            
            <div class="seccion-superior">
                
                <div class="columna-recibo-izq">
                    ' . $bloqueHtml('ORIGINAL') . '
                </div>
                
                <div class="columna-recibo-der">
                    ' . $bloqueHtml('COPIA') . '
                </div>
                
                <div class="clearfix"></div>
                
            </div>

        </div>
        </body>
        </html>';

        // ── 8. Renderizar ────────────────────────────────────────────────────
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"boleta_{$idPago}.pdf\"",
        ]);
    }
    /**
     * Convertir número a letras básico en español.
     */
    private static function numeroALetras(float $numero): string
    {
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $decenas  = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $convertirCentenas = function (int $n) use ($unidades, $decenas, $centenas): string {
            if ($n === 0)   return '';
            if ($n === 100) return 'CIEN';
            $c = $centenas[(int)($n / 100)];
            $r = $n % 100;
            if ($r === 0) return $c;
            if ($r < 20)  return ($c ? "$c " : '') . $unidades[$r];
            $d = $decenas[(int)($r / 10)];
            $u = $unidades[$r % 10];
            return ($c ? "$c " : '') . $d . ($u ? " Y $u" : '');
        };

        $entero = (int) $numero;
        $dec    = (int) round(($numero - $entero) * 100);

        if ($entero === 0) {
            $letras = 'CERO';
        } elseif ($entero < 1000) {
            $letras = $convertirCentenas($entero);
        } elseif ($entero < 1000000) {
            $miles = (int)($entero / 1000);
            $resto = $entero % 1000;
            $p     = $miles === 1 ? 'MIL' : $convertirCentenas($miles) . ' MIL';
            $letras = $p . ($resto ? ' ' . $convertirCentenas($resto) : '');
        } else {
            $letras = (string) $entero;
        }

        return "{$letras} " . str_pad($dec, 2, '0', STR_PAD_LEFT) . '/100';
    }
}