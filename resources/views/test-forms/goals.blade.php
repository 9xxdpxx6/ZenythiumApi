<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Цели - Test Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">
                            <i class="fas fa-bullseye"></i>
                            Цели - CRUD
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Navigation -->
                        <div class="mb-3">
                            <a href="/test-forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                        </div>

                        <!-- Get Types -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-list"></i> Получить типы целей</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-info" onclick="loadTypes()">
                                    <i class="fas fa-download"></i> Загрузить типы
                                </button>
                                <div id="typesResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Create Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Создать цель</h5>
                            </div>
                            <div class="card-body">
                                <form id="createForm">
                                    <div class="mb-3">
                                        <label for="createType" class="form-label">Тип цели *</label>
                                        <select class="form-control" id="createType" required>
                                            <option value="">Выберите тип цели</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createTitle" class="form-label">Название *</label>
                                        <input type="text" class="form-control" id="createTitle" required maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                        <label for="createDescription" class="form-label">Описание</label>
                                        <textarea class="form-control" id="createDescription" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createTargetValue" class="form-label">Целевое значение *</label>
                                        <input type="number" class="form-control" id="createTargetValue" step="0.01" min="0.01" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createStartDate" class="form-label">Дата начала *</label>
                                        <input type="date" class="form-control" id="createStartDate" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createEndDate" class="form-label">Дата окончания</label>
                                        <input type="date" class="form-control" id="createEndDate">
                                    </div>
                                    <div class="mb-3" id="createExerciseIdContainer" style="display: none;">
                                        <label for="createExerciseId" class="form-label">Упражнение *</label>
                                        <select class="form-control" id="createExerciseId">
                                            <option value="">Выберите упражнение</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Создать
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-filter"></i> Фильтры</h5>
                            </div>
                            <div class="card-body">
                                <form id="filterForm">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="filterStatus" class="form-label">Статус</label>
                                            <select class="form-control" id="filterStatus">
                                                <option value="">Все статусы</option>
                                                <option value="active">Активные</option>
                                                <option value="completed">Завершенные</option>
                                                <option value="failed">Проваленные</option>
                                                <option value="cancelled">Отмененные</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Применить фильтры
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                            <i class="fas fa-times"></i> Очистить
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- List -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-list"></i> Список целей</h5>
                                <button id="refreshList" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-sync-alt"></i> Обновить
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="listContainer">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Загрузка...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar"></i> Статистика достижений</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-info" onclick="loadStatistics()">
                                    <i class="fas fa-chart-bar"></i> Загрузить статистику
                                </button>
                                <div id="statisticsResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Completed Goals -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-check-circle"></i> Достигнутые цели</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-success" onclick="loadCompleted()">
                                    <i class="fas fa-check-circle"></i> Загрузить достигнутые цели
                                </button>
                                <div id="completedResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Failed Goals -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-times-circle"></i> Проваленные цели</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-danger" onclick="loadFailed()">
                                    <i class="fas fa-times-circle"></i> Загрузить проваленные цели
                                </button>
                                <div id="failedResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Update Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-edit"></i> Обновить цель</h5>
                            </div>
                            <div class="card-body">
                                <form id="updateForm">
                                    <div class="mb-3">
                                        <label for="updateId" class="form-label">ID цели *</label>
                                        <input type="number" class="form-control" id="updateId" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateType" class="form-label">Тип цели</label>
                                        <select class="form-control" id="updateType">
                                            <option value="">Не изменять</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="updateExerciseIdContainer" style="display: none;">
                                        <label for="updateExerciseId" class="form-label">Упражнение *</label>
                                        <select class="form-control" id="updateExerciseId">
                                            <option value="">Выберите упражнение</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateTitle" class="form-label">Название</label>
                                        <input type="text" class="form-control" id="updateTitle" maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateDescription" class="form-label">Описание</label>
                                        <textarea class="form-control" id="updateDescription" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateTargetValue" class="form-label">Целевое значение</label>
                                        <input type="number" class="form-control" id="updateTargetValue" step="0.01" min="0.01">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateEndDate" class="form-label">Дата окончания</label>
                                        <input type="date" class="form-control" id="updateEndDate">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateStatus" class="form-label">Статус</label>
                                        <select class="form-control" id="updateStatus">
                                            <option value="">Не изменять</option>
                                            <option value="active">Активная</option>
                                            <option value="completed">Завершенная</option>
                                            <option value="failed">Проваленная</option>
                                            <option value="cancelled">Отмененная</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Обновить
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Show -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-eye"></i> Показать цель</h5>
                            </div>
                            <div class="card-body">
                                <form id="showForm">
                                    <div class="mb-3">
                                        <label for="showId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="showId" required>
                                    </div>
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-eye"></i> Показать
                                    </button>
                                </form>
                                <div id="showResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Delete -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-trash"></i> Удалить цель</h5>
                            </div>
                            <div class="card-body">
                                <form id="deleteForm">
                                    <div class="mb-3">
                                        <label for="deleteId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="deleteId" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Удалить
                                    </button>
                                </form>
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
        // Автоматически определяем API адрес из текущего URL
        const API_BASE = `${window.location.protocol}//${window.location.host}/api/v1`;
        
        let goalTypes = [];
        let exercises = [];
        
        // Маппинг типов целей на человекочитаемые названия
        const goalTypeLabels = {
            'total_workouts': 'Всего тренировок',
            'completed_workouts': 'Завершенных тренировок',
            'target_weight': 'Целевой вес',
            'weight_loss': 'Похудение/сушка',
            'weight_gain': 'Массанабор',
            'total_volume': 'Общий объем тренировок',
            'weekly_volume': 'Недельный объем тренировок',
            'total_training_time': 'Общее время тренировок',
            'weekly_training_time': 'Недельное время тренировок',
            'training_frequency': 'Частота тренировок',
            'training_streak': 'Серия тренировок подряд',
            'exercise_max_weight': 'Максимальный вес в упражнении',
            'exercise_max_reps': 'Максимальное количество повторений',
            'exercise_volume': 'Объем упражнения'
        };
        
        // Функция для получения читаемого названия типа цели
        function getGoalTypeLabel(type) {
            // Сначала проверяем данные из API
            const goalType = goalTypes.find(gt => gt.value === type);
            if (goalType && goalType.label) {
                return goalType.label;
            }
            // Fallback на статический маппинг
            return goalTypeLabels[type] || type;
        }
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTypes();
            loadExercises();
            loadList();
        });

        function getAuthHeaders() {
            const token = localStorage.getItem('auth_token');
            return {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
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

        // Load Types
        async function loadTypes() {
            try {
                const response = await fetch(`${API_BASE}/goals/types`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    goalTypes = data.data;
                    
                    const createSelect = document.getElementById('createType');
                    const updateSelect = document.getElementById('updateType');
                    const options = data.data.map(item => {
                        const label = item.label || item.value;
                        const exerciseNote = item.requires_exercise ? ' (требует упражнение)' : '';
                        return `<option value="${item.value}" data-requires-exercise="${item.requires_exercise}">${label}${exerciseNote}</option>`;
                    }).join('');
                    
                    createSelect.innerHTML = '<option value="">Выберите тип цели</option>' + options;
                    updateSelect.innerHTML = '<option value="">Не изменять</option>' + options;
                    
                    // Show types result
                    const typesResult = document.getElementById('typesResult');
                    typesResult.innerHTML = `
                        <div class="alert alert-info">
                            <strong>Типы целей загружены:</strong>
                            <ul class="mb-0 mt-2">
                                ${data.data.map(item => {
                                    const label = item.label || item.value;
                                    const exerciseNote = item.requires_exercise ? ' (требует упражнение)' : '';
                                    return `<li>${label}${exerciseNote}</li>`;
                                }).join('')}
                            </ul>
                        </div>
                    `;
                } else {
                    showResponse(data, true);
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        }

        // Load Exercises
        async function loadExercises() {
            try {
                const response = await fetch(`${API_BASE}/exercises`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    exercises = data.data;
                    
                    const createSelect = document.getElementById('createExerciseId');
                    const updateSelect = document.getElementById('updateExerciseId');
                    const options = data.data.map(item => 
                        `<option value="${item.id}">${item.name}</option>`
                    ).join('');
                    
                    createSelect.innerHTML = '<option value="">Выберите упражнение</option>' + options;
                    updateSelect.innerHTML = '<option value="">Выберите упражнение</option>' + options;
                }
            } catch (error) {
                console.error('Error loading exercises:', error);
            }
        }

        // Handle type change to show/hide exercise field (create form)
        document.getElementById('createType').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const requiresExercise = selectedOption.getAttribute('data-requires-exercise') === 'true';
            const exerciseContainer = document.getElementById('createExerciseIdContainer');
            const exerciseSelect = document.getElementById('createExerciseId');
            
            if (requiresExercise) {
                exerciseContainer.style.display = 'block';
                exerciseSelect.required = true;
            } else {
                exerciseContainer.style.display = 'none';
                exerciseSelect.required = false;
                exerciseSelect.value = '';
            }
        });

        // Handle type change to show/hide exercise field (update form)
        document.getElementById('updateType').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const requiresExercise = selectedOption.getAttribute('data-requires-exercise') === 'true';
            const exerciseContainer = document.getElementById('updateExerciseIdContainer');
            const exerciseSelect = document.getElementById('updateExerciseId');
            
            if (requiresExercise) {
                exerciseContainer.style.display = 'block';
                exerciseSelect.required = true;
            } else {
                exerciseContainer.style.display = 'none';
                exerciseSelect.required = false;
                exerciseSelect.value = '';
            }
        });

        // Load List
        async function loadList(filters = {}) {
            try {
                const queryParams = new URLSearchParams();
                
                // Add filters to query params
                Object.keys(filters).forEach(key => {
                    if (filters[key] !== '' && filters[key] !== null && filters[key] !== undefined) {
                        queryParams.append(key, filters[key]);
                    }
                });
                
                const url = `${API_BASE}/goals${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
                const response = await fetch(url, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok) {
                    const listContainer = document.getElementById('listContainer');
                    if (data.data && data.data.length > 0) {
                        listContainer.innerHTML = `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Тип</th>
                                            <th>Название</th>
                                            <th>Целевое значение</th>
                                            <th>Текущее значение</th>
                                            <th>Прогресс</th>
                                            <th>Статус</th>
                                            <th>Дата начала</th>
                                            <th>Дата окончания</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${getGoalTypeLabel(item.type)}</td>
                                                <td>${item.title}</td>
                                                <td>${item.target_value}</td>
                                                <td>${item.current_value || 0}</td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar ${item.progress_percentage >= 100 ? 'bg-success' : item.progress_percentage >= 50 ? 'bg-warning' : 'bg-info'}" 
                                                             role="progressbar" 
                                                             style="width: ${Math.min(item.progress_percentage, 100)}%">
                                                            ${item.progress_percentage.toFixed(1)}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge ${getStatusBadgeClass(item.status)}">${getStatusText(item.status)}</span>
                                                </td>
                                                <td>${new Date(item.start_date).toLocaleDateString('ru-RU')}</td>
                                                <td>${item.end_date ? new Date(item.end_date).toLocaleDateString('ru-RU') : '-'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        listContainer.innerHTML = '<div class="text-center text-muted">Нет данных</div>';
                    }
                } else {
                    showResponse(data, true);
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        }

        function getStatusBadgeClass(status) {
            const classes = {
                'active': 'bg-primary',
                'completed': 'bg-success',
                'failed': 'bg-danger',
                'cancelled': 'bg-secondary'
            };
            return classes[status] || 'bg-secondary';
        }

        function getStatusText(status) {
            const texts = {
                'active': 'Активная',
                'completed': 'Завершенная',
                'failed': 'Проваленная',
                'cancelled': 'Отмененная'
            };
            return texts[status] || status;
        }

        // Refresh List
        document.getElementById('refreshList').addEventListener('click', loadList);

        // Filter Form
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const filters = {
                status: document.getElementById('filterStatus').value
            };
            
            loadList(filters);
        });

        // Clear Filters
        function clearFilters() {
            document.getElementById('filterForm').reset();
            loadList();
        }

        // Load Statistics
        async function loadStatistics() {
            try {
                const response = await fetch(`${API_BASE}/goals/statistics`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok) {
                    const statisticsResult = document.getElementById('statisticsResult');
                    statisticsResult.innerHTML = `
                        <div class="alert alert-info">
                            <h6>Статистика достижений:</h6>
                            <pre>${JSON.stringify(data.data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    showResponse(data, true);
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        }

        // Load Completed
        async function loadCompleted() {
            try {
                const response = await fetch(`${API_BASE}/goals/completed`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok) {
                    const completedResult = document.getElementById('completedResult');
                    if (data.data && data.data.length > 0) {
                        completedResult.innerHTML = `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Тип</th>
                                            <th>Название</th>
                                            <th>Целевое значение</th>
                                            <th>Текущее значение</th>
                                            <th>Дата завершения</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${getGoalTypeLabel(item.type)}</td>
                                                <td>${item.title}</td>
                                                <td>${item.target_value}</td>
                                                <td>${item.current_value || 0}</td>
                                                <td>${item.completed_at ? new Date(item.completed_at).toLocaleDateString('ru-RU') : '-'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        completedResult.innerHTML = '<div class="text-center text-muted">Нет достигнутых целей</div>';
                    }
                } else {
                    showResponse(data, true);
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        }

        // Load Failed
        async function loadFailed() {
            try {
                const response = await fetch(`${API_BASE}/goals/failed`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok) {
                    const failedResult = document.getElementById('failedResult');
                    if (data.data && data.data.length > 0) {
                        failedResult.innerHTML = `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Тип</th>
                                            <th>Название</th>
                                            <th>Целевое значение</th>
                                            <th>Текущее значение</th>
                                            <th>Дата окончания</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${getGoalTypeLabel(item.type)}</td>
                                                <td>${item.title}</td>
                                                <td>${item.target_value}</td>
                                                <td>${item.current_value || 0}</td>
                                                <td>${item.end_date ? new Date(item.end_date).toLocaleDateString('ru-RU') : '-'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        failedResult.innerHTML = '<div class="text-center text-muted">Нет проваленных целей</div>';
                    }
                } else {
                    showResponse(data, true);
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        }

        // Create
        document.getElementById('createForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const type = document.getElementById('createType').value;
            const selectedOption = document.getElementById('createType').options[document.getElementById('createType').selectedIndex];
            const requiresExercise = selectedOption.getAttribute('data-requires-exercise') === 'true';
            
            const formData = {
                type: type,
                title: document.getElementById('createTitle').value,
                description: document.getElementById('createDescription').value || null,
                target_value: parseFloat(document.getElementById('createTargetValue').value),
                start_date: document.getElementById('createStartDate').value,
                end_date: document.getElementById('createEndDate').value || null
            };

            if (requiresExercise) {
                const exerciseId = document.getElementById('createExerciseId').value;
                if (!exerciseId) {
                    showResponse({error: 'Упражнение обязательно для данного типа цели'}, true);
                    return;
                }
                formData.exercise_id = parseInt(exerciseId);
            }

            try {
                const response = await fetch(`${API_BASE}/goals`, {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('createForm').reset();
                    document.getElementById('createExerciseIdContainer').style.display = 'none';
                    loadList();
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Update
        document.getElementById('updateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('updateId').value;
            const formData = {};

            const type = document.getElementById('updateType').value;
            const title = document.getElementById('updateTitle').value;
            const description = document.getElementById('updateDescription').value;
            const targetValue = document.getElementById('updateTargetValue').value;
            const endDate = document.getElementById('updateEndDate').value;
            const status = document.getElementById('updateStatus').value;
            const exerciseId = document.getElementById('updateExerciseId').value;

            if (type) formData.type = type;
            if (title) formData.title = title;
            if (description) formData.description = description;
            if (targetValue) formData.target_value = parseFloat(targetValue);
            if (endDate) formData.end_date = endDate;
            if (status) formData.status = status;
            
            // Если тип требует упражнение, добавляем exercise_id
            if (type) {
                const selectedOption = document.getElementById('updateType').options[document.getElementById('updateType').selectedIndex];
                const requiresExercise = selectedOption.getAttribute('data-requires-exercise') === 'true';
                if (requiresExercise) {
                    if (exerciseId) {
                        formData.exercise_id = parseInt(exerciseId);
                    }
                }
            } else if (exerciseId) {
                // Если тип не меняется, но exercise_id передан, добавляем его
                formData.exercise_id = parseInt(exerciseId);
            }

            try {
                const response = await fetch(`${API_BASE}/goals/${id}`, {
                    method: 'PUT',
                    headers: getAuthHeaders(),
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('updateForm').reset();
                    document.getElementById('updateExerciseIdContainer').style.display = 'none';
                    loadList();
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Show
        document.getElementById('showForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('showId').value;

            try {
                const response = await fetch(`${API_BASE}/goals/${id}`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    const showResult = document.getElementById('showResult');
                    const goal = data.data;
                    showResult.innerHTML = `
                        <div class="alert alert-info">
                            <strong>ID:</strong> ${goal.id}<br>
                            <strong>Тип:</strong> ${getGoalTypeLabel(goal.type)}<br>
                            <strong>Название:</strong> ${goal.title}<br>
                            <strong>Описание:</strong> ${goal.description || 'Не указано'}<br>
                            <strong>Целевое значение:</strong> ${goal.target_value}<br>
                            <strong>Текущее значение:</strong> ${goal.current_value || 0}<br>
                            <strong>Прогресс:</strong> ${goal.progress_percentage.toFixed(1)}%<br>
                            <strong>Статус:</strong> <span class="badge ${getStatusBadgeClass(goal.status)}">${getStatusText(goal.status)}</span><br>
                            <strong>Дата начала:</strong> ${new Date(goal.start_date).toLocaleDateString('ru-RU')}<br>
                            <strong>Дата окончания:</strong> ${goal.end_date ? new Date(goal.end_date).toLocaleDateString('ru-RU') : 'Не указана'}<br>
                            <strong>Упражнение:</strong> ${goal.exercise ? goal.exercise.name : 'Не указано'}<br>
                            <strong>Создано:</strong> ${new Date(goal.created_at).toLocaleString('ru-RU')}<br>
                            <strong>Обновлено:</strong> ${new Date(goal.updated_at).toLocaleString('ru-RU')}
                        </div>
                    `;
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Delete
        document.getElementById('deleteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('deleteId').value;

            if (!confirm(`Вы уверены, что хотите удалить цель с ID ${id}?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/goals/${id}`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('deleteForm').reset();
                    loadList();
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });
    </script>
</body>
</html>

