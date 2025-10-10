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
        const API_BASE = 'http://localhost:8000/api/v1';
        
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
        async function loadList() {
            try {
                const response = await fetch(`${API_BASE}/cycles`, {
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
    </script>
</body>
</html>
