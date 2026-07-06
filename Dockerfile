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
# Stage 2 — application runtime: nginx + php-fpm, supervised in one image.
# The same image runs the web server (nginx + php-fpm) and the queue worker
# (php-fpm/nginx simply aren't started for the queue role — see entrypoint).
#
# PHP 8.4 is required: composer.lock pins Symfony 8.1 (needs php >= 8.4.1).
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS app

# PHP extensions Laravel + this app need, via mlocati's installer.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        exif \
        pcntl \
        intl \
        opcache

# nginx (web server) + supervisor (runs nginx and php-fpm as one process tree).
RUN apk add --no-cache nginx supervisor

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

# Container config. The nginx server block replaces Alpine's default site so
# it is picked up regardless of the stock nginx.conf's include glob.
COPY docker/nginx/app.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV CONTAINER_ROLE="web"
EXPOSE 8005

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]
