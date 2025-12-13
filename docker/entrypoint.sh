#!/bin/sh

set -e

DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PORT=${REDIS_PORT:-6379}

echo "Waiting for MySQL at $DB_HOST:$DB_PORT..."
timeout=60
count=0
while ! nc -z "$DB_HOST" "$DB_PORT"; do
    sleep 1
    count=$((count + 1))
    if [ $count -ge $timeout ]; then
        echo "MySQL connection timeout"
        exit 1
    fi
done
echo "MySQL is ready!"


echo "Waiting for Redis at $REDIS_HOST:$REDIS_PORT..."
count=0
while ! nc -z "$REDIS_HOST" "$REDIS_PORT"; do
    sleep 1
    count=$((count + 1))
    if [ $count -ge $timeout ]; then
        echo "Redis connection timeout"
        exit 1
    fi
done
echo "Redis is ready!"


# Laravel оптимизация
if [ "$APP_ENV" = "production" ] && [ "$CONTAINER_ROLE" = "app" ]; then
    echo "Optimizing Laravel (production, app container)..."
    
    php artisan optimize:clear
    
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "Running migrations..."
        php artisan migrate --force || echo "Migrations may have failed, continuing..."
    fi
fi

exec "$@"
