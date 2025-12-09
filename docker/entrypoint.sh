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
    
    # Clear any existing caches first to ensure fresh start
    php artisan config:clear || true
    
    # Manually clear compiled views directory (safer than view:clear which may fail)
    rm -rf /var/www/html/storage/framework/views/* 2>/dev/null || true
    
    # Ensure resources/views directory exists with proper permissions
    mkdir -p /var/www/html/resources/views
    mkdir -p /var/www/html/storage/framework/views
    chown -R www-data:www-data /var/www/html/resources/views /var/www/html/storage/framework/views || true
    chmod -R 775 /var/www/html/resources/views /var/www/html/storage/framework/views || true
    
    # Cache configuration first
    php artisan config:cache || true
    php artisan route:cache || true
    
    # Skip view:cache - it's causing "View path not found" errors
    # This is not critical for API applications as views compile on-demand when needed
    # (emails, test forms, etc. will compile automatically on first use)
    echo "Skipping view:cache (views will compile on-demand when needed)"
    
    php artisan event:cache || true

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "Running migrations..."
        php artisan migrate --force || echo "Migrations may have failed, continuing..."
    fi
fi

exec "$@"
