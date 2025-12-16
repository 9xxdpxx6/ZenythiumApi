<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Forms - API CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-5">
                    <i class="fas fa-dumbbell text-primary"></i>
                    Test Forms - API CRUD
                </h1>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Инструкция:</strong> Сначала выполните авторизацию, затем используйте формы для тестирования CRUD операций.
                </div>

                <div class="row g-4">
                    <!-- Auth -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Авторизация</h5>
                                <p class="card-text">Регистрация, логин, получение токена</p>
                                <a href="/test-forms/auth" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Muscle Groups -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-dumbbell fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Группы мышц</h5>
                                <p class="card-text">CRUD операции для групп мышц</p>
                                <a href="/test-forms/muscle-groups" class="btn btn-success">
                                    <i class="fas fa-cogs"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Exercises -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-running fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Упражнения</h5>
                                <p class="card-text">CRUD операции для упражнений</p>
                                <a href="/test-forms/exercises" class="btn btn-warning">
                                    <i class="fas fa-dumbbell"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Cycles -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-sync-alt fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Циклы</h5>
                                <p class="card-text">CRUD операции для циклов тренировок</p>
                                <a href="/test-forms/cycles" class="btn btn-info">
                                    <i class="fas fa-calendar-alt"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Plans -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clipboard-list fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Планы</h5>
                                <p class="card-text">CRUD операции для планов тренировок</p>
                                <a href="/test-forms/plans" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Workouts -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-fire fa-3x text-danger mb-3"></i>
                                <h5 class="card-title">Тренировки</h5>
                                <p class="card-text">CRUD операции для тренировок</p>
                                <a href="/test-forms/workouts" class="btn btn-danger">
                                    <i class="fas fa-play"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Workout Sets -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-layer-group fa-3x text-dark mb-3"></i>
                                <h5 class="card-title">Подходы</h5>
                                <p class="card-text">CRUD операции для подходов</p>
                                <a href="/test-forms/workout-sets" class="btn btn-dark">
                                    <i class="fas fa-layer-group"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Metrics -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Метрики</h5>
                                <p class="card-text">CRUD операции для метрик веса</p>
                                <a href="/test-forms/metrics" class="btn btn-primary">
                                    <i class="fas fa-weight"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Training Programs -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-book fa-3x mb-3" style="color: #6f42c1;"></i>
                                <h5 class="card-title">Программы тренировок</h5>
                                <p class="card-text">Каталог программ, установка и удаление</p>
                                <a href="/test-forms/training-programs" class="btn" style="background-color: #6f42c1; border-color: #6f42c1; color: white;">
                                    <i class="fas fa-book"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-3x mb-3" style="color: #20c997;"></i>
                                <h5 class="card-title">Статистика</h5>
                                <p class="card-text">Статистика тренировок и прогресса</p>
                                <a href="/test-forms/statistics" class="btn" style="background-color: #20c997; border-color: #20c997; color: white;">
                                    <i class="fas fa-chart-bar"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Goals -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-bullseye fa-3x mb-3" style="color: #fd7e14;"></i>
                                <h5 class="card-title">Цели</h5>
                                <p class="card-text">CRUD операции для целей и достижений</p>
                                <a href="/test-forms/goals" class="btn" style="background-color: #fd7e14; border-color: #fd7e14; color: white;">
                                    <i class="fas fa-bullseye"></i> Открыть
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <div class="alert alert-light">
                        <i class="fas fa-code"></i>
                        <strong>API Base URL:</strong> <code id="apiBaseUrl"></code>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('apiBaseUrl')">
                            <i class="fas fa-copy"></i> Копировать
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Устанавливаем API Base URL при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const apiBaseUrl = `${window.location.protocol}//${window.location.host}/api/v1`;
            document.getElementById('apiBaseUrl').textContent = apiBaseUrl;
        });

        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            navigator.clipboard.writeText(element.textContent).then(() => {
                const button = element.nextElementSibling;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Скопировано';
                button.classList.remove('btn-outline-secondary');
                button.classList.add('btn-success');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-secondary');
                }, 2000);
            });
        }
    </script>
</body>
</html>
