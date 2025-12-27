<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Циклы - Test Forms</title>
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
                            <i class="fas fa-sync-alt"></i>
                            Циклы - CRUD
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
                                <h5><i class="fas fa-plus"></i> Создать цикл</h5>
                            </div>
                            <div class="card-body">
                                <form id="createForm">
                                    <div class="mb-3">
                                        <label for="createName" class="form-label">Название *</label>
                                        <input type="text" class="form-control" id="createName">
                                    </div>
                                    <div class="mb-3">
                                        <label for="createStartDate" class="form-label">Дата начала</label>
                                        <input type="date" class="form-control" id="createStartDate">
                                    </div>
                                    <div class="mb-3">
                                        <label for="createEndDate" class="form-label">Дата окончания</label>
                                        <input type="date" class="form-control" id="createEndDate">
                                    </div>
                                    <div class="mb-3">
                                        <label for="createWeeks" class="form-label">Количество недель *</label>
                                        <input type="number" class="form-control" id="createWeeks" min="1" max="52">
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
                                            <input type="text" class="form-control" id="filterSearch" placeholder="Введите название цикла">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterUserId" class="form-label">ID пользователя</label>
                                            <input type="number" class="form-control" id="filterUserId" placeholder="ID пользователя">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterStartDateFrom" class="form-label">Дата начала от</label>
                                            <input type="date" class="form-control" id="filterStartDateFrom">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterStartDateTo" class="form-label">Дата начала до</label>
                                            <input type="date" class="form-control" id="filterStartDateTo">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterEndDateFrom" class="form-label">Дата окончания от</label>
                                            <input type="date" class="form-control" id="filterEndDateFrom">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterEndDateTo" class="form-label">Дата окончания до</label>
                                            <input type="date" class="form-control" id="filterEndDateTo">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="filterWeeksMin" class="form-label">Минимум недель</label>
                                            <input type="number" class="form-control" id="filterWeeksMin" min="1" max="52">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="filterWeeksMax" class="form-label">Максимум недель</label>
                                            <input type="number" class="form-control" id="filterWeeksMax" min="1" max="52">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="filterWeeks" class="form-label">Точно недель</label>
                                            <input type="number" class="form-control" id="filterWeeks" min="1" max="52">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterSortBy" class="form-label">Сортировка по</label>
                                            <select class="form-control" id="filterSortBy">
                                                <option value="start_date">Дата начала</option>
                                                <option value="end_date">Дата окончания</option>
                                                <option value="name">Название</option>
                                                <option value="weeks">Количество недель</option>
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
                                <h5><i class="fas fa-list"></i> Список циклов</h5>
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
                                <h5><i class="fas fa-edit"></i> Обновить цикл</h5>
                            </div>
                            <div class="card-body">
                                <form id="updateForm">
                                    <div class="mb-3">
                                        <label for="updateId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="updateId">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateName" class="form-label">Название *</label>
                                        <input type="text" class="form-control" id="updateName">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateStartDate" class="form-label">Дата начала</label>
                                        <input type="date" class="form-control" id="updateStartDate">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateEndDate" class="form-label">Дата окончания</label>
                                        <input type="date" class="form-control" id="updateEndDate">
                                    </div>
                                    <div class="mb-3">
                                        <label for="updateWeeks" class="form-label">Количество недель *</label>
                                        <input type="number" class="form-control" id="updateWeeks" min="1" max="52">
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
                                <h5><i class="fas fa-eye"></i> Показать цикл</h5>
                            </div>
                            <div class="card-body">
                                <form id="showForm">
                                    <div class="mb-3">
                                        <label for="showId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="showId">
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
                                <h5><i class="fas fa-trash"></i> Удалить цикл</h5>
                            </div>
                            <div class="card-body">
                                <form id="deleteForm">
                                    <div class="mb-3">
                                        <label for="deleteId" class="form-label">ID *</label>
                                        <input type="number" class="form-control" id="deleteId">
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
        
        // Load list on page load
        document.addEventListener('DOMContentLoaded', function() {
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
                
                const url = `${API_BASE}/cycles${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
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
                                            <th>Дата начала</th>
                                            <th>Дата окончания</th>
                                            <th>Недель</th>
                                            <th>Прогресс</th>
                                            <th>Завершено тренировок</th>
                                            <th>Создано</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td>${item.name}</td>
                                                <td>${item.start_date ? new Date(item.start_date).toLocaleDateString('ru-RU') : '-'}</td>
                                                <td>${item.end_date ? new Date(item.end_date).toLocaleDateString('ru-RU') : '-'}</td>
                                                <td>${item.weeks}</td>
                                                <td>${item.progress_percentage}%</td>
                                                <td>${item.completed_workouts_count}</td>
                                                <td>${new Date(item.created_at).toLocaleString('ru-RU')}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="generateShareLink(${item.id})" title="Сгенерировать ссылку для расшаривания">
                                                        <i class="fas fa-share-alt"></i>
                                                    </button>
                                                </td>
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
                start_date_from: document.getElementById('filterStartDateFrom').value,
                start_date_to: document.getElementById('filterStartDateTo').value,
                end_date_from: document.getElementById('filterEndDateFrom').value,
                end_date_to: document.getElementById('filterEndDateTo').value,
                weeks_min: document.getElementById('filterWeeksMin').value,
                weeks_max: document.getElementById('filterWeeksMax').value,
                weeks: document.getElementById('filterWeeks').value,
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
                start_date: document.getElementById('createStartDate').value || null,
                end_date: document.getElementById('createEndDate').value || null,
                weeks: parseInt(document.getElementById('createWeeks').value)
            };

            try {
                const response = await fetch(`${API_BASE}/cycles`, {
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
            const formData = {
                name: document.getElementById('updateName').value,
                start_date: document.getElementById('updateStartDate').value || null,
                end_date: document.getElementById('updateEndDate').value || null,
                weeks: parseInt(document.getElementById('updateWeeks').value)
            };

            try {
                const response = await fetch(`${API_BASE}/cycles/${id}`, {
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
                const response = await fetch(`${API_BASE}/cycles/${id}`, {
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
                            <strong>Дата начала:</strong> ${data.data.start_date ? new Date(data.data.start_date).toLocaleDateString('ru-RU') : 'Не указана'}<br>
                            <strong>Дата окончания:</strong> ${data.data.end_date ? new Date(data.data.end_date).toLocaleDateString('ru-RU') : 'Не указана'}<br>
                            <strong>Количество недель:</strong> ${data.data.weeks}<br>
                            <strong>Прогресс:</strong> ${data.data.progress_percentage}%<br>
                            <strong>Завершено тренировок:</strong> ${data.data.completed_workouts_count}<br>
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

            if (!confirm(`Вы уверены, что хотите удалить цикл с ID ${id}? Это также удалит все связанные планы и тренировки.`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/cycles/${id}`, {
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

        // Generate Share Link
        async function generateShareLink(cycleId) {
            const token = localStorage.getItem('auth_token');
            if (!token) {
                alert('Вы не авторизованы. Пожалуйста, сначала выполните авторизацию.');
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/cycles/${cycleId}/share-link`, {
                    method: 'GET',
                    headers: getAuthHeaders()
                });

                const data = await response.json();

                if (response.ok) {
                    const shareLink = data.share_link;
                    const shareId = data.share_id;
                    
                    const message = `Ссылка для расшаривания:\n\nShare ID: ${shareId}\nСсылка: ${shareLink}\n\nСкопировать ссылку в буфер обмена?`;
                    
                    if (confirm(message)) {
                        await navigator.clipboard.writeText(shareLink);
                        alert('Ссылка скопирована в буфер обмена!');
                    }
                    
                    showResponse(data, false);
                } else {
                    let errorMessage = data.message || 'Не удалось сгенерировать ссылку';
                    
                    if (response.status === 401) {
                        errorMessage = 'Вы не авторизованы. Пожалуйста, выполните авторизацию.';
                    } else if (response.status === 403) {
                        errorMessage = 'Цикл не найден или вы не имеете прав на его расшаривание. Убедитесь, что цикл принадлежит вам.';
                    } else if (response.status === 404) {
                        errorMessage = 'Цикл не найден. Проверьте правильность ID.';
                    }
                    
                    alert(`Ошибка (${response.status}): ${errorMessage}`);
                    showResponse(data, true);
                }
            } catch (error) {
                alert(`Ошибка сети: ${error.message}`);
                showResponse({error: error.message}, true);
            }
        }
    </script>
</body>
</html>
