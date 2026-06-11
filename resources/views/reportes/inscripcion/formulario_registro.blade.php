<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Registro</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            box-sizing: border-box;
        }

        body {
            font-size: 11px;
            color: #111827;
            margin: 24px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }

        .header p {
            margin: 4px 0 0;
            font-size: 11px;
        }

        .section {
            margin-top: 14px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            padding: 10px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: #0F172A;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            border: 1px solid #CBD5E1;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #E5E7EB;
            font-weight: bold;
            text-align: left;
        }

        .no-border td {
            border: none;
            padding: 4px 2px;
        }

        .label {
            font-weight: bold;
            color: #374151;
        }

        .small {
            font-size: 10px;
            color: #4B5563;
            margin-top: 14px;
        }

        .footer {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .firma {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding-top: 50px;
        }

        .linea {
            border-top: 1px solid #111827;
            width: 70%;
            margin: 0 auto 6px;
        }

        .page-break {
            page-break-before: always;
        }

        .no-break {
            page-break-inside: avoid;
        }

        .row-no-break {
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $usuario = $resumen['usuario'];
        $carrera = $resumen['carrera'];
        $referencias = collect($resumen['referencias'] ?? []);
        $referencia = $referencias->first();
        $grupos = collect($resumen['grupos'] ?? []);
        $planPago = $resumen['planPago'] ?? [];
        $cuotasMensuales = collect($planPago['cuotasMensuales'] ?? [])->sortBy(function ($cuota) {
            return (int) ($cuota->numeroCuota ?? 0);
        });

        $nombreCompleto = trim(
            ($usuario['nombres'] ?? '') . ' ' .
            ($usuario['apellidoPaterno'] ?? '') . ' ' .
            ($usuario['apellidoMaterno'] ?? '')
        );

        function bs_formato($valor) {
            return 'Bs ' . number_format((float) ($valor ?? 0), 2, '.', ',');
        }

        function fecha_formato($fecha) {
            if (!$fecha) return '-';
            return substr((string) $fecha, 0, 10);
        }
    @endphp

    <div class="header no-break">
        <h1>Formulario de Registro de Estudiante</h1>
        <p>Fecha de generación: {{ $fechaGeneracion }}</p>
    </div>

    <div class="section no-break">
        <div class="section-title">Datos de Matrícula</div>

        <table class="no-border">
            <tr>
                <td><span class="label">Matrícula:</span> {{ $usuario['matricula'] ?? '-' }}</td>
                <td>
    <span class="label">Fecha de inscripción:</span>
    {{ fecha_formato($usuario['created_at'] ?? null) }}
</td>
            </tr>
            <tr>
                <td><span class="label">Carrera:</span> {{ $carrera->nombreCarrera ?? '-' }}</td>
                <td><span class="label">Régimen:</span> {{ $carrera->regimen ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section no-break">
        <div class="section-title">Datos Personales</div>

        <table>
            <tr>
                <th>Nombre completo</th>
                <td>{{ $nombreCompleto }}</td>
            </tr>
            <tr>
                <th>Cédula de identidad</th>
                <td>{{ $usuario['ci'] ?? '-' }} {{ $usuario['expedido'] ?? '' }}</td>
            </tr>
            <tr>
                <th>Género</th>
                <td>{{ $usuario['genero'] ?? '-' }}</td>
            </tr>
            <tr>
                <th>Fecha de nacimiento</th>
                <td>{{ fecha_formato($usuario['fecha_nac'] ?? null) }}</td>
            </tr>
            <tr>
                <th>Correo electrónico</th>
                <td>{{ $usuario['email'] ?? '-' }}</td>
            </tr>
            <tr>
                <th>Celular</th>
                <td>{{ $usuario['celular'] ?? '-' }}</td>
            </tr>
            <tr>
                <th>Dirección</th>
                <td>{{ $usuario['direccion'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section no-break">
        <div class="section-title">Referencia de Contacto</div>

        <table>
            <tr>
                <th>Nombre de referencia</th>
                <td>{{ $referencia->nombreContactoReferencia ?? '-' }}</td>
            </tr>
            <tr>
                <th>Parentesco</th>
                <td>{{ $referencia->parentesco ?? '-' }}</td>
            </tr>
            <tr>
                <th>Número de contacto</th>
                <td>{{ $referencia->numeroReferencia ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section no-break">
        <div class="section-title">Materias y Grupos Inscritos</div>

        <table>
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Código</th>
                    <th>Semestre</th>
                    <th>Grupo</th>
                    <th>Turno</th>
                    <th>Gestión</th>
                    <th>Horario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grupos as $grupo)
                    <tr class="row-no-break">
                        <td>{{ $grupo->nombreMateria ?? '-' }}</td>
                        <td>{{ $grupo->codigoMateria ?? '-' }}</td>
                        <td>{{ $grupo->semestre ?? '-' }}</td>
                        <td>
                            {{ $grupo->nombre ?? '-' }}
                            @if($grupo->paralelo)
                                - {{ $grupo->paralelo }}
                            @endif
                        </td>
                        <td>{{ $grupo->turno ?? '-' }}</td>
                        <td>{{ $grupo->gestion ?? '-' }}</td>
                        <td>
                            @forelse($grupo->horarios ?? [] as $horario)
                                {{ $horario['dia'] }}:
                                {{ substr($horario['horaInicio'], 0, 5) }} -
                                {{ substr($horario['horaFin'], 0, 5) }}<br>
                            @empty
                                -
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No hay grupos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="header no-break">
        <h1>Plan de Pago</h1>
        <p>
            Estudiante: {{ $nombreCompleto }} |
            Matrícula: {{ $usuario['matricula'] ?? '-' }}
        </p>
    </div>

    <div class="section no-break">
        <div class="section-title">Resumen del Plan de Pago</div>

        <table>
            <tr>
                <th>Matrícula</th>
                <td>{{ bs_formato($planPago['totalMatricula'] ?? 0) }}</td>
                <th>Total cuotas</th>
                <td>{{ bs_formato($planPago['totalCuotas'] ?? 0) }}</td>
            </tr>
            <tr>
                <th>Condonado</th>
                <td>{{ bs_formato($planPago['totalCondonado'] ?? 0) }}</td>
                <th>Total plan</th>
                <td>{{ bs_formato($planPago['totalPlan'] ?? 0) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Cuotas</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 9%;">N°</th>
                    <th style="width: 22%;">Monto</th>
                    <th style="width: 28%;">Vencimiento</th>
                    <th style="width: 19%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cuotasMensuales as $cuota)
                    <tr class="row-no-break">
                        <td>{{ $cuota->numeroCuota ?? '-' }}</td>
                        <td>{{ bs_formato($cuota->monto ?? 0) }}</td>
                        <td>{{ fecha_formato($cuota->fecha_vencimiento ?? null) }}</td>
                        <td>{{ $cuota->estadoCuota ?? 'Debe' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay cuotas mensuales registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="small">
        Este formulario no incluye documentos adjuntos ni archivos del estudiante.
    </p>

    <div class="footer no-break">
        <div class="firma">
            <div class="linea"></div>
            Firma del Estudiante
        </div>

        <div class="firma">
            <div class="linea"></div>
            Responsable de Inscripción
        </div>
    </div>
</body>
</html>