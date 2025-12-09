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
if [ "$1" = "php-fpm" ] || [ "$1" = "php" ] || echo "$@" | grep -q "artisan serve"; then
    echo "Optimizing Laravel for production..."
    
    # Ensure resources/views directory exists
    mkdir -p /var/www/html/resources/views
    chown -R www-data:www-data /var/www/html/resources/views || true
    
    php artisan config:cache || true
    php artisan route:cache || true
    
    # Only cache views if the views directory exists and has content
    if [ -d "/var/www/html/resources/views" ] && [ "$(ls -A /var/www/html/resources/views 2>/dev/null)" ]; then
        php artisan view:cache || true
    else
        echo "Skipping view:cache - views directory not found or empty"
    fi
    
    php artisan event:cache || true

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "Running migrations..."
        php artisan migrate --force || echo "Migrations may have failed, continuing..."
    fi
fi

exec "$@"
