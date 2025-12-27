<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расшаренные циклы - Test Forms</title>
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
        .share-link-display {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
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
                            <i class="fas fa-share-alt"></i>
                            Расшаренные циклы
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Navigation -->
                        <div class="mb-3">
                            <a href="/test-forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                        </div>

                        <!-- User Info -->
                        <div class="card mb-4" id="userInfoCard" style="display: none;">
                            <div class="card-header">
                                <h5><i class="fas fa-user"></i> Информация о пользователе</h5>
                            </div>
                            <div class="card-body" id="userInfo">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Загрузка...
                                </div>
                            </div>
                        </div>

                        <!-- Generate Share Link -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-link"></i> Сгенерировать ссылку для расшаривания</h5>
                            </div>
                            <div class="card-body">
                                <form id="shareLinkForm">
                                    <div class="mb-3">
                                        <label for="shareLinkCycleId" class="form-label">ID цикла *</label>
                                        <input type="number" class="form-control" id="shareLinkCycleId" placeholder="Введите ID цикла">
                                        <small class="form-text text-muted">Цикл должен принадлежать текущему пользователю</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-link"></i> Сгенерировать ссылку
                                    </button>
                                </form>
                                <div id="shareLinkResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- View Shared Cycle -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-eye"></i> Просмотр расшаренного цикла</h5>
                            </div>
                            <div class="card-body">
                                <form id="viewSharedCycleForm">
                                    <div class="mb-3">
                                        <label for="viewShareId" class="form-label">Share ID (UUID) *</label>
                                        <input type="text" class="form-control" id="viewShareId" placeholder="550e8400-e29b-41d4-a716-446655440000">
                                        <small class="form-text text-muted">UUID ссылки для расшаривания</small>
                                    </div>
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-eye"></i> Просмотреть
                                    </button>
                                </form>
                                <div id="viewSharedCycleResult" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Import Shared Cycle -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-download"></i> Импорт расшаренного цикла</h5>
                            </div>
                            <div class="card-body">
                                <form id="importSharedCycleForm">
                                    <div class="mb-3">
                                        <label for="importShareId" class="form-label">Share ID (UUID) *</label>
                                        <input type="text" class="form-control" id="importShareId" placeholder="550e8400-e29b-41d4-a716-446655440000">
                                        <small class="form-text text-muted">UUID ссылки для импорта. Нельзя импортировать свою собственную программу.</small>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-download"></i> Импортировать
                                    </button>
                                </form>
                                <div id="importSharedCycleResult" class="mt-3"></div>
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
        
        // Load user info on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUserInfo();
        });

        function getAuthHeaders() {
            const token = localStorage.getItem('auth_token');
            return {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        }

        async function loadUserInfo() {
            const token = localStorage.getItem('auth_token');
            if (!token) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/user`, {
                    headers: getAuthHeaders()
                });

                if (response.ok) {
                    const data = await response.json();
                    const user = data.data || data.user || data;
                    
                    document.getElementById('userInfoCard').style.display = 'block';
                    document.getElementById('userInfo').innerHTML = `
                        <div class="alert alert-info">
                            <strong>ID:</strong> ${user.id}<br>
                            <strong>Имя:</strong> ${user.name || '-'}<br>
                            <strong>Email:</strong> ${user.email || '-'}
                        </div>
                        <small class="text-muted">Убедитесь, что ID цикла принадлежит этому пользователю</small>
                    `;
                }
            } catch (error) {
                console.error('Error loading user info:', error);
            }
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

        function formatStructure(structure) {
            if (!structure || !structure.cycles || structure.cycles.length === 0) {
                return '<div class="text-muted">Структура не найдена</div>';
            }

            let html = '<div class="structure-cycle">';
            structure.cycles.forEach(cycle => {
                html += `<h6><i class="fas fa-sync-alt"></i> ${cycle.name}</h6>`;
                if (cycle.plans && cycle.plans.length > 0) {
                    cycle.plans.forEach(plan => {
                        html += `<div class="structure-plan">`;
                        html += `<strong><i class="fas fa-clipboard-list"></i> ${plan.name}</strong>`;
                        if (plan.exercises && plan.exercises.length > 0) {
                            html += '<ul class="list-unstyled mt-2">';
                            plan.exercises.forEach(exercise => {
                                html += `<li class="structure-exercise">`;
                                html += `<i class="fas fa-dumbbell"></i> ${exercise.name}`;
                                if (exercise.muscle_group) {
                                    html += ` <span class="badge bg-secondary">${exercise.muscle_group.name}</span>`;
                                }
                                if (exercise.description) {
                                    html += ` <small class="text-muted">- ${exercise.description}</small>`;
                                }
                                html += `</li>`;
                            });
                            html += '</ul>';
                        }
                        html += `</div>`;
                    });
                }
            });
            html += '</div>';
            return html;
        }

        // Generate Share Link Form
        document.getElementById('shareLinkForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const cycleId = document.getElementById('shareLinkCycleId').value;
            
            if (!cycleId) {
                alert('Пожалуйста, введите ID цикла');
                return;
            }

            const token = localStorage.getItem('auth_token');
            if (!token) {
                alert('Вы не авторизованы. Пожалуйста, сначала выполните авторизацию.');
                window.location.href = '/test-forms/auth';
                return;
            }

            const resultDiv = document.getElementById('shareLinkResult');
            resultDiv.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';

            try {
                const response = await fetch(`${API_BASE}/cycles/${cycleId}/share-link`, {
                    method: 'GET',
                    headers: getAuthHeaders()
                });

                const data = await response.json();

                if (response.ok) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle"></i> Ссылка успешно сгенерирована</h6>
                            <div class="share-link-display">
                                <strong>Share ID:</strong> <code>${data.share_id}</code><br>
                                <strong>Ссылка:</strong> <code>${data.share_link}</code>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('${data.share_link}')">
                                    <i class="fas fa-copy"></i> Копировать
                                </button>
                            </div>
                        </div>
                    `;
                    showResponse(data, false);
                } else {
                    let errorMessage = data.message || 'Не удалось сгенерировать ссылку';
                    
                    if (response.status === 401) {
                        errorMessage += '<br><small>Проверьте, что вы авторизованы и токен действителен.</small>';
                    } else if (response.status === 403) {
                        errorMessage += '<br><small>Убедитесь, что цикл принадлежит вам.</small>';
                    } else if (response.status === 404) {
                        errorMessage += '<br><small>Проверьте правильность ID цикла.</small>';
                    }
                    
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Ошибка (${response.status}):</strong> ${errorMessage}
                        </div>
                    `;
                    showResponse(data, true);
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Ошибка сети:</strong> ${error.message}
                        <br><small>Проверьте подключение к серверу.</small>
                    </div>
                `;
                showResponse({error: error.message}, true);
            }
        });

        // View Shared Cycle Form
        document.getElementById('viewSharedCycleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const shareId = document.getElementById('viewShareId').value;
            const resultDiv = document.getElementById('viewSharedCycleResult');
            resultDiv.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Загрузка...</div>';

            try {
                const response = await fetch(`${API_BASE}/shared-cycles/${shareId}`, {
                    method: 'GET',
                    headers: getAuthHeaders()
                });

                const data = await response.json();

                if (response.ok) {
                    const cycleData = data.data;
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle"></i> Данные цикла</h6>
                            <div class="mt-3">
                                <p><strong>Название:</strong> ${cycleData.name || '-'}</p>
                                <p><strong>Недель:</strong> ${cycleData.weeks || '-'}</p>
                                <p><strong>Автор:</strong> ${cycleData.author ? cycleData.author.name : '-'}</p>
                                <p><strong>Планов:</strong> ${cycleData.plans_count || 0}</p>
                                <p><strong>Упражнений:</strong> ${cycleData.exercises_count || 0}</p>
                                <p><strong>Просмотров:</strong> ${cycleData.view_count || 0}</p>
                                <p><strong>Импортов:</strong> ${cycleData.import_count || 0}</p>
                                ${cycleData.structure ? `
                                    <div class="mt-3">
                                        <strong>Структура:</strong>
                                        ${formatStructure(cycleData.structure)}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                    showResponse(data, false);
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Ошибка:</strong> ${data.message || 'Не удалось загрузить данные цикла'}
                        </div>
                    `;
                    showResponse(data, true);
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Ошибка сети:</strong> ${error.message}
                    </div>
                `;
                showResponse({error: error.message}, true);
            }
        });

        // Import Shared Cycle Form
        document.getElementById('importSharedCycleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const shareId = document.getElementById('importShareId').value;
            const resultDiv = document.getElementById('importSharedCycleResult');
            resultDiv.innerHTML = '<div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Импорт...</div>';

            try {
                const response = await fetch(`${API_BASE}/shared-cycles/${shareId}/import`, {
                    method: 'POST',
                    headers: getAuthHeaders()
                });

                const data = await response.json();

                if (response.ok || response.status === 201) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle"></i> Цикл успешно импортирован</h6>
                            <div class="mt-3">
                                <p><strong>ID импортированного цикла:</strong> ${data.data.cycle_id}</p>
                                <p><strong>Планов создано:</strong> ${data.data.plans_count || 0}</p>
                                <p><strong>Упражнений создано/использовано:</strong> ${data.data.exercises_count || 0}</p>
                            </div>
                        </div>
                    `;
                    showResponse(data, false);
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Ошибка:</strong> ${data.message || 'Не удалось импортировать цикл'}
                        </div>
                    `;
                    showResponse(data, true);
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Ошибка сети:</strong> ${error.message}
                    </div>
                `;
                showResponse({error: error.message}, true);
            }
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Ссылка скопирована в буфер обмена!');
            }).catch(err => {
                console.error('Ошибка копирования:', err);
            });
        }
    </script>
</body>
</html>

