# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 — build the frontend assets (Vite + Tailwind).
# No .env is present here (see .dockerignore), so VITE_* fall back to their
# defaults — notably VITE_REVERB_ENABLED is off, i.e. polling mode.
# ---------------------------------------------------------------------------
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2 — application runtime: FrankenPHP (embedded Caddy w/ auto-HTTPS).
# The same image runs the web server and the queue worker.
#
# PHP 8.4 is required: composer.lock pins Symfony 8.1 (needs php >= 8.4.1).
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:php8.4 AS app

# PHP extensions Laravel + this app need.
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    bcmath \
    gd \
    zip \
    exif \
    pcntl \
    intl \
    opcache

# Composer binary.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first (better layer caching). Scripts/autoloader are
# deferred until the full source is present.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Application source + compiled assets.
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Container config.
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# HTTP only by default; docker-compose sets SERVER_NAME to your domain to
# switch FrankenPHP/Caddy into automatic HTTPS mode.
ENV SERVER_NAME=":80"
ENV CONTAINER_ROLE="web"

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
