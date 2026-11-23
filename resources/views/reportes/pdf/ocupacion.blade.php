<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ocupación S.R.C.</title>
    <style>
        @page { margin: 18mm 14mm; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #263345;
            font-size: 10px;
        }
        .header {
            margin-bottom: 14px;
            padding: 12px 14px;
            color: #fff;
            background: #7b1e24;
            border-radius: 8px;
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }
        .header p {
            margin: 2px 0;
            font-size: 9px;
        }
        .kpis {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 6px;
        }
        .kpis td {
            width: 25%;
            padding: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .kpis small {
            display: block;
            color: #647184;
        }
        .kpis strong {
            display: block;
            margin-top: 3px;
            color: #263345;
            font-size: 14px;
        }
        table.detail {
            width: 100%;
            border-collapse: collapse;
        }
        table.detail th {
            color: #fff;
            background: #a6262e;
            border: 1px solid #8f2027;
            padding: 7px 5px;
            text-align: left;
        }
        table.detail td {
            padding: 6px 5px;
            border: 1px solid #e2e8f0;
        }
        table.detail tr:nth-child(even) td {
            background: #f8fafc;
        }
        .empty {
            padding: 18px;
            color: #647184;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>S.R.C - Universidad de Pamplona</h1>
        <p>Reporte de ocupación · {{ $desde->toDateString() }} a {{ $hasta->toDateString() }}</p>
        <p>Estado: {{ $estado ?: 'Todos' }} · Aula: {{ $aula ? $aula->bloque . ' · ' . $aula->nombre : 'Todas las aulas' }}</p>
        <p>Generado: {{ now()->format('d/m/Y H:i') }} · Usuario: {{ $generadoPor ?: 'Sistema' }}</p>
    </div>

    <table class="kpis">
        <tr>
            <td>
                <small>% ocupación</small>
                <strong>{{ number_format($kpis['ocupacion'], 2, ',', '.') }}%</strong>
            </td>
            <td>
                <small>Reservas del mes</small>
                <strong>{{ $kpis['reservas_mes']['total'] }}</strong>
            </td>
            <td>
                <small>Disponibles / En uso</small>
                <strong>{{ $kpis['equipos_hoy']['disponibles'] }} / {{ $kpis['equipos_hoy']['en_uso'] }}</strong>
            </td>
            <td>
                <small>Tasa de cancelación</small>
                <strong>{{ number_format($kpis['cancelaciones']['tasa_cancelacion'], 2, ',', '.') }}%</strong>
            </td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Bloque horario</th>
                <th>Aula</th>
                <th>Slots reservados</th>
                <th>Slots disponibles</th>
                <th>Ocupación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detalle as $fila)
                @php
                    $porcentaje = $fila['slots_disponibles'] > 0
                        ? round(($fila['slots_reservados'] / $fila['slots_disponibles']) * 100, 2)
                        : 0;
                @endphp
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                    <td>{{ $fila['bloque'] }}</td>
                    <td>{{ $fila['aula'] }}</td>
                    <td>{{ $fila['slots_reservados'] }}</td>
                    <td>{{ $fila['slots_disponibles'] }}</td>
                    <td>{{ number_format($porcentaje, 2, ',', '.') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">Sin datos para el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
