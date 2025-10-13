<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Планы - Test Forms</title>
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
                            <i class="fas fa-clipboard-list"></i>
                            Планы - CRUD
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
                                <h5><i class="fas fa-plus"></i> Создать план</h5>
                            </div>
                            <div class="card-body">
                                <form id="createForm">
                                    <div class="mb-3">
                                        <label for="createCycleId" class="form-label">Цикл *</label>
                                        <select class="form-control" id="createCycleId" required>
                                            <option value="">Выберите цикл</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createName" class="form-label">Название *</label>
                                        <input type="text" class="form-control" id="createName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="createOrder" class="form-label">Порядок</label>
                                        <input type="number" class="form-control" id="createOrder" min="1">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="createIsActive" checked>
                                            <label class="form-check-label" for="createIsActive">
                                                Активен
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
                                            <label for="filterSearch" class="form-label">Поиск по названию</label>
                                            <input type="text" class="form-control" id="filterSearch" placeholder="Введите название плана">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterUserId" class="form-label">ID пользователя</label>
                                            <input type="number" class="form-control" id="filterUserId" placeholder="ID пользователя">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterCycleId" class="form-label">Цикл</label>
                                            <select class="form-control" id="filterCycleId">
                                                <option value="">Все циклы</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterOrder" class="form-label">Порядок</label>
                                            <input type="number" class="form-control" id="filterOrder" min="1" placeholder="Порядок плана">
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
                                                <option value="order">Порядок</option>
                                                <option value="name">Название</option>
                                                <option value="is_active">Статус активности</option>
                                                <option value="created_at">Дата создания</option>
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
                                <h5><i class="fas fa-list"></i> Список планов</h5>
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
                                <h5><i class="fas fa-edit"></i> Обновить план</h5>
                            </div>
                            <div class="card-body">
                                <form id="updateForm">
                                    <div class="mb-3">
                                        <label for="updateId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="updateId" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateCycleId" class="form-label">Цикл *</label>
                                        <select class="form-control" id="updateCycleId" required>
                                            <option value="">Выберите цикл</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateName" class="form-label">Название *</label>
                                        <input type="text" class="form-control" id="updateName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateOrder" class="form-label">Порядок</label>
                                        <input type="number" class="form-control" id="updateOrder" min="1">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="updateIsActive">
                                            <label class="form-check-label" for="updateIsActive">
                                                Активен
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
                                <h5><i class="fas fa-eye"></i> Показать план</h5>
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
                                <h5><i class="fas fa-trash"></i> Удалить план</h5>
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
            loadCycles();
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

        // Load Cycles for dropdowns
        async function loadCycles() {
            try {
                const response = await fetch(`${API_BASE}/cycles`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    const createSelect = document.getElementById('createCycleId');
                    const updateSelect = document.getElementById('updateCycleId');
                    
                    const options = data.data.map(item => 
                        `<option value="${item.id}">${item.name}</option>`
                    ).join('');
                    
                    createSelect.innerHTML = '<option value="">Выберите цикл</option>' + options;
                    updateSelect.innerHTML = '<option value="">Выберите цикл</option>' + options;
                }
            } catch (error) {
                console.error('Error loading cycles:', error);
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
                
                const url = `${API_BASE}/plans${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
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
                                            <th>Цикл</th>
                                            <th>Порядок</th>
                                            <th>Статус</th>
                                            <th>Упражнений</th>
                                            <th>Создано</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${item.name}</td>
                                                <td>${item.cycle ? item.cycle.name : '-'}</td>
                                                <td>${item.order || '-'}</td>
                                                <td><span class="badge ${item.is_active ? 'bg-success' : 'bg-secondary'}">${item.is_active ? 'Активен' : 'Неактивен'}</span></td>
                                                <td>${item.exercise_count}</td>
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
                cycle_id: document.getElementById('filterCycleId').value,
                order: document.getElementById('filterOrder').value,
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
                cycle_id: parseInt(document.getElementById('createCycleId').value),
                name: document.getElementById('createName').value,
                order: document.getElementById('createOrder').value ? parseInt(document.getElementById('createOrder').value) : null,
                is_active: document.getElementById('createIsActive').checked
            };

            try {
                const response = await fetch(`${API_BASE}/plans`, {
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
                cycle_id: parseInt(document.getElementById('updateCycleId').value),
                name: document.getElementById('updateName').value,
                order: document.getElementById('updateOrder').value ? parseInt(document.getElementById('updateOrder').value) : null,
                is_active: document.getElementById('updateIsActive').checked
            };

            try {
                const response = await fetch(`${API_BASE}/plans/${id}`, {
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
                const response = await fetch(`${API_BASE}/plans/${id}`, {
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
                            <strong>Цикл:</strong> ${data.data.cycle ? data.data.cycle.name : 'Не указан'}<br>
                            <strong>Порядок:</strong> ${data.data.order || 'Не указан'}<br>
                            <strong>Статус:</strong> ${data.data.is_active ? 'Активен' : 'Неактивен'}<br>
                            <strong>Количество упражнений:</strong> ${data.data.exercise_count}<br>
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

            if (!confirm(`Вы уверены, что хотите удалить план с ID ${id}?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/plans/${id}`, {
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
