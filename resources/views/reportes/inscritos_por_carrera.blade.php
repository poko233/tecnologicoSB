<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inscritos por Carrera</title>
    <style>
        @page { size: portrait; margin: 12mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; margin: 0; }
        
        /* ── ENCABEZADO ── */
        .encabezado {
            width: 100%;
            border: 1.5px solid #000;
            margin-bottom: 8px;
        }
        .encabezado td {
            padding: 6px;
            vertical-align: middle;
        }
        .logo-cell {
            width: 80px;
            text-align: center;
            border-right: 1.5px solid #000;
        }
        .logo-cell img {
            height: 50px;
        }
        .titulo-cell {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
        }
        
        /* ── INFO ── */
        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .info td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 9px;
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .info .label {
            width: 80px;
            text-align: right;
            padding-right: 8px;
        }
        
        /* ── TABLA ── */
        .tabla {
            width: 100%;
            border-collapse: collapse;
        }
        .tabla th {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            background-color: #ddd;
        }
        .tabla td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: center;
            font-size: 8px;
        }
        .tabla td.nombre {
            text-align: left;
        }
        
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@foreach($datos as $data)
    @php
        $carrera = $data['carrera'];
        $estudiantes = $data['estudiantes'];
        $logoPath = public_path('empresa/logo_largo.png');
    @endphp

    {{-- ═══ ENCABEZADO: LOGO + TÍTULO ═══ --}}
    <table class="encabezado">
        <tr>
            <td class="logo-cell">
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo">
                @endif
            </td>
            <td class="titulo-cell">
                INSCRITOS POR CARRERA
            </td>
        </tr>
    </table>

    {{-- ═══ INFORMACIÓN ═══ --}}
    <table class="info">
        <tr>
            <td class="label">CARRERA:</td>
            <td>{{ strtoupper($carrera['nombre']) }}</td>
            <td class="label">TOTAL:</td>
            <td>{{ count($estudiantes) }} inscritos</td>
            <td class="label">FECHA:</td>
            <td>{{ $fecha }}</td>
        </tr>
    </table>

    {{-- ═══ TABLA DE ESTUDIANTES ═══ --}}
    <table class="tabla">
        <thead>
            <tr>
                <th width="5%">N°</th>
                <th width="15%">CARNET</th>
                <th width="38%">ESTUDIANTE</th>
                <th width="18%">FECHA INSCRIPCIÓN</th>
                <th width="12%">GESTIÓN</th>
                <th width="12%">TURNO</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 1; @endphp
            @foreach($estudiantes as $est)
                <tr>
                    <td>{{ $n++ }}</td>
                    <td>{{ $est['carnet'] ?? '' }}</td>
                    <td class="nombre">{{ $est['estudiante'] ?? '' }}</td>
                    <td>{{ $est['fecha_inscripcion'] ?? '' }}</td>
                    <td>{{ $est['gestion'] ?? '' }}</td>
                    <td>{{ $est['turno'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>