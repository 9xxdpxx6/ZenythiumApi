# Multi-stage production Dockerfile для Laravel

# Stage 1: Frontend Builder - сборка фронтенда
FROM node:18-alpine AS frontend

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Builder - установка PHP зависимостей
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

# Stage 3: Production - финальный образ
FROM php:8.3-fpm AS production

# Установка системных зависимостей (только runtime)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Установка Redis расширения
RUN pecl install redis && docker-php-ext-enable redis

# Рабочая директория
WORKDIR /var/www/html

# Копирование зависимостей из builder stage
COPY --from=builder --chown=www-data:www-data /var/www/html/vendor /var/www/html/vendor

# Копирование собранного фронтенда из frontend stage
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Копирование composer из builder stage
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Копирование кода приложения
COPY --chown=www-data:www-data . .

# Запуск post-autoload-dump скриптов
RUN composer dump-autoload --optimize --no-interaction || true

# Создание необходимых директорий с правильными правами
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Expose port 9000
EXPOSE 9000

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "exit(@fsockopen('127.0.0.1', 9000) ? 0 : 1);" || exit 1

# Копирование entrypoint скрипта
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]