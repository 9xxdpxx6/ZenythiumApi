<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Структура цикла: {{ $cycle['name'] }}</title>
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
        .info {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 150px;
        }
        .plan {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fafafa;
        }
        .plan-header {
            font-weight: bold;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
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
    <h1>Структура цикла: {{ $cycle['name'] }}</h1>

    <div class="info">
        <div class="info-item">
            <span class="info-label">Продолжительность:</span>
            <span>{{ $cycle['weeks'] }} недель</span>
        </div>
    </div>

    <h2 style="color: #34495e; margin-top: 25px; margin-bottom: 15px; border-bottom: 2px solid #ecf0f1; padding-bottom: 5px;">Планы тренировок</h2>
    
    @foreach($cycle['plans'] as $plan)
        <div class="plan">
            <div class="plan-header">
                {{ $plan['order'] }}. {{ $plan['name'] }}
            </div>
            @if(!empty($plan['exercises']))
                @foreach($plan['exercises'] as $exercise)
                    <div class="exercise">
                        <span class="exercise-name">{{ $exercise['order'] }}. {{ $exercise['name'] }}</span>
                        @if($exercise['muscle_group'])
                            <span class="exercise-details">({{ $exercise['muscle_group'] }})</span>
                        @endif
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

    <div class="footer">
        Экспортировано: {{ now()->format('d.m.Y H:i') }}
    </div>
</body>
</html>

