# Docker Setup для Zenythium API

## Предварительные требования

- Docker Desktop (или Docker + Docker Compose)
- Git

## Настройка проекта

### 1. Создайте файл `.env`

Создайте файл `.env` в корне проекта и настройте под Docker:

```env
APP_NAME="Zenythium API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Europe/Moscow

FRONTEND_URL=http://localhost:3000

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=zenythium
DB_USERNAME=zenythium
DB_PASSWORD=password

# Redis Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Cache & Queue
CACHE_STORE=database
QUEUE_CONNECTION=database

# Session
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Mail Configuration
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@zenythium.local"
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:5173,localhost:8080,127.0.0.1:3000
SANCTUM_TOKEN_PREFIX=

# CORS Configuration
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://localhost:8080
CORS_ALLOWED_PATTERNS=
CORS_MAX_AGE=86400
CORS_SUPPORTS_CREDENTIALS=true

# Firebase Cloud Messaging
FCM_USE_V1_API=true
FCM_PROJECT_ID=your_firebase_project_id
FCM_SERVICE_ACCOUNT_PATH=/var/www/html/storage/app/private/zenythium-firebase-adminsdk-fbsvc-5d64f5f90f.json
```

### 2. Важные параметры для настройки в `.env`:

#### Обязательно заполните:
- `APP_KEY` - сгенерируйте ключ командой: `php artisan key:generate` (после первого запуска)
- `DB_DATABASE` - имя базы данных (по умолчанию: `zenythium`)
- `DB_USERNAME` - имя пользователя БД (по умолчанию: `zenythium`). **ВАЖНО:** Не используйте `root` - MySQL контейнер не может создать пользователя с таким именем
- `DB_PASSWORD` - пароль пользователя БД (по умолчанию: `password`). Этот же пароль будет использоваться для root пользователя MySQL
- `FCM_PROJECT_ID` - ваш Firebase Project ID
- `FCM_SERVICE_ACCOUNT_PATH` - путь к файлу Firebase Service Account JSON

**Примечание:** Если вы используете `DB_USERNAME=root` в `.env`, замените на другое имя (например, `zenythium` или `app_user`). MySQL контейнер создает отдельного пользователя, и `root` - зарезервированное имя.

#### Для продакшена измените:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_LEVEL=error`

## Запуск проекта

### Первый запуск:

```bash
# Сборка образов
docker-compose build

# Запуск контейнеров
docker-compose up -d

# Генерация ключа приложения
docker-compose exec app php artisan key:generate

# Запуск миграций (если entrypoint не выполнил автоматически)
docker-compose exec app php artisan migrate

# Заполнение базы данных (опционально)
docker-compose exec app php artisan db:seed
```

### Обычный запуск:

```bash
# Запуск всех сервисов
docker-compose up -d

# Просмотр логов
docker-compose logs -f

# Остановка
docker-compose down

# Остановка с удалением volumes (ОСТОРОЖНО: удалит БД!)
docker-compose down -v
```

## Доступ к сервисам

- **API**: http://localhost:8000
- **MySQL**: localhost:3307 (порт изменен на 3307, чтобы не конфликтовать с локальным MySQL)
  - Database: `zenythium` (или значение из `DB_DATABASE`)
  - Username: `zenythium` (или значение из `DB_USERNAME`)
  - Password: `password` (или значение из `DB_PASSWORD`)
- **Redis**: localhost:6379

**Примечание:** Если порт 3307 тоже занят, измените его в `docker-compose.yml` в секции `mysql.ports`.

## Полезные команды

```bash
# Выполнение Artisan команд
docker-compose exec app php artisan [command]

# Вход в контейнер приложения
docker-compose exec app bash

# Просмотр логов конкретного сервиса
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql
docker-compose logs -f queue
docker-compose logs -f scheduler

# Перезапуск сервиса
docker-compose restart app

# Пересборка после изменений в Dockerfile
docker-compose build --no-cache app
docker-compose up -d
```

## Структура сервисов

- **app** - PHP-FPM контейнер с Laravel приложением
- **nginx** - Nginx веб-сервер
- **mysql** - MySQL 8.0 база данных
- **redis** - Redis для кэширования и очередей
- **queue** - Worker для обработки очередей
- **scheduler** - Запуск Laravel Scheduler (cron tasks)

## Решение проблем

### Проблема с правами доступа к storage

```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Очистка кэша

```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Сброс базы данных

```bash
# Остановка и удаление volumes
docker-compose down -v

# Запуск заново
docker-compose up -d

# Миграции
docker-compose exec app php artisan migrate
```

### Проверка подключения к БД

```bash
docker-compose exec app php artisan tinker
# Затем в tinker:
DB::connection()->getPdo();
```

## Firebase Service Account

Убедитесь, что файл Firebase Service Account JSON находится по пути:
```
storage/app/private/zenythium-firebase-adminsdk-fbsvc-5d64f5f90f.json
```

Этот файл монтируется как read-only для безопасности.

## Решение проблем с сетью

### Проблема: не удается загрузить образы из Docker Hub (EOF ошибка)

Если при сборке возникает ошибка `failed to resolve reference` или `EOF`, это проблема с подключением к Docker Hub.

#### Решение 1: Настройка DNS в Docker Desktop

1. Откройте **Docker Desktop**
2. Перейдите в **Settings → Resources → Network**
3. В поле **DNS servers** укажите: `8.8.8.8, 8.8.4.4` (Google DNS)
4. Нажмите **Apply & Restart**

#### Решение 2: Использование зеркал Docker Hub (для РФ/СНГ)

Если вы находитесь в регионе с ограниченным доступом:

1. Создайте/отредактируйте файл: `%USERPROFILE%\.docker\daemon.json` (Windows)
2. Добавьте зеркала:

```json
{
  "registry-mirrors": [
    "https://dockerhub.timeweb.cloud"
  ]
}
```

3. Перезапустите Docker Desktop

#### Решение 3: Ручная загрузка образов

Попробуйте загрузить образы по одному с повторными попытками:

```powershell
# Загрузите базовые образы
docker pull php:8.3-fpm
docker pull composer:2
docker pull mysql:8.0
docker pull redis:7-alpine
docker pull nginx:alpine

# После успешной загрузки всех образов
docker-compose build
```

#### Решение 4: Проверка прокси/VPN

Если используете VPN или корпоративный прокси:

1. **Docker Desktop → Settings → Resources → Proxies**
2. Включите **Manual proxy configuration**
3. Укажите адрес и порт прокси-сервера

#### Решение 5: Временное отключение файрвола

Попробуйте временно отключить Windows Firewall/антивирус для проверки соединения.

## Production рекомендации

1. Измените все пароли в `.env`
2. Установите `APP_DEBUG=false`
3. Используйте `.env.production` с правильными настройками
4. Настройте SSL/TLS для Nginx
5. Используйте секреты Docker для чувствительных данных
6. Настройте логирование и мониторинг
7. Используйте внешнюю БД вместо контейнера MySQL для production

