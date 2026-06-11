<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { font-family: Arial, sans-serif; font-size: 9px; box-sizing: border-box; }
  body { margin: 0; padding: 10px; }

  /* ── ENCABEZADO CON LOGO ── */
  .header-table {
    width: 100%;
    border: 1.5px solid #000;
    margin-bottom: 6px;
    border-collapse: collapse;
  }
  .header-table td {
    vertical-align: middle;
    padding: 6px;
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
    background: #f5f5f5;
  }

  /* ── META ── */
  .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    border: 1px solid #000;
  }
  .meta-table td {
    padding: 4px 6px;
    border: 1px solid #000;
    background: #fafafa;
  }
  .meta-table .label {
    font-weight: bold;
    width: 60px;
    background: #eee;
  }

  /* ── TABLA NOTAS ── */
  table.notas {
    width: 100%;
    border-collapse: collapse;
  }
  table.notas th {
    background: #ddd;
    padding: 5px 3px;
    text-align: center;
    border: 1px solid #000;
    font-size: 8px;
    font-weight: bold;
  }
  table.notas td {
    border: 1px solid #000;
    padding: 4px 3px;
    text-align: center;
  }
  table.notas td.nombre {
    text-align: left;
  }
  .nota-final {
    font-weight: bold;
  }

  /* ── RESUMEN ── */
  .resumen {
    border: 1px solid #000;
    margin-top: 8px;
    padding: 5px 8px;
    font-weight: bold;
    background: #f5f5f5;
  }
</style>
</head>
<body>

{{-- ═══ LOGO + TÍTULO ═══ --}}
<table class="header-table">
  <tr>
    <td class="logo-cell">
      @php 
        $logoPath = public_path('empresa/logo_largo.png'); 
        $logoBase64 = '';
        if(file_exists($logoPath)) {
          $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
      @endphp
      @if($logoBase64)
        <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
      @endif

    </td>
    <td class="titulo-cell">
      PLANILLA DE CALIFICACIONES
    </td>
  </tr>
</table>

{{-- ═══ DATOS ═══ --}}
<table class="meta-table">
  <tr>
    <td class="label">Grupo:</td>
    <td>{{ $grupo['nombre'] }}</td>
    <td class="label">Gestión:</td>
    <td>{{ $grupo['gestion'] }}</td>
    <td class="label">Materia:</td>
    <td>{{ $materia['nombre'] }}</td>
    <td class="label">Carrera:</td>
    <td>{{ $carrera }}</td>
  </tr>
</table>

{{-- ═══ TABLA DE NOTAS ═══ --}}
<table class="notas">
  <thead>
    <tr>
      <th style="width:22px">#</th>
      <th style="width:180px">Apellidos y Nombres</th>
      <th>Asist.</th>
      @foreach($elementos_competencia as $ec)
        <th>{{ $ec['nombre'] }}</th>
      @endforeach
      <th>N. Acad.</th>
      <th>N. Final</th>
      <th>Estado</th>
    </tr>
  </thead>
  <tbody>
    @foreach($estudiantes as $i => $est)
      @php
        $notasMap = collect($est['notas_ec'])->pluck('puntaje', 'id_elemento_competencia')->toArray();
      @endphp
      <tr>
        <td>{{ $i + 1 }}</td>
        <td class="nombre">{{ $est['nombre_completo'] }}</td>
        <td>{{ number_format($est['nota_asistencia'], 2) }}</td>
        @foreach($elementos_competencia as $ec)
          <td>{{ isset($notasMap[$ec['id']]) ? number_format($notasMap[$ec['id']], 2) : '—' }}</td>
        @endforeach
        <td>{{ number_format($est['nota_academica'], 2) }}</td>
        <td class="nota-final">{{ number_format($est['nota_final'], 2) }}</td>
        <td><strong>{{ $est['estado'] }}</strong></td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- ═══ RESUMEN ═══ --}}
<div class="resumen">
  Total estudiantes: {{ $total_estudiantes }}
  &nbsp;&nbsp;|&nbsp;&nbsp;
  Aprobados: {{ $aprobados }}
  &nbsp;&nbsp;|&nbsp;&nbsp;
  Reprobados: {{ $reprobados }}
</div>

</body>
</html>