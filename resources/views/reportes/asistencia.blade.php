<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: "Arial Narrow", Arial, sans-serif;
        font-size: 8px;
        color: #000;
        background: #fff;
        padding: 14px 18px;
    }

    /* ── HEADER ── */
    .header {
        display: table;
        width: 100%;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #000;
    }
    .header-logo {
        display: table-cell;
        width: 120px;
        vertical-align: middle;
    }
    .header-center {
        display: table-cell;
        text-align: center;
        vertical-align: middle;
    }
    .header-center h1 {
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #000;
    }
    .header-center .sub {
        font-size: 7px;
        color: #444;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-top: 2px;
    }

    /* ── FICHA DE DATOS ── */
    .ficha {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
        border: 1px solid #999;
    }
    .ficha td {
        padding: 4px 6px;
        border: 1px solid #ccc;
    }
    .ficha .lbl {
        font-weight: 700;
        color: #000;
        white-space: nowrap;
        text-transform: uppercase;
        font-size: 7px;
        background: #f0f0f0;
        width: 1%;
    }
    .ficha .val {
        font-size: 8px;
        font-weight: 600;
        color: #000;
    }

    /* ── LEYENDA ── */
    .leyenda {
        margin-bottom: 8px;
        padding: 4px 8px;
        border: 1px solid #ccc;
        font-size: 7px;
        background: #fafafa;
        display: flex;
        gap: 14px;
        align-items: center;
    }
    .leyenda strong {
        display: inline-block;
        width: 14px;
        height: 14px;
        text-align: center;
        line-height: 14px;
        font-size: 7px;
        font-weight: 900;
        border: 1px solid #aaa;
        background: #fff;
        color: #000;
    }
    .leyenda span {
        color: #333;
    }

    /* ── TABLA PRINCIPAL CORREGIDA CON TEXTO NEGRO ── */
    table.att {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5px;
        table-layout: fixed;
    }

    /* Cabecera de la tabla (Sigue la línea oscura oficial) */
    table.att thead tr th {
        background: #000000;
        color: #ffffff;
        border: 1px solid #333333;
        padding: 6px 2px;
        text-align: center;
        font-size: 6.5px;
        font-weight: 900;
        text-transform: uppercase;
    }
    
    table.att .th-num {
        width: 3%;
    }
    table.att .th-name {
        text-align: left;
        padding-left: 5px;
        width: 35%;
    }
    table.att .th-date {
        font-size: 6px;
        font-weight: bold;
        white-space: nowrap;
        padding: 6px 0;
    }
    table.att .th-pct {
        width: 5%;
    }

    /* Celdas del cuerpo: Texto estrictamente NEGRO */
    table.att tbody tr td {
        border: 1px solid #cccccc;
        padding: 0;
        height: 14px;
        line-height: 14px;
        text-align: center;
        vertical-align: middle;
        font-size: 7.5px;
        color: #000000 !important; /* Forza a que todo sea texto negro */
        background: #ffffff;
    }

    /* Filas alternas para mejor lectura visual */
    table.att tbody tr:nth-child(even) td {
        background: #f9f9f9;
    }

    /* Celda de numeración: Fondo claro, número NEGRO bien marcado */
    table.att tbody tr td.td-num {
        color: #000000 !important;
        background: #f0f0f0 !important;
        font-size: 7px;
        font-weight: 900;
        border-right: 1px solid #999999;
    }
    
    /* Celda de nombres: Texto Negro */
    table.att tbody tr td.td-name {
        text-align: left;
        padding-left: 5px;
        font-weight: 700;
        color: #000000 !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border-right: 1px solid #cccccc;
    }

    /* Celda de porcentajes: Fondo claro, número NEGRO fuerte */
    table.att tbody tr td.td-pct {
        font-weight: 900;
        font-size: 7.5px;
        color: #000000 !important;
        background: #e9e9e9 !important;
        border-left: 1px solid #999999;
    }

    /* Estados de asistencia */
    .cP { font-weight: 900; color: #000000 !important; }
    .cA { font-weight: 900; color: #000000 !important; }
    .cT { font-weight: 900; color: #000000 !important; }
    .cJ { font-weight: 900; color: #000000 !important; }
    .c_ { color: #888888 !important; font-size: 6.5px; }

    /* Totales en la parte inferior: Texto Negro */
    table.att tfoot tr td {
        background: #dcdcdc !important;
        border: 1px solid #999999;
        font-weight: 900;
        font-size: 7px;
        text-align: center;
        height: 14px;
        line-height: 14px;
        vertical-align: middle;
        color: #000000 !important;
    }
    table.att tfoot tr td.td-total-label {
        text-align: right;
        padding-right: 6px;
        font-size: 6.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-right: 1px solid #999999;
        color: #000000 !important;
    }

    /* ── FIRMA ── */
    .firma {
        margin-top: 48px;
        text-align: center;
    }
    .firma .linea {
        width: 220px;
        border-top: 1px solid #333;
        margin: 0 auto 4px;
    }
    .firma .nombre {
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .firma .cargo {
        font-size: 7px;
        color: #555;
        margin-top: 2px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="header">
    <div class="header-logo">
        <img src="{{ public_path('empresa/logo_largo.png') }}"
             style="max-width:110px; max-height:45px;" />
    </div>
    <div class="header-center">
        <h1>Registro de Asistencia Estudiantil</h1>
        <div class="sub">Documento Oficial — Uso Académico Exclusivo</div>
    </div>
</div>

{{-- ── FICHA ── --}}
<table class="ficha">
    <tr>
        <td class="lbl">Carrera</td>
        <td class="val" style="width:200px">{{ strtoupper($carrera) }}</td>
        <td width="6"></td>
        <td class="lbl">Docente</td>
        <td class="val" style="width:180px">{{ strtoupper($docente) }}</td>
        <td width="6"></td>
        <td class="lbl">Turno</td>
        <td class="val">{{ strtoupper($turno ?? '—') }}</td>
    </tr>
    <tr>
        <td class="lbl">Módulo</td>
        <td class="val">{{ strtoupper($asignatura) }}</td>
        <td></td>
        <td class="lbl">Período Acad.</td>
        <td class="val">{{ $periodo }}</td>
        <td></td>
        <td class="lbl">Paralelo</td>
        <td class="val">{{ strtoupper($paralelo) }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha Inicio</td>
        <td class="val">{{ $fecha_inicio }}</td>
        <td></td>
        <td class="lbl">Fecha Final</td>
        <td class="val">{{ $fecha_fin }}</td>
        <td></td>
        <td></td><td></td>
    </tr>
</table>

{{-- ── LEYENDA ── --}}
<div class="leyenda">
    <strong>P</strong><span>Presente</span>
    <strong>A</strong><span>Ausente</span>
    <strong>T</strong><span>Tardanza</span>
    <strong>J</strong><span>Justificado</span>
    <strong style="color:#bbb">–</strong><span>Sin registro</span>
</div>

{{-- ── TABLA CON TEXTO NEGRO FIJO ── --}}
<table class="att">
    <thead>
        <tr>
            <th class="th-num">N°</th>
            <th class="th-name">Apellidos y Nombre</th>
            @foreach ($fechas as $fecha)
                <th class="th-date">{{ \Carbon\Carbon::parse($fecha)->format('d/m') }}</th>
            @endforeach
            <th class="th-pct">%</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($filas as $i => $fila)
            <tr>
                <td class="td-num">{{ $i + 1 }}</td>
                <td class="td-name">{{ strtoupper($fila['nombre']) }}</td>
                @foreach ($fechas as $fecha)
                    @php $e = $fila['asistencias'][$fecha] ?? null; @endphp
                    <td class="td-sess c{{ $e ?? '_' }}">{{ $e ?? '–' }}</td>
                @endforeach
                @php
                    $p   = $fila['porcentaje'];
                    $cls = $p >= 75 ? 'pHi' : ($p >= 50 ? 'pMed' : 'pLow');
                @endphp
                <td class="td-pct {{ $cls }}">{{ $p }}%</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 2 + count($fechas) + 1 }}"
                    style="text-align:center;padding:10px;color:#888">
                    No hay estudiantes registrados.
                </td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="td-total-label">Total Presentes por Sesión →</td>
            @foreach ($fechas as $fecha)
                @php
                    $total = collect($filas)
                        ->filter(fn($f) => ($f['asistencias'][$fecha] ?? null) === 'P')
                        ->count();
                @endphp
                <td class="td-sess">{{ $total }}</td>
            @endforeach
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- ── FIRMA ── --}}
<div class="firma">
    <div class="linea"></div>
    <div class="nombre">{{ strtoupper($docente) }}</div>
    <div class="cargo">Docente Responsable</div>
</div>

</body>
</html>