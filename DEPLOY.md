# Деплой на VPS (Production)

## Подготовка сервера

### 1. Установка Docker и Docker Compose

```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Установка Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Клонирование проекта

```bash
git clone <your-repo-url> /var/www/zenythium-api
cd /var/www/zenythium-api
```

### 3. Создание .env файла

```bash
cp .env.example .env
nano .env
```

**Важные настройки для production:**

```env
APP_NAME="Zenythium API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=zenythium
DB_USERNAME=zenythium
DB_PASSWORD=<strong-password>

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis

LOG_CHANNEL=stack
LOG_LEVEL=error

# Для миграций при первом запуске
RUN_MIGRATIONS=true
```

## Сборка и запуск Production окружения

### 1. Сборка образов

```bash
docker-compose build --no-cache
```

### 2. Первый запуск (с миграциями)

```bash
# Важно: убедитесь, что директории storage и bootstrap/cache существуют и имеют права
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Запуск с миграциями
RUN_MIGRATIONS=true docker-compose up -d

# Или после запуска:
docker-compose exec app php artisan migrate --force
```

### 3. Генерация APP_KEY (если нужно)

```bash
docker-compose exec app php artisan key:generate
```

### 4. Оптимизация Laravel

```bash
# Кэширование конфигурации (уже выполняется в entrypoint)
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan event:cache
```

## Настройка Nginx Reverse Proxy (с SSL)

### 1. Установка Nginx на хосте

```bash
sudo apt update
sudo apt install nginx certbot python3-certbot-nginx
```

### 2. Создание конфигурации Nginx

Создайте файл `/etc/nginx/sites-available/zenythium`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 3. Активация и SSL

```bash
sudo ln -s /etc/nginx/sites-available/zenythium /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Получение SSL сертификата
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## Мониторинг и логи

### Просмотр логов

```bash
# Все сервисы
docker-compose logs -f

# Конкретный сервис
docker-compose logs -f app
docker-compose logs -f nginx
```

### Проверка статуса

```bash
docker-compose ps
```

### Использование ресурсов

```bash
docker stats
```

## Обновление приложения

### 1. Обновление кода

```bash
cd /var/www/zenythium-api
git pull origin main

# Пересборка образов
docker-compose build --no-cache

# Перезапуск с миграциями (если нужно)
RUN_MIGRATIONS=true docker-compose up -d

# Очистка старых образов
docker system prune -af
```

### 2. Обновление без даунтайма (zero-downtime)

```bash
# Сборка нового образа
docker-compose build app queue scheduler

# Перезапуск контейнеров по одному
docker-compose up -d --no-deps app
docker-compose up -d --no-deps queue
docker-compose up -d --no-deps scheduler
```

## Бэкапы базы данных

### Создание бэкапа

```bash
docker-compose exec mysql mysqldump -u zenythium -p${DB_PASSWORD} zenythium > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Восстановление

```bash
docker-compose exec -T mysql mysql -u zenythium -p${DB_PASSWORD} zenythium < backup.sql
```

## Автоматический перезапуск при перезагрузке сервера

Docker Compose уже настроен с `restart: always`, но убедитесь, что Docker запускается при загрузке:

```bash
sudo systemctl enable docker
```

## Безопасность

### Firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

### Регулярные обновления

```bash
sudo apt update && sudo apt upgrade -y
docker-compose pull
```

## Troubleshooting

### Проблемы с памятью

Если контейнеры падают из-за нехватки памяти:

1. Увеличьте лимиты в `docker-compose.yml`
2. Или увеличьте swap:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### Проблемы с правами доступа

```bash
sudo chown -R $USER:$USER /var/www/zenythium-api
sudo chmod -R 775 /var/www/zenythium-api/storage
sudo chmod -R 775 /var/www/zenythium-api/bootstrap/cache
```

## Производительность

### Оптимизация MySQL

Параметры MySQL уже настроены в `docker-compose.yml`. Для высоких нагрузок можно увеличить:

- `innodb_buffer_pool_size` (увеличьте до 70% от доступной RAM)
- `max_connections` (по умолчанию 200)

### Мониторинг производительности

```bash
# Мониторинг в реальном времени
docker stats

# Логи производительности Laravel
docker-compose exec app tail -f storage/logs/laravel.log
```

