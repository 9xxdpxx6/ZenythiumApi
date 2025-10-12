<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Упражнения - Test Forms</title>
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
                            <i class="fas fa-running"></i>
                            Упражнения - CRUD
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
                                <h5><i class="fas fa-plus"></i> Создать упражнение</h5>
                            </div>
                            <div class="card-body">
                                <form id="createForm">
                                    <div class="mb-3">
                                        <label for="createName" class="form-label">Название *</label>
                                        <input type="text" class="form-control" id="createName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createDescription" class="form-label">Описание</label>
                                        <textarea class="form-control" id="createDescription" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createMuscleGroupId" class="form-label">Группа мышц *</label>
                                        <select class="form-control" id="createMuscleGroupId" required>
                                            <option value="">Выберите группу мышц</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="createIsActive" checked>
                                            <label class="form-check-label" for="createIsActive">
                                                Активно
                                            </label>
                                        </div>
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
                                            <label for="filterSearch" class="form-label">Поиск по названию/описанию</label>
                                            <input type="text" class="form-control" id="filterSearch" placeholder="Введите название или описание">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterUserId" class="form-label">ID пользователя</label>
                                            <input type="number" class="form-control" id="filterUserId" placeholder="ID пользователя">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterMuscleGroupId" class="form-label">Группа мышц</label>
                                            <select class="form-control" id="filterMuscleGroupId">
                                                <option value="">Все группы мышц</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterIsActive" class="form-label">Статус активности</label>
                                            <select class="form-control" id="filterIsActive">
                                                <option value="">Все</option>
                                                <option value="1">Активные</option>
                                                <option value="0">Неактивные</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterDateFrom" class="form-label">Дата создания от</label>
                                            <input type="date" class="form-control" id="filterDateFrom">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterDateTo" class="form-label">Дата создания до</label>
                                            <input type="date" class="form-control" id="filterDateTo">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterSortBy" class="form-label">Сортировка по</label>
                                            <select class="form-control" id="filterSortBy">
                                                <option value="name">Название</option>
                                                <option value="created_at">Дата создания</option>
                                                <option value="muscle_group_id">Группа мышц</option>
                                                <option value="is_active">Статус активности</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterSortOrder" class="form-label">Порядок сортировки</label>
                                            <select class="form-control" id="filterSortOrder">
                                                <option value="asc">По возрастанию</option>
                                                <option value="desc">По убыванию</option>
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
                                <h5><i class="fas fa-list"></i> Список упражнений</h5>
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
                                <h5><i class="fas fa-edit"></i> Обновить упражнение</h5>
                            </div>
                            <div class="card-body">
                                <form id="updateForm">
                                    <div class="mb-3">
                                        <label for="updateId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="updateId" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateName" class="form-label">Название *</label>
                                        <input type="text" class="form-control" id="updateName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateDescription" class="form-label">Описание</label>
                                        <textarea class="form-control" id="updateDescription" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateMuscleGroupId" class="form-label">Группа мышц *</label>
                                        <select class="form-control" id="updateMuscleGroupId" required>
                                            <option value="">Выберите группу мышц</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="updateIsActive">
                                            <label class="form-check-label" for="updateIsActive">
                                                Активно
                                            </label>
                                        </div>
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
                                <h5><i class="fas fa-eye"></i> Показать упражнение</h5>
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
                                <h5><i class="fas fa-trash"></i> Удалить упражнение</h5>
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
            loadMuscleGroups();
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

        // Load Muscle Groups for dropdowns
        async function loadMuscleGroups() {
            try {
                const response = await fetch(`${API_BASE}/muscle-groups`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    const createSelect = document.getElementById('createMuscleGroupId');
                    const updateSelect = document.getElementById('updateMuscleGroupId');
                    
                    const options = data.data.map(item => 
                        `<option value="${item.id}">${item.name}</option>`
                    ).join('');
                    
                    createSelect.innerHTML = '<option value="">Выберите группу мышц</option>' + options;
                    updateSelect.innerHTML = '<option value="">Выберите группу мышц</option>' + options;
                }
            } catch (error) {
                console.error('Error loading muscle groups:', error);
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
                
                const url = `${API_BASE}/exercises${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
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
                                            <th>Название</th>
                                            <th>Описание</th>
                                            <th>Группа мышц</th>
                                            <th>Активно</th>
                                            <th>Создано</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${item.name}</td>
                                                <td>${item.description || '-'}</td>
                                                <td>${item.muscle_group ? item.muscle_group.name : '-'}</td>
                                                <td>${item.is_active ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'}</td>
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
                muscle_group_id: document.getElementById('filterMuscleGroupId').value,
                is_active: document.getElementById('filterIsActive').value,
                date_from: document.getElementById('filterDateFrom').value,
                date_to: document.getElementById('filterDateTo').value,
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
                name: document.getElementById('createName').value,
                description: document.getElementById('createDescription').value,
                muscle_group_id: parseInt(document.getElementById('createMuscleGroupId').value),
                is_active: document.getElementById('createIsActive').checked
            };

            try {
                const response = await fetch(`${API_BASE}/exercises`, {
                    method: 'POST',
                    headers: getAuthHeaders(),
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    document.getElementById('createForm').reset();
                    document.getElementById('createIsActive').checked = true;
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
                name: document.getElementById('updateName').value,
                description: document.getElementById('updateDescription').value,
                muscle_group_id: parseInt(document.getElementById('updateMuscleGroupId').value),
                is_active: document.getElementById('updateIsActive').checked
            };

            try {
                const response = await fetch(`${API_BASE}/exercises/${id}`, {
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
                const response = await fetch(`${API_BASE}/exercises/${id}`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    const showResult = document.getElementById('showResult');
                    showResult.innerHTML = `
                        <div class="alert alert-info">
                            <strong>ID:</strong> ${data.data.id}<br>
                            <strong>Название:</strong> ${data.data.name}<br>
                            <strong>Описание:</strong> ${data.data.description || 'Не указано'}<br>
                            <strong>Группа мышц:</strong> ${data.data.muscle_group ? data.data.muscle_group.name : 'Не указана'}<br>
                            <strong>Активно:</strong> ${data.data.is_active ? 'Да' : 'Нет'}<br>
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

            if (!confirm(`Вы уверены, что хотите удалить упражнение с ID ${id}?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/exercises/${id}`, {
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
