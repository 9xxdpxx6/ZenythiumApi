<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация - Test Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">
                            <i class="fas fa-user-shield"></i>
                            Авторизация
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Navigation -->
                        <div class="mb-3">
                            <a href="/test-forms" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                        </div>

                        <!-- Token Status -->
                        <div id="tokenStatus" class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Статус:</strong> <span id="tokenText">Токен не получен</span>
                        </div>

                        <!-- Register Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-user-plus"></i> Регистрация</h5>
                            </div>
                            <div class="card-body">
                                <form id="registerForm">
                                    <div class="mb-3">
                                        <label for="regName" class="form-label">Имя</label>
                                        <input type="text" class="form-control" id="regName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="regEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="regEmail" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="regPassword" class="form-label">Пароль</label>
                                        <input type="password" class="form-control" id="regPassword" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="regPasswordConfirmation" class="form-label">Подтверждение пароля</label>
                                        <input type="password" class="form-control" id="regPasswordConfirmation" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Зарегистрироваться
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Login Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-sign-in-alt"></i> Вход</h5>
                            </div>
                            <div class="card-body">
                                <form id="loginForm">
                                    <div class="mb-3">
                                        <label for="loginEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="loginEmail" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="loginPassword" class="form-label">Пароль</label>
                                        <input type="password" class="form-control" id="loginPassword" required>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-sign-in-alt"></i> Войти
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-user"></i> Информация о пользователе</h5>
                            </div>
                            <div class="card-body">
                                <button id="getUserInfo" class="btn btn-info">
                                    <i class="fas fa-user"></i> Получить информацию
                                </button>
                                <div id="userInfo" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Logout -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-sign-out-alt"></i> Выход</h5>
                            </div>
                            <div class="card-body">
                                <button id="logoutBtn" class="btn btn-danger">
                                    <i class="fas fa-sign-out-alt"></i> Выйти
                                </button>
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
        
        // Check token on load
        document.addEventListener('DOMContentLoaded', function() {
            checkTokenStatus();
        });

        function checkTokenStatus() {
            const token = localStorage.getItem('auth_token');
            const statusDiv = document.getElementById('tokenStatus');
            const tokenText = document.getElementById('tokenText');
            
            if (token) {
                statusDiv.className = 'alert alert-success';
                tokenText.textContent = `Токен получен: ${token.substring(0, 20)}...`;
            } else {
                statusDiv.className = 'alert alert-warning';
                tokenText.textContent = 'Токен не получен';
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
        }

        // Register
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('regName').value,
                email: document.getElementById('regEmail').value,
                password: document.getElementById('regPassword').value,
                password_confirmation: document.getElementById('regPasswordConfirmation').value
            };

            try {
                const response = await fetch(`${API_BASE}/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok && data.token) {
                    localStorage.setItem('auth_token', data.token);
                    checkTokenStatus();
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Login
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                email: document.getElementById('loginEmail').value,
                password: document.getElementById('loginPassword').value
            };

            try {
                const response = await fetch(`${API_BASE}/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                console.log('Login response:', data); // Отладка
                
                if (response.ok && data.token) {
                    localStorage.setItem('auth_token', data.token);
                    checkTokenStatus();
                    console.log('Token saved:', data.token); // Отладка
                } else {
                    console.log('No token in response or response not ok'); // Отладка
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Get User Info
        document.getElementById('getUserInfo').addEventListener('click', async function() {
            const token = localStorage.getItem('auth_token');
            if (!token) {
                showResponse({error: 'Токен не найден. Сначала войдите в систему.'}, true);
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/user`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    const user = data.data || data.user || data;
                    document.getElementById('userInfo').innerHTML = `
                        <div class="alert alert-info">
                            <strong>ID:</strong> ${user.id}<br>
                            <strong>Имя:</strong> ${user.name}<br>
                            <strong>Email:</strong> ${user.email}
                        </div>
                    `;
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', async function() {
            const token = localStorage.getItem('auth_token');
            if (!token) {
                showResponse({error: 'Токен не найден.'}, true);
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                showResponse(data, !response.ok);
                
                if (response.ok) {
                    localStorage.removeItem('auth_token');
                    checkTokenStatus();
                    document.getElementById('userInfo').innerHTML = '';
                }
            } catch (error) {
                showResponse({error: error.message}, true);
            }
        });
    </script>
</body>
</html>
