@php
    // Функция для правильного падежа "раз"
    function getRepsWord($number) {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;
        
        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) {
            return 'раз';
        }
        
        if ($lastDigit == 1) {
            return 'раз';
        } elseif ($lastDigit >= 2 && $lastDigit <= 4) {
            return 'раза';
        } else {
            return 'раз';
        }
    }
    
    // Функция для правильного падежа "подход"
    function getSetsWord($number) {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;
        
        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) {
            return 'подходов';
        }
        
        if ($lastDigit == 1) {
            return 'подход';
        } elseif ($lastDigit >= 2 && $lastDigit <= 4) {
            return 'подхода';
        } else {
            return 'подходов';
        }
    }
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экспорт цикла: {{ $cycle['name'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .no-break {
            page-break-inside: avoid;
        }
        .workout {
            page-break-inside: avoid;
        }
        .plan {
            page-break-inside: avoid;
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
        .plan {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fafafa;
            page-break-inside: avoid;
        }
        .plan-header {
            font-weight: bold;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .exercise {
            margin-left: 20px;
            margin-bottom: 8px;
            padding: 5px;
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
        .workout {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #fff;
            border-left: 3px solid #3498db;
        }
        .workout-header {
            font-weight: bold;
            color: #2c3e50;
        }
        .workout-details {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .workout-exercise {
            margin-top: 10px;
            margin-left: 15px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 3px;
            page-break-inside: avoid;
        }
        .workout-exercise-name {
            font-weight: bold;
            color: #34495e;
            margin-bottom: 8px;
        }
        .workout-sets-list {
            margin-top: 5px;
        }
        .workout-set-line {
            margin-bottom: 4px;
            font-size: 11px;
            color: #34495e;
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
    <h1>Цикл тренировок: {{ $cycle['name'] }}</h1>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Пользователь:</div>
            <div class="info-value">{{ $cycle['user']['name'] }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Дата начала:</div>
            <div class="info-value">
                @if($cycle['start_date'])
                    @php
                        $startDate = \Carbon\Carbon::parse($cycle['start_date']);
                        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
                    @endphp
                    {{ $startDate->format('d') }} {{ $months[$startDate->month - 1] }} {{ $startDate->format('Y') }} года
                @else
                    Не указана
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Дата окончания:</div>
            <div class="info-value">
                @if($cycle['end_date'])
                    @php
                        $endDate = \Carbon\Carbon::parse($cycle['end_date']);
                        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
                    @endphp
                    {{ $endDate->format('d') }} {{ $months[$endDate->month - 1] }} {{ $endDate->format('Y') }} года
                @else
                    Не указана
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Продолжительность:</div>
            <div class="info-value">{{ $cycle['weeks'] }} недель</div>
        </div>
    </div>

    <div class="statistics">
        <div class="stat-item">
            <div class="stat-label">Прогресс</div>
            <div class="stat-value">{{ $cycle['progress_percentage'] }}%</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Текущая неделя</div>
            <div class="stat-value">{{ $cycle['current_week'] }} / {{ $cycle['weeks'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Завершено тренировок</div>
            <div class="stat-value">{{ $cycle['completed_workouts'] }} / {{ $cycle['total_scheduled_workouts'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Планов в цикле</div>
            <div class="stat-value">{{ $cycle['plans_count'] }}</div>
        </div>
    </div>

    <h2>Планы тренировок</h2>
    @foreach($cycle['plans'] as $plan)
        <div class="plan">
            <div class="plan-header">
                {{ $plan['order'] }}. {{ $plan['name'] }}
                @if(!$plan['is_active'])
                    <span style="color: #e74c3c; font-size: 11px;">(Неактивен)</span>
                @endif
                <span style="color: #7f8c8d; font-size: 11px; font-weight: normal;">({{ $plan['exercise_count'] }} упражнений)</span>
            </div>
            @if(!empty($plan['exercises']))
                <h3 style="margin-top: 10px; margin-bottom: 8px; font-size: 12px;">Упражнения:</h3>
                @foreach($plan['exercises'] as $exercise)
                    <div class="exercise">
                        <span class="exercise-name">{{ $exercise['order'] }}. {{ $exercise['name'] }}</span>
                        @if($exercise['muscle_group'])
                            <span class="exercise-details">({{ $exercise['muscle_group']['name'] }})</span>
                        @endif
                        @if($exercise['description'])
                            <div class="exercise-details" style="margin-top: 3px;">{{ $exercise['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            @else
                <div style="color: #7f8c8d; font-style: italic;">Нет упражнений</div>
            @endif
        </div>
    @endforeach

    @if(!empty($cycle['workouts']))
        <h2>История тренировок</h2>
        @foreach($cycle['workouts'] as $workout)
            <div class="workout">
                <div class="workout-header">
                    {{ $workout['plan_name'] ?? 'План не указан' }}
                </div>
                <div class="workout-details">
                    @if($workout['started_at'])
                        @php
                            $startedAt = \Carbon\Carbon::parse($workout['started_at']);
                            $finishedAt = $workout['finished_at'] ? \Carbon\Carbon::parse($workout['finished_at']) : null;
                            $isSameDay = $finishedAt && $startedAt->isSameDay($finishedAt);
                        @endphp
                        @if($isSameDay)
                            {{ $startedAt->format('d.m.Y') }} | Начало: {{ $startedAt->format('H:i') }}
                            @if($finishedAt)
                                | Окончание: {{ $finishedAt->format('H:i') }}
                                @if($workout['duration_minutes'])
                                    | Длительность: {{ $workout['duration_minutes'] }} мин.
                                @endif
                            @endif
                        @else
                            Начало: {{ $startedAt->format('d.m.Y H:i') }}
                            @if($finishedAt)
                                | Окончание: {{ $finishedAt->format('d.m.Y H:i') }}
                                @if($workout['duration_minutes'])
                                    | Длительность: {{ $workout['duration_minutes'] }} мин.
                                @endif
                            @endif
                        @endif
                    @else
                        Не указано
                    @endif
                    @if(!$workout['finished_at'])
                        | <span style="color: #e74c3c;">Не завершена</span>
                    @endif
                </div>
                @if(!empty($workout['exercises']))
                    @foreach($workout['exercises'] as $exercise)
                        <div class="workout-exercise">
                            <div class="workout-exercise-name">{{ $exercise['name'] }}</div>
                            <div class="workout-sets-list">
                                @php
                                    // Группируем одинаковые подходы
                                    $groupedSets = [];
                                    
                                    if (!empty($exercise['sets'])) {
                                        $currentWeight = $exercise['sets'][0]['weight'] ?? '-';
                                        $currentReps = $exercise['sets'][0]['reps'] ?? '-';
                                        $currentCount = 1;
                                        
                                        for ($i = 1; $i < count($exercise['sets']); $i++) {
                                            $set = $exercise['sets'][$i];
                                            $setWeight = $set['weight'] ?? '-';
                                            $setReps = $set['reps'] ?? '-';
                                            
                                            if ($setWeight == $currentWeight && $setReps == $currentReps) {
                                                $currentCount++;
                                            } else {
                                                $groupedSets[] = [
                                                    'weight' => $currentWeight,
                                                    'reps' => $currentReps,
                                                    'count' => $currentCount,
                                                ];
                                                $currentWeight = $setWeight;
                                                $currentReps = $setReps;
                                                $currentCount = 1;
                                            }
                                        }
                                        
                                        // Добавляем последнюю группу
                                        $groupedSets[] = [
                                            'weight' => $currentWeight,
                                            'reps' => $currentReps,
                                            'count' => $currentCount,
                                        ];
                                    }
                                @endphp
                                
                                @foreach($groupedSets as $groupedSet)
                                    @php
                                        // Форматируем вес: если .00, то округляем до целых и добавляем "кг"
                                        $weight = $groupedSet['weight'];
                                        if ($weight !== '-' && is_numeric($weight)) {
                                            $weightFloat = (float) $weight;
                                            if ($weightFloat == round($weightFloat)) {
                                                $weight = (int) $weightFloat;
                                            } else {
                                                $weight = number_format($weightFloat, 2, '.', '');
                                            }
                                        }
                                        
                                        $reps = $groupedSet['reps'] ?? '-';
                                        $count = $groupedSet['count'];
                                        
                                        // Формируем строку
                                        if ($weight !== '-' && $reps !== '-') {
                                            $repsWord = getRepsWord((int) $reps);
                                            $setsWord = getSetsWord($count);
                                            
                                            if ($count == 1) {
                                                $line = "<strong>{$weight}</strong> кг на <strong>{$reps}</strong> {$repsWord}";
                                            } else {
                                                $line = "<strong>{$weight}</strong> кг на <strong>{$reps}</strong> {$repsWord}, {$count} {$setsWord}";
                                            }
                                        } else {
                                            $line = 'Не указано';
                                        }
                                    @endphp
                                    <div class="workout-set-line">{!! $line !!}</div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
    @endif

    <div class="footer">
        Экспортировано: {{ now()->format('d.m.Y H:i') }} | ID цикла: {{ $cycle['id'] }}
    </div>
</body>
</html>

