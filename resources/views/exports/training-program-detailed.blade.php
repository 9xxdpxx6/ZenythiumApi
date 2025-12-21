<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экспорт программы: {{ $program['name'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h2 {
            color: #34495e;
            margin-top: 25px;
            margin-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 5px;
        }
        h3 {
            color: #555;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 8px 15px 8px 0;
            width: 200px;
            color: #555;
        }
        .info-value {
            display: table-cell;
            padding: 8px 0;
        }
        .statistics {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .stat-item {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 10px;
        }
        .stat-label {
            font-weight: bold;
            color: #7f8c8d;
            font-size: 11px;
        }
        .stat-value {
            font-size: 18px;
            color: #2c3e50;
            font-weight: bold;
        }
        .cycle {
            margin-bottom: 30px;
            padding: 15px;
            border: 2px solid #3498db;
            border-radius: 5px;
            background-color: #f0f8ff;
        }
        .cycle-header {
            font-weight: bold;
            font-size: 15px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .plan {
            margin-bottom: 20px;
            margin-left: 20px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
        }
        .plan-header {
            font-weight: bold;
            font-size: 13px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .exercise {
            margin-left: 20px;
            margin-bottom: 6px;
            padding: 4px;
        }
        .exercise-name {
            font-weight: bold;
            color: #34495e;
        }
        .exercise-details {
            font-size: 11px;
            color: #7f8c8d;
            margin-left: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Программа тренировок: {{ $program['name'] }}</h1>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Автор:</div>
            <div class="info-value">{{ $program['author']['name'] ?? 'Не указан' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Продолжительность:</div>
            <div class="info-value">{{ $program['duration_weeks'] }} недель</div>
        </div>
        <div class="info-row">
            <div class="info-label">Статус:</div>
            <div class="info-value">
                @if($program['is_active'])
                    <span style="color: #27ae60;">Активна</span>
                @else
                    <span style="color: #e74c3c;">Неактивна</span>
                @endif
            </div>
        </div>
        @if($program['description'])
            <div class="info-row">
                <div class="info-label">Описание:</div>
                <div class="info-value">{{ $program['description'] }}</div>
            </div>
        @endif
    </div>

    <div class="statistics">
        <div class="stat-item">
            <div class="stat-label">Установок</div>
            <div class="stat-value">{{ $program['installations_count'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Циклов</div>
            <div class="stat-value">{{ $program['cycles_count'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Планов</div>
            <div class="stat-value">{{ $program['plans_count'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Упражнений</div>
            <div class="stat-value">{{ $program['exercises_count'] }}</div>
        </div>
    </div>

    @if($program['structure'] && !empty($program['structure']['cycles']))
        <h2>Структура программы</h2>
        @foreach($program['structure']['cycles'] as $cycleIndex => $cycle)
            <div class="cycle">
                <div class="cycle-header">
                    Цикл {{ $cycleIndex + 1 }}: {{ $cycle['name'] }}
                </div>
                @if(!empty($cycle['plans']))
                    @foreach($cycle['plans'] as $plan)
                        <div class="plan">
                            <div class="plan-header">{{ $plan['name'] }}</div>
                            @if(!empty($plan['exercises']))
                                @foreach($plan['exercises'] as $exercise)
                                    <div class="exercise">
                                        <span class="exercise-name">{{ $exercise['name'] }}</span>
                                        @if($exercise['description'])
                                            <div class="exercise-details" style="margin-top: 3px;">{{ $exercise['description'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div style="color: #7f8c8d; font-style: italic; margin-left: 20px;">Нет упражнений</div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div style="color: #7f8c8d; font-style: italic; margin-left: 20px;">Нет планов</div>
                @endif
            </div>
        @endforeach
    @else
        <div style="color: #7f8c8d; font-style: italic; padding: 20px;">Структура программы не определена</div>
    @endif

    <div class="footer">
        Экспортировано: {{ now()->format('d.m.Y H:i') }} | ID программы: {{ $program['id'] }}
    </div>
</body>
</html>

