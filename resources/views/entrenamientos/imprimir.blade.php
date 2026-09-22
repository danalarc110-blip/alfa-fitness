<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $rutina->nombre }} - Hoja de Entrenamiento | Alpha Fitness</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #111;
            background: #fff;
            padding: 24px;
            font-size: 13px;
            line-height: 1.4;
        }
        .no-print {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #18181b;
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .btn-print {
            background: #facc15;
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-close {
            color: #aaa;
            text-decoration: none;
            font-size: 13px;
            margin-right: 12px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .brand {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .brand span {
            color: #ca8a04;
        }
        .meta {
            text-align: right;
            font-size: 12px;
            color: #555;
        }
        .routine-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .badges {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .badge {
            display: inline-block;
            background: #f4f4f5;
            border: 1px solid #e4e4e7;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        .day-section {
            margin-bottom: 24px;
            page-break-inside: avoid;
        }
        .day-header {
            background: #f4f4f5;
            padding: 8px 12px;
            font-weight: 700;
            font-size: 14px;
            border-left: 4px solid #ca8a04;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th, td {
            border: 1px solid #e4e4e7;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background: #fafafa;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .checkbox-cell {
            width: 24px;
            text-align: center;
        }
        .check-box {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1.5px solid #71717a;
            border-radius: 3px;
        }
        .footer {
            margin-top: 32px;
            border-top: 1px solid #e4e4e7;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #71717a;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <strong>Modo Imprimible</strong> · Listo para imprimir o guardar como PDF desde el navegador.
        </div>
        <div>
            <a href="javascript:window.close()" class="btn-close">Cerrar</a>
            <button onclick="window.print()" class="btn-print">🖨️ Imprimir Rutina</button>
        </div>
    </div>

    <div class="header">
        <div>
            <div class="brand">Alpha <span>Fitness</span></div>
            <div style="font-size: 12px; color: #666; margin-top: 2px;">Plan Oficial de Entrenamiento</div>
        </div>
        <div class="meta">
            <div><strong>Atleta:</strong> {{ $nombre }}</div>
            <div><strong>Fecha:</strong> {{ now()->format('d/m/Y') }}</div>
            @if($rutina->asignado_por)
                <div><strong>Coach / Entrenador:</strong> {{ $rutina->asignado_por }}</div>
            @endif
        </div>
    </div>

    <div class="routine-title">{{ $rutina->nombre }}</div>
    <div class="badges">
        <span class="badge">Nivel: {{ $rutina->nivel }}</span>
        <span class="badge">Objetivo: {{ $rutina->objetivo ?: 'General' }}</span>
        <span class="badge">{{ $rutina->dias->count() }} {{ $rutina->dias->count() === 1 ? 'Día' : 'Días' }} por semana</span>
        <span class="badge">{{ $rutina->totalEjercicios() }} Ejercicios en total</span>
    </div>

    @foreach($rutina->dias as $dia)
        <div class="day-section">
            <div class="day-header">
                <span>{{ $dia->titulo }}</span>
                <span style="font-size: 11px; font-weight: normal; color: #666;">
                    Duración estimada: {{ $dia->duracion_estimada_min }}-{{ $dia->duracion_estimada_max }} min
                </span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Ejercicio</th>
                        <th>Grupo Muscular</th>
                        <th class="text-center" style="width: 80px;">Series x Reps</th>
                        <th class="text-center" style="width: 90px;">Peso Obj.</th>
                        <th class="text-center" style="width: 80px;">Descanso</th>
                        <th class="text-center" style="width: 120px;">Registro Series</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dia->ejercicios as $idx => $re)
                        <tr>
                            <td class="text-center" style="color: #666;">{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $re->ejercicio->nombre ?? 'Ejercicio' }}</strong>
                                @if($re->ejercicio?->subgrupo)
                                    <div style="font-size: 11px; color: #666;">{{ $re->ejercicio->subgrupo }}</div>
                                @endif
                            </td>
                            <td>{{ $re->ejercicio->grupo_muscular ?? 'General' }}</td>
                            <td class="text-center font-bold">
                                {{ $re->series }} &times; {{ $re->repeticiones }}
                            </td>
                            <td class="text-center">
                                {{ $re->peso ? $re->peso . ' kg' : '---' }}
                            </td>
                            <td class="text-center">
                                {{ $re->descanso_segundos ?: 60 }}s
                            </td>
                            <td class="text-center">
                                <div style="display: flex; gap: 4px; justify-content: center;">
                                    @for($s = 1; $s <= ($re->series ?: 3); $s++)
                                        <span class="check-box" title="Serie {{ $s }}"></span>
                                    @endfor
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="color: #888; padding: 16px;">
                                Sin ejercicios asignados en este día.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <span>Alpha Fitness Club · Disciplina, Fuerza y Superación</span>
        <span>Generado electrónicamente · alpha-fitness</span>
    </div>

</body>
</html>
