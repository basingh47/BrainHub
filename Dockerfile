# ─────────────────────────────────────────────────────────────
# Stage 1: PHP dependencies.
# Split out so the frontend build can reach Laravel's pagination
# Blade views, which tailwind.config.js scans for classes.
# ─────────────────────────────────────────────────────────────
FROM php:8.4-cli AS vendor

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Only the manifests, so this layer is reused until dependencies change.
# Autoloader is generated later, once application code is present.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ─────────────────────────────────────────────────────────────
# Stage 2: compile frontend assets.
# Node is needed only to BUILD css/js. This stage is discarded —
# the shipped image has no Node and no node_modules.
# ─────────────────────────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

# Sources + the three configs Vite and Tailwind read
COPY resources/ resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./

# tailwind.config.js scans this path for pagination classes. Without it
# the pagination component ships unstyled.
COPY --from=vendor \
    /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build

# ─────────────────────────────────────────────────────────────
# Stage 3: the image that actually runs on Render
# ─────────────────────────────────────────────────────────────
FROM php:8.4-cli

# Identical to stage 1's RUN, so Docker reuses that cached layer.
# pdo_mysql reaches the DB; opcache stops PHP recompiling every request.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# vendor/ is in .dockerignore, so the COPY below cannot clobber this
COPY --from=vendor /app/vendor ./vendor

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# Compiled assets. Without this, @vite throws "Vite manifest not found"
# and every Breeze page returns HTTP 500.
COPY --from=assets /app/public/build ./public/build

# storage/framework/* is gitignored, so these arrive missing
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 storage bootstrap/cache

# Fork 4 request handlers instead of 1 (see CMD note)
ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 10000

# Caching and migrations run at STARTUP, not build time: .env is
# dockerignored and Render's env vars do not exist during `docker build`,
# so caching config earlier would bake in empty credentials.
#
# NOTE: `php artisan serve` is a development server, kept deliberately
# for now — moving to php:8.4-apache or FrankenPHP is a separate change.
# PHP_CLI_SERVER_WORKERS above softens the single-request bottleneck.
CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan migrate --force \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
