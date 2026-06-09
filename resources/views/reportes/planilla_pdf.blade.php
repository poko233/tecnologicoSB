<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { font-family: Arial, sans-serif; font-size: 9px; box-sizing: border-box; }
  body { margin: 0; padding: 10px; }
 
  .titulo {
    background: #1F3864; color: #fff; text-align: center;
    font-size: 14px; font-weight: bold; padding: 8px; margin-bottom: 6px;
  }
  .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .meta-table td { background: #D9E1F2; padding: 4px 6px; }
  .meta-table .label { font-weight: bold; color: #1F3864; width: 70px; }
 
  table.notas { width: 100%; border-collapse: collapse; }
  table.notas th {
    background: #1F3864; color: #fff;
    padding: 5px 3px; text-align: center; border: 1px solid #aaa;
    font-size: 8px;
  }
  table.notas th.ec-col { background: #2E75B6; }
  table.notas td { border: 1px solid #ccc; padding: 4px 3px; text-align: center; }
  table.notas td.nombre { text-align: left; }
 
  .aprobado  { background: #E2EFDA; }
  .reprobado { background: #FCE4D6; }
  .nota-final { background: #FFF2CC; }
 
  .resumen {
    background: #D9E1F2; margin-top: 8px;
    padding: 5px 8px; font-weight: bold;
  }
</style>
</head>
<body>
 
<div class="titulo">PLANILLA DE CALIFICACIONES</div>
 
<table class="meta-table">
  <tr>
    <td class="label">Grupo:</td>   <td>{{ $grupo['nombre'] }}</td>
    <td class="label">Gestión:</td> <td>{{ $grupo['gestion'] }}</td>
    <td class="label">Materia:</td> <td>{{ $materia['nombre'] }}</td>
    <td class="label">Carrera:</td> <td>{{ $carrera }}</td>
  </tr>
</table>
 
<table class="notas">
  <thead>
    <tr>
      <th style="width:22px">#</th>
      <th style="width:200px">Apellidos y Nombres</th>
      <th>Asist.</th>
      @foreach($elementos_competencia as $ec)
        <th class="ec-col">{{ $ec['nombre'] }}</th>
      @endforeach
      <th>N. Acad.</th>
      <th>N. Final</th>
      <th>Estado</th>
    </tr>
  </thead>
  <tbody>
    @foreach($estudiantes as $i => $est)
      @php
        $clase = ($est['estado'] ?? '') === 'Aprobado' ? 'aprobado' : 'reprobado';
        $notasMap = collect($est['notas_ec'])->pluck('puntaje', 'id_elemento_competencia')->toArray();
      @endphp
      <tr class="{{ $clase }}">
        <td>{{ $i + 1 }}</td>
        <td class="nombre">{{ $est['nombre_completo'] }}</td>
        <td>{{ number_format($est['nota_asistencia'], 2) }}</td>
        @foreach($elementos_competencia as $ec)
          <td>{{ isset($notasMap[$ec['id']]) ? number_format($notasMap[$ec['id']], 2) : '—' }}</td>
        @endforeach
        <td>{{ number_format($est['nota_academica'], 2) }}</td>
        <td class="nota-final">{{ number_format($est['nota_final'], 2) }}</td>
        <td>{{ $est['estado'] }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
 
<div class="resumen">
  Total estudiantes: {{ $total_estudiantes }}
  &nbsp;&nbsp;|&nbsp;&nbsp;
  Aprobados: {{ $aprobados }}
  &nbsp;&nbsp;|&nbsp;&nbsp;
  Reprobados: {{ $reprobados }}
</div>
 
</body>
</html>
*/