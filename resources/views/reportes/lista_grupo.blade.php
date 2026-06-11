<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lista Oficial por Grupo</title>
    <style>
        @page { size: portrait; margin: 12mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; }
        .encabezado { width: 100%; border: 1.5px solid #000; margin-bottom: 8px; }
        .encabezado td { padding: 6px; vertical-align: middle; }
        .logo-cell { width: 80px; text-align: center; border-right: 1.5px solid #000; }
        .logo-cell img { height: 50px; }
        .titulo-cell { text-align: center; font-size: 14px; font-weight: bold; }
        .info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info td { border: 1px solid #000; padding: 4px 8px; font-size: 9px; background: #f5f5f5; }
        .info .label { width: 70px; font-weight: bold; text-align: right; }
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th { border: 1px solid #000; padding: 5px; font-size: 8px; background: #ddd; }
        .tabla td { border: 1px solid #000; padding: 4px 5px; font-size: 8px; }
        .tabla td.nombre { text-align: left; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@foreach($datos as $data)
    @php
        $grupo = $data['grupo'];
        $estudiantes = $data['estudiantes'];
        $logoPath = public_path('empresa/logo_largo.png');
    @endphp

    <table class="encabezado">
        <tr>
            <td class="logo-cell">
                @if(file_exists($logoPath)) <img src="{{ $logoPath }}" alt="Logo"> @endif
            </td>
            <td class="titulo-cell">LISTA OFICIAL POR GRUPO</td>
        </tr>
    </table>

    <table class="info">
        <tr><td class="label">CARRERA:</td><td>{{ $grupo['carrera'] }}</td></tr>
        <tr><td class="label">GRUPO:</td><td>{{ $grupo['grupo'] }}</td></tr>
        <tr><td class="label">MATERIA:</td><td>{{ $grupo['materia'] }}</td></tr>
        <tr><td class="label">DOCENTE:</td><td>{{ $grupo['docente'] }}</td></tr>
        <tr><td class="label">GESTIÓN:</td><td>{{ $grupo['gestion'] }} | TURNO: {{ $grupo['turno'] }}</td></tr>
    </table>

    <table class="tabla">
        <thead>
            <tr>
                <th width="5%">N°</th>
                <th width="42%">NOMBRES Y APELLIDOS</th>
                <th width="15%">CARNET</th>
                <th width="15%">CELULAR</th>
                <th width="23%">OBSERVACIÓN</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 1; @endphp
            @foreach($estudiantes as $est)
                <tr>
                    <td>{{ $n++ }}</td>
                    <td class="nombre">{{ $est['estudiante'] ?? '' }}</td>
                    <td>{{ $est['carnet'] ?? '' }}</td>
                    <td>{{ $est['celular'] ?? '' }}</td>
                    <td>{{ $est['observacion'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!$loop->last) <div class="page-break"></div> @endif
@endforeach

</body>
</html>