<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тренировки - Test Forms</title>
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
                            <i class="fas fa-fire"></i>
                            Тренировки - CRUD
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Navigation -->
                        <div class="mb-3">
                            <a href="/test-forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                        </div>

                        <!-- Create Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-plus"></i> Создать тренировку</h5>
                            </div>
                            <div class="card-body">
                                <form id="createForm">
                                    <div class="mb-3">
                                        <label for="createPlanId" class="form-label">План *</label>
                                        <select class="form-control" id="createPlanId" required>
                                            <option value="">Выберите план</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createStartedAt" class="form-label">Время начала *</label>
                                        <input type="datetime-local" class="form-control" id="createStartedAt" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createFinishedAt" class="form-label">Время окончания</label>
                                        <input type="datetime-local" class="form-control" id="createFinishedAt">
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Создать
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Start Workout -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-play"></i> Начать тренировку</h5>
                            </div>
                            <div class="card-body">
                                <form id="startForm">
                                    <div class="mb-3">
                                        <label for="startPlanId" class="form-label">План *</label>
                                        <select class="form-control" id="startPlanId" required>
                                            <option value="">Выберите план</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Начать тренировку
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Finish Workout -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-stop"></i> Завершить тренировку</h5>
                            </div>
                            <div class="card-body">
                                <form id="finishForm">
                                    <div class="mb-3">
                                        <label for="finishWorkoutId" class="form-label">ID тренировки *</label>
                                        <input type="number" class="form-control" id="finishWorkoutId" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-stop"></i> Завершить тренировку
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
                                            <label for="filterSearch" class="form-label">Поиск по плану/пользователю</label>
                                            <input type="text" class="form-control" id="filterSearch" placeholder="Введите название плана или имя пользователя">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterUserId" class="form-label">ID пользователя</label>
                                            <input type="number" class="form-control" id="filterUserId" placeholder="ID пользователя">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterPlanId" class="form-label">План</label>
                                            <select class="form-control" id="filterPlanId">
                                                <option value="">Все планы</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterCompleted" class="form-label">Статус завершения</label>
                                            <select class="form-control" id="filterCompleted">
                                                <option value="">Все</option>
                                                <option value="true">Завершенные</option>
                                                <option value="false">Незавершенные</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterStartedAtFrom" class="form-label">Начало от</label>
                                            <input type="datetime-local" class="form-control" id="filterStartedAtFrom">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterStartedAtTo" class="form-label">Начало до</label>
                                            <input type="datetime-local" class="form-control" id="filterStartedAtTo">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterFinishedAtFrom" class="form-label">Окончание от</label>
                                            <input type="datetime-local" class="form-control" id="filterFinishedAtFrom">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterFinishedAtTo" class="form-label">Окончание до</label>
                                            <input type="datetime-local" class="form-control" id="filterFinishedAtTo">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterSortBy" class="form-label">Сортировка по</label>
                                            <select class="form-control" id="filterSortBy">
                                                <option value="started_at">Время начала</option>
                                                <option value="finished_at">Время окончания</option>
                                                <option value="created_at">Дата создания</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterSortOrder" class="form-label">Порядок сортировки</label>
                                            <select class="form-control" id="filterSortOrder">
                                                <option value="desc">По убыванию</option>
                                                <option value="asc">По возрастанию</option>
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
                                <h5><i class="fas fa-list"></i> Список тренировок</h5>
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

                        <!-- Update Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-edit"></i> Обновить тренировку</h5>
                            </div>
                            <div class="card-body">
                                <form id="updateForm">
                                    <div class="mb-3">
                                        <label for="updateId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="updateId" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updatePlanId" class="form-label">План *</label>
                                        <select class="form-control" id="updatePlanId" required>
                                            <option value="">Выберите план</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateStartedAt" class="form-label">Время начала *</label>
                                        <input type="datetime-local" class="form-control" id="updateStartedAt" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateFinishedAt" class="form-label">Время окончания</label>
                                        <input type="datetime-local" class="form-control" id="updateFinishedAt">
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
                                <h5><i class="fas fa-eye"></i> Показать тренировку</h5>
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
                                <h5><i class="fas fa-trash"></i> Удалить тренировку</h5>
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
        const API_BASE = 'http://localhost:8000/api/v1';
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPlans();
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

        // Load Plans for dropdowns
        async function loadPlans() {
            try {
                const response = await fetch(`${API_BASE}/plans`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    const createSelect = document.getElementById('createPlanId');
                    const startSelect = document.getElementById('startPlanId');
                    const updateSelect = document.getElementById('updatePlanId');
                    
                    const options = data.data.map(item => 
                        `<option value="${item.id}">${item.name} (Цикл: ${item.cycle ? item.cycle.name : 'Не указан'})</option>`
                    ).join('');
                    
                    createSelect.innerHTML = '<option value="">Выберите план</option>' + options;
                    startSelect.innerHTML = '<option value="">Выберите план</option>' + options;
                    updateSelect.innerHTML = '<option value="">Выберите план</option>' + options;
                }
            } catch (error) {
                console.error('Error loading plans:', error);
            }
        }

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
                
                const url = `${API_BASE}/workouts${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
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
                                            <th>План</th>
                                            <th>Время начала</th>
                                            <th>Время окончания</th>
                                            <th>Продолжительность</th>
                                            <th>Упражнений</th>
                                            <th>Общий объем</th>
                                            <th>Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${item.plan ? item.plan.name : '-'}</td>
                                                <td>${item.started_at ? new Date(item.started_at).toLocaleString('ru-RU') : '-'}</td>
                                                <td>${item.finished_at ? new Date(item.finished_at).toLocaleString('ru-RU') : '-'}</td>
                                                <td>${item.duration_minutes ? item.duration_minutes + ' мин' : '-'}</td>
                                                <td>${item.exercise_count}</td>
                                                <td>${item.total_volume ? item.total_volume + ' кг' : '-'}</td>
                                                <td>${item.finished_at ? '<span class="badge bg-success">Завершена</span>' : '<span class="badge bg-warning">В процессе</span>'}</td>
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

        // Refresh List
        document.getElementById('refreshList').addEventListener('click', loadList);

        // Filter Form
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const filters = {
                search: document.getElementById('filterSearch').value,
                user_id: document.getElementById('filterUserId').value,
                plan_id: document.getElementById('filterPlanId').value,
                completed: document.getElementById('filterCompleted').value,
                started_at_from: document.getElementById('filterStartedAtFrom').value,
                started_at_to: document.getElementById('filterStartedAtTo').value,
                finished_at_from: document.getElementById('filterFinishedAtFrom').value,
                finished_at_to: document.getElementById('filterFinishedAtTo').value,
                sort_by: document.getElementById('filterSortBy').value,
                sort_order: document.getElementById('filterSortOrder').value
            };
            
            loadList(filters);
        });

        // Clear Filters
        function clearFilters() {
            document.getElementById('filterForm').reset();
            loadList();
        }

        // Create
        document.getElementById('createForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                plan_id: parseInt(document.getElementById('createPlanId').value),
                started_at: document.getElementById('createStartedAt').value,
                finished_at: document.getElementById('createFinishedAt').value || null
            };

            try {
                const response = await fetch(`${API_BASE}/workouts`, {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('createForm').reset();
                    loadList();
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Start Workout
        document.getElementById('startForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                plan_id: parseInt(document.getElementById('startPlanId').value)
            };

            try {
                const response = await fetch(`${API_BASE}/workouts/start`, {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('startForm').reset();
                    loadList();
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Finish Workout
        document.getElementById('finishForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const workoutId = document.getElementById('finishWorkoutId').value;

            try {
                const response = await fetch(`${API_BASE}/workouts/${workoutId}/finish`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('finishForm').reset();
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
            const formData = {
                plan_id: parseInt(document.getElementById('updatePlanId').value),
                started_at: document.getElementById('updateStartedAt').value,
                finished_at: document.getElementById('updateFinishedAt').value || null
            };

            try {
                const response = await fetch(`${API_BASE}/workouts/${id}`, {
                    method: 'PUT',
                    headers: getAuthHeaders(),
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('updateForm').reset();
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
                const response = await fetch(`${API_BASE}/workouts/${id}`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    const showResult = document.getElementById('showResult');
                    showResult.innerHTML = `
                        <div class="alert alert-info">
                            <strong>ID:</strong> ${data.data.id}<br>
                            <strong>План:</strong> ${data.data.plan ? data.data.plan.name : 'Не указан'}<br>
                            <strong>Время начала:</strong> ${data.data.started_at ? new Date(data.data.started_at).toLocaleString('ru-RU') : 'Не указано'}<br>
                            <strong>Время окончания:</strong> ${data.data.finished_at ? new Date(data.data.finished_at).toLocaleString('ru-RU') : 'Не указано'}<br>
                            <strong>Продолжительность:</strong> ${data.data.duration_minutes ? data.data.duration_minutes + ' минут' : 'Не завершена'}<br>
                            <strong>Количество упражнений:</strong> ${data.data.exercise_count}<br>
                            <strong>Общий объем:</strong> ${data.data.total_volume ? data.data.total_volume + ' кг' : 'Не указан'}<br>
                            <strong>Создано:</strong> ${new Date(data.data.created_at).toLocaleString('ru-RU')}<br>
                            <strong>Обновлено:</strong> ${new Date(data.data.updated_at).toLocaleString('ru-RU')}
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

            if (!confirm(`Вы уверены, что хотите удалить тренировку с ID ${id}?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/workouts/${id}`, {
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
