#!/bin/sh

set -e

# Ждем только если команда не указана явно (т.е. для app контейнера)
if [ "$1" = "php-fpm" ]; then
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

    if [ ! -f .env ]; then
        echo "Creating .env file from .env.example..."
        cp .env.example .env 2>/dev/null || echo "Warning: .env.example not found"
    fi

    echo "Running migrations..."
    php artisan migrate --force || echo "Migrations may have failed, continuing..."

    echo "Clearing cache..."
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan route:clear || true
    php artisan view:clear || true

    echo "Caching configuration..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"

