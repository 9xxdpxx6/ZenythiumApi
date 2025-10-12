<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подходы - Test Forms</title>
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
                            <i class="fas fa-layer-group"></i>
                            Подходы - CRUD
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
                                <h5><i class="fas fa-plus"></i> Создать подход</h5>
                            </div>
                            <div class="card-body">
                                <form id="createForm">
                                    <div class="mb-3">
                                        <label for="createWorkoutId" class="form-label">Тренировка *</label>
                                        <select class="form-control" id="createWorkoutId" required>
                                            <option value="">Выберите тренировку</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createPlanExerciseId" class="form-label">Упражнение плана *</label>
                                        <select class="form-control" id="createPlanExerciseId" required>
                                            <option value="">Выберите упражнение</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createWeight" class="form-label">Вес (кг)</label>
                                        <input type="number" class="form-control" id="createWeight" step="0.01" min="0" max="999.99">
                                    </div>
                                    <div class="mb-3">
                                        <label for="createReps" class="form-label">Повторения</label>
                                        <input type="number" class="form-control" id="createReps" min="0" max="9999">
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
                                            <label for="filterSearch" class="form-label">Поиск по плану/упражнению/пользователю</label>
                                            <input type="text" class="form-control" id="filterSearch" placeholder="Введите название плана, упражнения или имя пользователя">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterUserId" class="form-label">ID пользователя</label>
                                            <input type="number" class="form-control" id="filterUserId" placeholder="ID пользователя">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterWorkoutId" class="form-label">Тренировка</label>
                                            <select class="form-control" id="filterWorkoutId">
                                                <option value="">Все тренировки</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterPlanExerciseId" class="form-label">Упражнение плана</label>
                                            <select class="form-control" id="filterPlanExerciseId">
                                                <option value="">Все упражнения</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterWeightFrom" class="form-label">Вес от (кг)</label>
                                            <input type="number" class="form-control" id="filterWeightFrom" step="0.01" min="0" max="999.99">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterWeightTo" class="form-label">Вес до (кг)</label>
                                            <input type="number" class="form-control" id="filterWeightTo" step="0.01" min="0" max="999.99">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterRepsFrom" class="form-label">Повторения от</label>
                                            <input type="number" class="form-control" id="filterRepsFrom" min="0" max="9999">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterRepsTo" class="form-label">Повторения до</label>
                                            <input type="number" class="form-control" id="filterRepsTo" min="0" max="9999">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterSortBy" class="form-label">Сортировка по</label>
                                            <select class="form-control" id="filterSortBy">
                                                <option value="created_at">Дата создания</option>
                                                <option value="weight">Вес</option>
                                                <option value="reps">Повторения</option>
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
                                <h5><i class="fas fa-list"></i> Список подходов</h5>
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
                                <h5><i class="fas fa-edit"></i> Обновить подход</h5>
                            </div>
                            <div class="card-body">
                                <form id="updateForm">
                                    <div class="mb-3">
                                        <label for="updateId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="updateId" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateWorkoutId" class="form-label">Тренировка</label>
                                        <select class="form-control" id="updateWorkoutId">
                                            <option value="">Выберите тренировку</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updatePlanExerciseId" class="form-label">Упражнение плана</label>
                                        <select class="form-control" id="updatePlanExerciseId">
                                            <option value="">Выберите упражнение</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateWeight" class="form-label">Вес (кг)</label>
                                        <input type="number" class="form-control" id="updateWeight" step="0.01" min="0" max="999.99">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateReps" class="form-label">Повторения</label>
                                        <input type="number" class="form-control" id="updateReps" min="0" max="9999">
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
                                <h5><i class="fas fa-eye"></i> Показать подход</h5>
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
                                <h5><i class="fas fa-trash"></i> Удалить подход</h5>
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
            loadWorkouts();
            loadPlanExercises();
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

        // Load Workouts for dropdowns
        async function loadWorkouts() {
            try {
                const response = await fetch(`${API_BASE}/workouts`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    const createSelect = document.getElementById('createWorkoutId');
                    const updateSelect = document.getElementById('updateWorkoutId');
                    
                    const options = data.data.map(item => 
                        `<option value="${item.id}">ID: ${item.id} - ${item.plan ? item.plan.name : 'План не указан'} (${item.started_at ? new Date(item.started_at).toLocaleDateString('ru-RU') : 'Дата не указана'})</option>`
                    ).join('');
                    
                    createSelect.innerHTML = '<option value="">Выберите тренировку</option>' + options;
                    updateSelect.innerHTML = '<option value="">Выберите тренировку</option>' + options;
                }
            } catch (error) {
                console.error('Error loading workouts:', error);
            }
        }

        // Load Plan Exercises for dropdowns
        async function loadPlanExercises() {
            try {
                // Note: This endpoint might not exist, so we'll try to get it from plans
                const response = await fetch(`${API_BASE}/plans`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    // For now, we'll create a simple dropdown
                    // In a real app, you'd need a proper endpoint for plan exercises
                    const createSelect = document.getElementById('createPlanExerciseId');
                    const updateSelect = document.getElementById('updatePlanExerciseId');
                    
                    // This is a placeholder - you'd need to implement proper plan exercises loading
                    createSelect.innerHTML = '<option value="">Выберите упражнение</option><option value="1">Упражнение 1</option><option value="2">Упражнение 2</option>';
                    updateSelect.innerHTML = '<option value="">Выберите упражнение</option><option value="1">Упражнение 1</option><option value="2">Упражнение 2</option>';
                }
            } catch (error) {
                console.error('Error loading plan exercises:', error);
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
                
                const url = `${API_BASE}/workout-sets${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
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
                                            <th>Тренировка</th>
                                            <th>Упражнение</th>
                                            <th>Вес (кг)</th>
                                            <th>Повторения</th>
                                            <th>Объем</th>
                                            <th>Создано</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${item.workout ? 'ID: ' + item.workout.id : '-'}</td>
                                                <td>${item.plan_exercise ? 'ID: ' + item.plan_exercise.id : '-'}</td>
                                                <td>${item.weight || '-'}</td>
                                                <td>${item.reps || '-'}</td>
                                                <td>${item.weight && item.reps ? (item.weight * item.reps).toFixed(2) + ' кг' : '-'}</td>
                                                <td>${new Date(item.created_at).toLocaleString('ru-RU')}</td>
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
                workout_id: document.getElementById('filterWorkoutId').value,
                plan_exercise_id: document.getElementById('filterPlanExerciseId').value,
                weight_from: document.getElementById('filterWeightFrom').value,
                weight_to: document.getElementById('filterWeightTo').value,
                reps_from: document.getElementById('filterRepsFrom').value,
                reps_to: document.getElementById('filterRepsTo').value,
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
                workout_id: parseInt(document.getElementById('createWorkoutId').value),
                plan_exercise_id: parseInt(document.getElementById('createPlanExerciseId').value),
                weight: document.getElementById('createWeight').value ? parseFloat(document.getElementById('createWeight').value) : null,
                reps: document.getElementById('createReps').value ? parseInt(document.getElementById('createReps').value) : null
            };

            try {
                const response = await fetch(`${API_BASE}/workout-sets`, {
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

        // Update
        document.getElementById('updateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('updateId').value;
            const formData = {};
            
            if (document.getElementById('updateWorkoutId').value) {
                formData.workout_id = parseInt(document.getElementById('updateWorkoutId').value);
            }
            if (document.getElementById('updatePlanExerciseId').value) {
                formData.plan_exercise_id = parseInt(document.getElementById('updatePlanExerciseId').value);
            }
            if (document.getElementById('updateWeight').value) {
                formData.weight = parseFloat(document.getElementById('updateWeight').value);
            }
            if (document.getElementById('updateReps').value) {
                formData.reps = parseInt(document.getElementById('updateReps').value);
            }

            try {
                const response = await fetch(`${API_BASE}/workout-sets/${id}`, {
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
                const response = await fetch(`${API_BASE}/workout-sets/${id}`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    const showResult = document.getElementById('showResult');
                    showResult.innerHTML = `
                        <div class="alert alert-info">
                            <strong>ID:</strong> ${data.data.id}<br>
                            <strong>Тренировка:</strong> ${data.data.workout ? 'ID: ' + data.data.workout.id : 'Не указана'}<br>
                            <strong>Упражнение плана:</strong> ${data.data.plan_exercise ? 'ID: ' + data.data.plan_exercise.id : 'Не указано'}<br>
                            <strong>Вес:</strong> ${data.data.weight ? data.data.weight + ' кг' : 'Не указан'}<br>
                            <strong>Повторения:</strong> ${data.data.reps || 'Не указано'}<br>
                            <strong>Объем:</strong> ${data.data.weight && data.data.reps ? (data.data.weight * data.data.reps).toFixed(2) + ' кг' : 'Не указан'}<br>
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

            if (!confirm(`Вы уверены, что хотите удалить подход с ID ${id}?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/workout-sets/${id}`, {
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
