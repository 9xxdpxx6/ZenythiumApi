<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - Test Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        pre {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">
                            <i class="fas fa-chart-bar"></i>
                            Статистика - API
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Navigation -->
                        <div class="mb-3">
                            <a href="/test-forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                        </div>

                        <!-- General Statistics -->
                        <div class="card mb-4 stat-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-chart-line"></i> Основная статистика</h5>
                                <button id="btnGeneralStats" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Загрузить
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="generalStatsResult"></div>
                            </div>
                        </div>

                        <!-- Exercise Statistics -->
                        <div class="card mb-4 stat-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-dumbbell"></i> Статистика по упражнениям</h5>
                                <button id="btnExerciseStats" class="btn btn-success btn-sm">
                                    <i class="fas fa-sync-alt"></i> Загрузить
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="exerciseStatsResult"></div>
                            </div>
                        </div>

                        <!-- Time Analytics -->
                        <div class="card mb-4 stat-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-clock"></i> Временная аналитика</h5>
                                <button id="btnTimeAnalytics" class="btn btn-info btn-sm">
                                    <i class="fas fa-sync-alt"></i> Загрузить
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="timeAnalyticsResult"></div>
                            </div>
                        </div>

                        <!-- Muscle Group Statistics -->
                        <div class="card mb-4 stat-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-user-md"></i> Статистика по мышечным группам</h5>
                                <button id="btnMuscleGroupStats" class="btn btn-warning btn-sm">
                                    <i class="fas fa-sync-alt"></i> Загрузить
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="muscleGroupStatsResult"></div>
                            </div>
                        </div>

                        <!-- Records -->
                        <div class="card mb-4 stat-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-trophy"></i> Рекорды</h5>
                                <button id="btnRecords" class="btn btn-danger btn-sm">
                                    <i class="fas fa-sync-alt"></i> Загрузить
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="recordsResult"></div>
                            </div>
                        </div>

                        <!-- Response -->
                        <div id="response" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = `${window.location.protocol}//${window.location.host}/api/v1`;

        function getAuthHeaders() {
            const token = localStorage.getItem('auth_token');
            return {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
        }
        
        function getFetchOptions(method = 'GET', body = null) {
            const options = {
                method: method,
                headers: getAuthHeaders(),
                credentials: 'include'
            };
            
            if (body) {
                options.body = typeof body === 'string' ? body : JSON.stringify(body);
            }
            
            return options;
        }

        function showResponse(data, isError = false) {
            const responseDiv = document.getElementById('response');
            responseDiv.innerHTML = `
                <div class="alert ${isError ? 'alert-danger' : 'alert-success'}">
                    <h6>Ответ сервера:</h6>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                </div>
            `;
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('ru-RU');
        }

        function formatDateTime(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleString('ru-RU');
        }

        // General Statistics
        document.getElementById('btnGeneralStats').addEventListener('click', async function() {
            const resultDiv = document.getElementById('generalStatsResult');
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';

            try {
                const response = await fetch(`${API_BASE}/user/statistics`, getFetchOptions('GET'));

                const data = await response.json();
                showResponse(data, !response.ok);

                if (response.ok && data.data) {
                    const stats = data.data;
                    resultDiv.innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${stats.total_workouts || 0}</div>
                                        <div class="stat-label">Всего тренировок</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${stats.completed_workouts || 0}</div>
                                        <div class="stat-label">Завершенных тренировок</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${Math.round((stats.total_training_time || 0) / 60)}</div>
                                        <div class="stat-label">Часов тренировок</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${(parseFloat(stats.total_volume || 0) / 1000).toFixed(1)}k</div>
                                        <div class="stat-label">Общий объем (кг)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card bg-secondary text-white">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${stats.current_weight || '-'} кг</div>
                                        <div class="stat-label">Текущий вес</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card bg-dark text-white">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${stats.active_cycles_count || 0}</div>
                                        <div class="stat-label">Активных циклов</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card" style="background-color: #6f42c1; color: white;">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${stats.weight_change_30_days !== null ? (stats.weight_change_30_days > 0 ? '+' : '') + stats.weight_change_30_days.toFixed(1) : '-'} кг</div>
                                        <div class="stat-label">Изменение веса за 30 дней</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card" style="background-color: #fd7e14; color: white;">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${stats.training_frequency_4_weeks || 0}</div>
                                        <div class="stat-label">Тренировок в неделю (4 недели)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card" style="background-color: #20c997; color: white;">
                                    <div class="card-body text-center">
                                        <div class="stat-value">${stats.training_streak_days || 0}</div>
                                        <div class="stat-label">Серия тренировок (дней)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${data.message || 'Не удалось загрузить данные'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
                showResponse({error: error.message}, true);
            }
        });

        // Exercise Statistics
        document.getElementById('btnExerciseStats').addEventListener('click', async function() {
            const resultDiv = document.getElementById('exerciseStatsResult');
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';

            try {
                const response = await fetch(`${API_BASE}/user/exercise-statistics`, getFetchOptions('GET'));

                const data = await response.json();
                showResponse(data, !response.ok);

                if (response.ok && data.data) {
                    const stats = data.data;
                    let html = '';

                    // Top Exercises
                    if (stats.top_exercises && stats.top_exercises.length > 0) {
                        html += `
                            <h6><i class="fas fa-star"></i> Топ упражнений:</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Упражнение</th>
                                            <th>Группа мышц</th>
                                            <th>Подходов</th>
                                            <th>Объем</th>
                                            <th>Макс. вес</th>
                                            <th>Ср. вес</th>
                                            <th>Последний раз</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${stats.top_exercises.map(ex => `
                                            <tr>
                                                <td><strong>${ex.exercise_name}</strong></td>
                                                <td>${ex.muscle_group}</td>
                                                <td>${ex.total_sets}</td>
                                                <td>${ex.total_volume.toFixed(0)}</td>
                                                <td>${ex.max_weight.toFixed(1)} кг</td>
                                                <td>${ex.avg_weight.toFixed(1)} кг</td>
                                                <td>${formatDate(ex.last_performed)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // Exercise Progress
                    if (stats.exercise_progress && stats.exercise_progress.length > 0) {
                        html += `
                            <h6><i class="fas fa-chart-line"></i> Прогресс по упражнениям:</h6>
                            ${stats.exercise_progress.map(progress => `
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <strong>${progress.exercise_name}</strong> (${progress.muscle_group})
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Дата</th>
                                                        <th>Макс. вес</th>
                                                        <th>Объем</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${progress.weight_progression.slice(-10).map(prog => `
                                                        <tr>
                                                            <td>${formatDate(prog.date)}</td>
                                                            <td>${prog.max_weight.toFixed(1)} кг</td>
                                                            <td>${prog.total_volume.toFixed(0)}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        `;
                    }

                    if (!html) {
                        html = '<div class="alert alert-info">Нет данных по упражнениям</div>';
                    }

                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${data.message || 'Не удалось загрузить данные'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
                showResponse({error: error.message}, true);
            }
        });

        // Time Analytics
        document.getElementById('btnTimeAnalytics').addEventListener('click', async function() {
            const resultDiv = document.getElementById('timeAnalyticsResult');
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';

            try {
                const response = await fetch(`${API_BASE}/user/time-analytics`, getFetchOptions('GET'));

                const data = await response.json();
                showResponse(data, !response.ok);

                if (response.ok && data.data) {
                    const analytics = data.data;
                    let html = '';

                    // Weekly Pattern
                    if (analytics.weekly_pattern && analytics.weekly_pattern.length > 0) {
                        const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        const daysRu = {
                            'Monday': 'Понедельник',
                            'Tuesday': 'Вторник',
                            'Wednesday': 'Среда',
                            'Thursday': 'Четверг',
                            'Friday': 'Пятница',
                            'Saturday': 'Суббота',
                            'Sunday': 'Воскресенье'
                        };

                        html += `
                            <h6><i class="fas fa-calendar-week"></i> Паттерн тренировок по дням недели:</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>День недели</th>
                                            <th>Количество тренировок</th>
                                            <th>Средняя длительность</th>
                                            <th>Общий объем</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${daysOrder.map(day => {
                                            const dayData = analytics.weekly_pattern.find(d => d.day_of_week === day) || {
                                                day_of_week: day,
                                                workout_count: 0,
                                                avg_duration: 0,
                                                total_volume: 0
                                            };
                                            return `
                                                <tr>
                                                    <td>${daysRu[day]}</td>
                                                    <td>${dayData.workout_count}</td>
                                                    <td>${dayData.avg_duration.toFixed(1)} мин</td>
                                                    <td>${dayData.total_volume.toFixed(0)}</td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // Monthly Trends
                    if (analytics.monthly_trends && analytics.monthly_trends.length > 0) {
                        html += `
                            <h6><i class="fas fa-calendar-alt"></i> Тренды по месяцам:</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Месяц</th>
                                            <th>Тренировок</th>
                                            <th>Общий объем</th>
                                            <th>Средняя длительность</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${analytics.monthly_trends.map(trend => `
                                            <tr>
                                                <td>${trend.month}</td>
                                                <td>${trend.workout_count}</td>
                                                <td>${trend.total_volume.toFixed(0)}</td>
                                                <td>${trend.avg_duration.toFixed(1)} мин</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // Volume Trends
                    if (analytics.volume_trends && analytics.volume_trends.length > 0) {
                        html += `
                            <h6><i class="fas fa-chart-area"></i> Тренды объема по неделям:</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Неделя</th>
                                            <th>Общий объем</th>
                                            <th>Тренировок</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${analytics.volume_trends.map(trend => `
                                            <tr>
                                                <td>${trend.week}</td>
                                                <td>${trend.total_volume.toFixed(0)}</td>
                                                <td>${trend.workout_count}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    if (!html) {
                        html = '<div class="alert alert-info">Нет данных по временной аналитике</div>';
                    }

                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${data.message || 'Не удалось загрузить данные'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
                showResponse({error: error.message}, true);
            }
        });

        // Muscle Group Statistics
        document.getElementById('btnMuscleGroupStats').addEventListener('click', async function() {
            const resultDiv = document.getElementById('muscleGroupStatsResult');
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';

            try {
                const response = await fetch(`${API_BASE}/user/muscle-group-statistics`, getFetchOptions('GET'));

                const data = await response.json();
                showResponse(data, !response.ok);

                if (response.ok && data.data) {
                    const stats = data.data;
                    let html = '';

                    // Muscle Group Stats
                    if (stats.muscle_group_stats && stats.muscle_group_stats.length > 0) {
                        html += `
                            <h6><i class="fas fa-dumbbell"></i> Статистика по мышечным группам:</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Группа мышц</th>
                                            <th>Общий объем</th>
                                            <th>Тренировок</th>
                                            <th>Упражнений</th>
                                            <th>Ср. объем/тренировку</th>
                                            <th>Последняя тренировка</th>
                                            <th>Дней с последней</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${stats.muscle_group_stats.map(mg => `
                                            <tr>
                                                <td><strong>${mg.muscle_group_name}</strong></td>
                                                <td>${mg.total_volume.toFixed(0)}</td>
                                                <td>${mg.workout_count}</td>
                                                <td>${mg.exercise_count}</td>
                                                <td>${mg.avg_volume_per_workout.toFixed(0)}</td>
                                                <td>${formatDate(mg.last_trained)}</td>
                                                <td>${mg.days_since_last_training}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // Balance Analysis
                    if (stats.balance_analysis) {
                        const balance = stats.balance_analysis;
                        html += `
                            <h6><i class="fas fa-balance-scale"></i> Анализ баланса:</h6>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <p><strong>Наиболее тренируемая:</strong> ${balance.most_trained || '-'}</p>
                                    <p><strong>Наименее тренируемая:</strong> ${balance.least_trained || '-'}</p>
                                    <p><strong>Коэффициент баланса:</strong> ${balance.balance_score || 0}</p>
                                    ${balance.recommendations && balance.recommendations.length > 0 ? `
                                        <div class="mt-3">
                                            <strong>Рекомендации:</strong>
                                            <ul>
                                                ${balance.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                                            </ul>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    }

                    if (!html) {
                        html = '<div class="alert alert-info">Нет данных по мышечным группам</div>';
                    }

                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${data.message || 'Не удалось загрузить данные'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
                showResponse({error: error.message}, true);
            }
        });

        // Records
        document.getElementById('btnRecords').addEventListener('click', async function() {
            const resultDiv = document.getElementById('recordsResult');
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';

            try {
                const response = await fetch(`${API_BASE}/user/records`, getFetchOptions('GET'));

                const data = await response.json();
                showResponse(data, !response.ok);

                if (response.ok && data.data) {
                    const records = data.data;
                    let html = '';

                    // Personal Records
                    if (records.personal_records && records.personal_records.length > 0) {
                        html += `
                            <h6><i class="fas fa-medal"></i> Личные рекорды по упражнениям:</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Упражнение</th>
                                            <th>Группа мышц</th>
                                            <th>Макс. вес</th>
                                            <th>Макс. повторений</th>
                                            <th>Макс. объем</th>
                                            <th>Дата достижения</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${records.personal_records.map(record => `
                                            <tr>
                                                <td><strong>${record.exercise_name}</strong></td>
                                                <td>${record.muscle_group}</td>
                                                <td>${record.max_weight.toFixed(1)} кг</td>
                                                <td>${record.max_reps}</td>
                                                <td>${record.max_volume.toFixed(0)}</td>
                                                <td>${formatDate(record.achieved_date)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // Workout Records
                    if (records.workout_records) {
                        const workoutRecords = records.workout_records;
                        html += `
                            <h6><i class="fas fa-trophy"></i> Рекорды тренировок:</h6>
                            <div class="row g-3 mb-4">
                                ${workoutRecords.max_volume_workout ? `
                                    <div class="col-md-4">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h6>Максимальный объем</h6>
                                                <div class="stat-value">${workoutRecords.max_volume_workout.total_volume.toFixed(0)}</div>
                                                <div class="stat-label">Дата: ${formatDate(workoutRecords.max_volume_workout.date)}</div>
                                                <div class="stat-label">Длительность: ${workoutRecords.max_volume_workout.duration_minutes} мин</div>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                                ${workoutRecords.longest_workout ? `
                                    <div class="col-md-4">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h6>Самая длинная</h6>
                                                <div class="stat-value">${workoutRecords.longest_workout.duration_minutes} мин</div>
                                                <div class="stat-label">Дата: ${formatDate(workoutRecords.longest_workout.date)}</div>
                                                <div class="stat-label">Объем: ${workoutRecords.longest_workout.total_volume.toFixed(0)}</div>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                                ${workoutRecords.most_exercises_workout ? `
                                    <div class="col-md-4">
                                        <div class="card bg-info text-white">
                                            <div class="card-body">
                                                <h6>Больше всего упражнений</h6>
                                                <div class="stat-value">${workoutRecords.most_exercises_workout.exercise_count}</div>
                                                <div class="stat-label">Дата: ${formatDate(workoutRecords.most_exercises_workout.date)}</div>
                                                <div class="stat-label">Объем: ${workoutRecords.most_exercises_workout.total_volume.toFixed(0)}</div>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }

                    if (!html) {
                        html = '<div class="alert alert-info">Нет данных по рекордам</div>';
                    }

                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${data.message || 'Не удалось загрузить данные'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${error.message}</div>`;
                showResponse({error: error.message}, true);
            }
        });
    </script>
</body>
</html>

