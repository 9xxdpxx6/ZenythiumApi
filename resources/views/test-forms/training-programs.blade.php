<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Программы тренировок - Test Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .structure-item {
            margin-left: 20px;
            padding: 10px;
            border-left: 2px solid #dee2e6;
        }
        .structure-cycle {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .structure-plan {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #ffffff;
            border-radius: 3px;
        }
        .structure-exercise {
            margin-left: 15px;
            padding: 5px;
            font-size: 0.9em;
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
                            <i class="fas fa-dumbbell"></i>
                            Программы тренировок - Каталог
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Navigation -->
                        <div class="mb-3">
                            <a href="/test-forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
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
                                            <label for="filterSearch" class="form-label">Поиск по названию и описанию</label>
                                            <input type="text" class="form-control" id="filterSearch" placeholder="Введите название или описание программы">
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
                                            <label for="filterPerPage" class="form-label">Элементов на странице</label>
                                            <select class="form-control" id="filterPerPage">
                                                <option value="15">15</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="filterPage" class="form-label">Страница</label>
                                            <input type="number" class="form-control" id="filterPage" min="1" placeholder="Номер страницы">
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
                                <h5><i class="fas fa-list"></i> Список программ</h5>
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

                        <!-- Show Detail -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-eye"></i> Детальная информация о программе</h5>
                            </div>
                            <div class="card-body">
                                <form id="showForm">
                                    <div class="mb-3">
                                        <label for="showId" class="form-label">ID программы *</label>
                                        <input type="number" class="form-control" id="showId" required>
                                    </div>
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-eye"></i> Показать
                                    </button>
                                </form>
                                <div id="showResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Install/Uninstall -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-download"></i> Установка / Удаление программы</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-download text-success"></i> Установить программу</h6>
                                        <form id="installForm">
                                            <div class="mb-3">
                                                <label for="installId" class="form-label">ID программы *</label>
                                                <input type="number" class="form-control" id="installId" required>
                                            </div>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-download"></i> Установить
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-trash text-danger"></i> Удалить установку</h6>
                                        <form id="uninstallForm">
                                            <div class="mb-3">
                                                <label for="uninstallId" class="form-label">ID установки *</label>
                                                <input type="number" class="form-control" id="uninstallId" required>
                                                <small class="form-text text-muted">ID установки можно найти в ответе после установки</small>
                                            </div>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Удалить установку
                                            </button>
                                        </form>
                                    </div>
                                </div>
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
        
        // Load data on page load
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
            responseDiv.scrollIntoView({ behavior: 'smooth' });
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
                
                const url = `${API_BASE}/training-programs${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
                
                const listContainer = document.getElementById('listContainer');
                listContainer.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';
                
                const response = await fetch(url, {
                    headers: getAuthHeaders()
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Неизвестная ошибка' }));
                    const listContainer = document.getElementById('listContainer');
                    listContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Ошибка загрузки:</strong> ${errorData.message || errorData.detail || 'Не удалось загрузить список программ'}
                            ${errorData.detail ? `<br><small>${errorData.detail}</small>` : ''}
                        </div>
                    `;
                    showResponse(errorData, true);
                    console.error('API Error:', errorData);
                    return;
                }

                const data = await response.json();
                
                console.log('API Response:', data); // Debug
                
                if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                        const paginationInfo = data.meta ? `
                            <div class="mb-3">
                                <small class="text-muted">
                                    Показано ${data.meta.from || 0}-${data.meta.to || 0} из ${data.meta.total || 0} записей 
                                    (страница ${data.meta.current_page || 1} из ${data.meta.last_page || 1})
                                </small>
                            </div>
                        ` : '';
                        
                        listContainer.innerHTML = paginationInfo + `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Название</th>
                                            <th>Автор</th>
                                            <th>Длительность (недели)</th>
                                            <th>Циклы</th>
                                            <th>Планы</th>
                                            <th>Упражнения</th>
                                            <th>Статус</th>
                                            <th>Установлена</th>
                                            <th>Создано</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(item => `
                                            <tr>
                                                <td>${item.id}</td>
                                                <td><strong>${item.name}</strong></td>
                                                <td>${item.author ? item.author.name : '<span class="text-muted">Не указан</span>'}</td>
                                                <td>${item.duration_weeks || '-'}</td>
                                                <td><span class="badge bg-info">${item.cycles_count || 0}</span></td>
                                                <td><span class="badge bg-primary">${item.plans_count || 0}</span></td>
                                                <td><span class="badge bg-success">${item.exercises_count || 0}</span></td>
                                                <td><span class="badge ${item.is_active ? 'bg-success' : 'bg-secondary'}">${item.is_active ? 'Активна' : 'Неактивна'}</span></td>
                                                <td>
                                                    ${item.is_installed 
                                                        ? '<span class="badge bg-primary"><i class="fas fa-check"></i> Да</span>' 
                                                        : '<span class="badge bg-secondary"><i class="fas fa-times"></i> Нет</span>'}
                                                </td>
                                                <td>${new Date(item.created_at).toLocaleString('ru-RU')}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="showProgramDetails(${item.id})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    ${!item.is_installed 
                                                        ? `<button class="btn btn-sm btn-success" onclick="installProgram(${item.id})">
                                                            <i class="fas fa-download"></i>
                                                           </button>`
                                                        : `<button class="btn btn-sm btn-warning" onclick="findInstallId(${item.id})">
                                                            <i class="fas fa-info-circle"></i> ID
                                                           </button>`}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        listContainer.innerHTML = '<div class="text-center text-muted">Нет данных для отображения</div>';
                        console.log('No data in response:', data); // Debug
                    }
            } catch (error) {
                const listContainer = document.getElementById('listContainer');
                listContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Ошибка сети:</strong> ${error.message}
                        <br><small>Проверьте, что сервер запущен и доступен по адресу ${API_BASE}</small>
                    </div>
                `;
                showResponse({error: error.message, type: 'network_error'}, true);
                console.error('Fetch Error:', error); // Debug
            }
        }

        // Refresh List
        document.getElementById('refreshList').addEventListener('click', loadList);

        // Filter Form
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const filters = {
                search: document.getElementById('filterSearch').value,
                is_active: document.getElementById('filterIsActive').value,
                per_page: document.getElementById('filterPerPage').value,
                page: document.getElementById('filterPage').value
            };
            
            loadList(filters);
        });

        // Clear Filters
        function clearFilters() {
            document.getElementById('filterForm').reset();
            loadList();
        }

        // Show Program Details
        async function showProgramDetails(id) {
            document.getElementById('showId').value = id;
            
            try {
                const response = await fetch(`${API_BASE}/training-programs/${id}`, {
                    headers: getAuthHeaders()
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Неизвестная ошибка' }));
                    showResponse(errorData, true);
                    document.getElementById('showResult').innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Ошибка:</strong> ${errorData.message || errorData.detail || 'Не удалось загрузить программу'}
                            ${errorData.detail ? `<br><small>${errorData.detail}</small>` : ''}
                        </div>
                    `;
                    return;
                }

                const data = await response.json();
                showResponse(data, false);
                
                if (data.data) {
                    const program = data.data;
                    const showResult = document.getElementById('showResult');
                    
                    let structureHtml = '';
                    // Структура может быть напрямую в program.structure или program.structure.cycles
                    const cycles = program.structure?.cycles || (program.structure && Array.isArray(program.structure) ? program.structure : []);
                    
                    if (cycles && cycles.length > 0) {
                        structureHtml = cycles.map(cycle => `
                            <div class="structure-cycle">
                                <h6><i class="fas fa-sync-alt"></i> ${cycle.name || 'Цикл без названия'}</h6>
                                ${cycle.plans && cycle.plans.length > 0 ? cycle.plans.map(plan => `
                                    <div class="structure-plan">
                                        <strong><i class="fas fa-clipboard-list"></i> ${plan.name || 'План без названия'}</strong>
                                        ${plan.exercises && plan.exercises.length > 0 ? `
                                            <div class="mt-2">
                                                ${plan.exercises.map(exercise => `
                                                    <div class="structure-exercise">
                                                        <i class="fas fa-dumbbell"></i> ${exercise.name || 'Упражнение без названия'}
                                                        ${exercise.muscle_group_id ? ` <span class="badge bg-secondary">Группа: ${exercise.muscle_group_id}</span>` : ''}
                                                        ${exercise.description ? ` <small class="text-muted">- ${exercise.description}</small>` : ''}
                                                    </div>
                                                `).join('')}
                                            </div>
                                        ` : '<div class="structure-exercise text-muted">Нет упражнений</div>'}
                                    </div>
                                `).join('') : '<div class="structure-plan text-muted">Нет планов</div>'}
                            </div>
                        `).join('');
                    } else {
                        structureHtml = '<div class="text-muted">Структура не доступна или программа не содержит циклов</div>';
                    }
                    
                    showResult.innerHTML = `
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">${program.name}</h5>
                                ${program.description ? `<p class="card-text">${program.description}</p>` : ''}
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>ID:</strong> ${program.id}</p>
                                        <p><strong>Автор:</strong> ${program.author ? program.author.name : 'Не указан'}</p>
                                        <p><strong>Длительность:</strong> ${program.duration_weeks || '-'} недель</p>
                                        <p><strong>Статус:</strong> <span class="badge ${program.is_active ? 'bg-success' : 'bg-secondary'}">${program.is_active ? 'Активна' : 'Неактивна'}</span></p>
                                        <p><strong>Установлена:</strong> ${program.is_installed ? '<span class="badge bg-primary">Да</span>' : '<span class="badge bg-secondary">Нет</span>'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Циклов:</strong> <span class="badge bg-info">${program.cycles_count || 0}</span></p>
                                        <p><strong>Планов:</strong> <span class="badge bg-primary">${program.plans_count || 0}</span></p>
                                        <p><strong>Упражнений:</strong> <span class="badge bg-success">${program.exercises_count || 0}</span></p>
                                        <p><strong>Создано:</strong> ${new Date(program.created_at).toLocaleString('ru-RU')}</p>
                                        <p><strong>Обновлено:</strong> ${new Date(program.updated_at).toLocaleString('ru-RU')}</p>
                                    </div>
                                </div>
                                ${structureHtml ? `
                                    <hr>
                                    <h6>Структура программы:</h6>
                                    ${structureHtml}
                                ` : ''}
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        }

        // Show Form
        document.getElementById('showForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('showId').value;
            showProgramDetails(id);
        });

        // Install Program
        async function installProgram(id) {
            if (!confirm(`Вы уверены, что хотите установить программу с ID ${id}?`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/training-programs/${id}/install`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Неизвестная ошибка' }));
                    showResponse(errorData, true);
                    alert(`Ошибка установки программы: ${errorData.message || errorData.detail || 'Неизвестная ошибка'}`);
                    return;
                }

                const data = await response.json();
                showResponse(data, false);
                
                loadList();
                if (data.data && data.data.install_id) {
                    alert(`Программа успешно установлена!\nID установки: ${data.data.install_id}\nID цикла: ${data.data.cycle_id}\nСоздано планов: ${data.data.plans_count}\nСоздано упражнений: ${data.data.exercises_count}`);
                }
            } catch (error) {
                showResponse({error: error.message, type: 'network_error'}, true);
                alert(`Ошибка сети: ${error.message}\nПроверьте, что сервер запущен и доступен`);
            }
        }

        // Install Form
        document.getElementById('installForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('installId').value;
            installProgram(id);
        });

        // Find Install ID (helper function to show install ID)
        async function findInstallId(programId) {
            try {
                const response = await fetch(`${API_BASE}/training-programs/${programId}`, {
                    headers: getAuthHeaders()
                });

                const data = await response.json();
                
                if (response.ok && data.data) {
                    // Try to find installation ID from user's installations
                    // Note: This is a workaround since we don't have a direct endpoint for this
                    alert(`Программа установлена.\nИспользуйте API для получения ID установки или проверьте ответ после установки.`);
                }
            } catch (error) {
                console.error('Error finding install ID:', error);
            }
        }

        // Uninstall
        document.getElementById('uninstallForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('uninstallId').value;

            if (!confirm(`Вы уверены, что хотите удалить установку программы с ID ${id}? Это удалит только элементы, созданные при установке.`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/training-programs/${id}/uninstall`, {
                    method: 'DELETE',
                    headers: getAuthHeaders()
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Неизвестная ошибка' }));
                    showResponse(errorData, true);
                    alert(`Ошибка удаления установки: ${errorData.message || errorData.detail || 'Неизвестная ошибка'}`);
                    return;
                }

                const data = await response.json();
                showResponse(data, false);
                
                document.getElementById('uninstallForm').reset();
                loadList();
                alert('Установка программы успешно удалена');
            } catch (error) {
                showResponse({error: error.message, type: 'network_error'}, true);
                alert(`Ошибка сети: ${error.message}\nПроверьте, что сервер запущен и доступен`);
            }
        });
    </script>
</body>
</html>

