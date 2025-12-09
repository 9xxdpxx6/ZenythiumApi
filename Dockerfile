# Multi-stage production Dockerfile для Laravel

# Stage 1: Builder - установка зависимостей
FROM php:8.3-fpm AS builder

# Установка системных зависимостей (только для composer)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www/html

# Копирование composer файлов
COPY composer.json composer.lock ./

# Установка production зависимостей
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Stage 2: Production - финальный образ
FROM php:8.3-fpm AS production

# Установка ВСЕХ необходимых зависимостей за один RUN (важно для кэширования)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    netcat-openbsd \
    curl \  # 🔥 ОБЯЗАТЕЛЬНО для healthcheck Coolify
    gcc \   # Для компиляции Redis
    make \  # Для компиляции Redis
    && pecl install redis && docker-php-ext-enable redis \  # Установка Redis сразу после зависимостей
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Копирование PHP конфигурации
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm-prod.ini /usr/local/etc/php/conf.d/opcache.ini

# Рабочая директория
WORKDIR /var/www/html

# Копирование зависимостей из builder stage
COPY --from=builder --chown=www-data:www-data /var/www/html/vendor /var/www/html/vendor

# Копирование кода приложения
COPY --chown=www-data:www-data . .

# Создание необходимых директорий с правильными правами
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan optimize:clear  # Очистка кэша после копирования кода

# 🔥 Healthcheck через HTTP-эндпоинт (обязательно для Coolify)
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD curl -f http://localhost/health || exit 1

# Копирование entrypoint скрипта
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]