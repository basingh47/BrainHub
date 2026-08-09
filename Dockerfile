# ─────────────────────────────────────────────────────────────
# Docker v2 — Breeze needs compiled Vite assets in the image.
# See docs/Docker/Docker-v2/ for why v1 was not enough.
# ─────────────────────────────────────────────────────────────

# Stage 1: PHP dependencies (also used so Tailwind can scan pagination views)
FROM php:8.4-cli AS vendor

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ─────────────────────────────────────────────────────────────
# Stage 2: build frontend (Node is build-time only; not in final image)
# ─────────────────────────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources/ resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./

# tailwind.config.js scans Laravel pagination views under vendor/
COPY --from=vendor \
    /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build

# ─────────────────────────────────────────────────────────────
# Stage 3: production image (Render runs this)
# ─────────────────────────────────────────────────────────────
FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# Required for Breeze @vite — without this, auth pages return HTTP 500
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
