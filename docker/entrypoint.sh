#!/bin/sh

set -e

echo "Waiting for MySQL..."
timeout=60
count=0
while ! nc -z mysql 3306; do
    sleep 1
    count=$((count + 1))
    if [ $count -ge $timeout ]; then
        echo "MySQL connection timeout"
        exit 1
    fi
done
echo "MySQL is ready!"

echo "Waiting for Redis..."
count=0
while ! nc -z redis 6379; do
    sleep 1
    count=$((count + 1))
    if [ $count -ge $timeout ]; then
        echo "Redis connection timeout"
        exit 1
    fi
done
echo "Redis is ready!"

# Только для app контейнера выполняем миграции и кэширование
if [ "$1" = "php-fpm" ]; then
    echo "Optimizing Laravel for production..."
    
    # Кэширование конфигурации (критично для production)
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
    
    # Миграции (только если нужно)
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "Running migrations..."
        php artisan migrate --force || echo "Migrations may have failed, continuing..."
    fi
fi

exec "$@"
