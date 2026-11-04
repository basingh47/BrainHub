# ─────────────────────────────────────────────────────────────
# Production image — NGINX + PHP-FPM (serversideup/php, supervised by S6).
# Replaces `php artisan serve` (single-threaded dev server).
# See docs/deployment/render-and-local.md.
# ─────────────────────────────────────────────────────────────

# Stage 1: PHP dependencies (also used so Tailwind can scan pagination views)
FROM serversideup/php:8.4-cli AS vendor

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

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
    /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build

# ─────────────────────────────────────────────────────────────
# Stage 3: production image (Render runs this)
# NGINX :8080 → PHP-FPM → Laravel. Runs as www-data (non-root).
# ─────────────────────────────────────────────────────────────
FROM serversideup/php:8.4-fpm-nginx

# Image defaults — Render / Compose environment variables override these.
ENV PHP_OPCACHE_ENABLE=1 \
    # Render free = 512 MB RAM; ~40 MB per PHP worker
    PHP_FPM_PM_MAX_CHILDREN=6 \
    # On start: migrate --force, storage:link, optimize (config/route/view/event cache)
    AUTORUN_ENABLED=true \
    # Docker HEALTHCHECK hits Laravel's health route (boots the full app)
    HEALTHCHECK_PATH=/up \
    LOG_CHANNEL=stderr

COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor

COPY --chown=www-data:www-data . .

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# Required for Breeze @vite — without this, auth pages return HTTP 500
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
