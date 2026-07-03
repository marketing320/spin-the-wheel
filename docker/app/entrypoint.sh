#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Wait for the database to accept connections before doing anything.
# ---------------------------------------------------------------------------
echo "⏳ Waiting for database ${DB_HOST}:${DB_PORT:-3306} ..."
until php -r '
    try {
        new PDO(
            "mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: "3306"),
            getenv("DB_USERNAME"),
            getenv("DB_PASSWORD")
        );
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    sleep 2
done
echo "✅ Database is ready."

# Ensure writable runtime directories exist (volume may start empty).
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Public symlink for uploaded images (prizes / celebration confetti).
php artisan storage:link 2>/dev/null || true

# Cache config + views. Routes are intentionally NOT cached (the app has a
# closure route from install:api, which is not serialisable).
php artisan config:cache
php artisan view:cache

# Only the web container owns schema migrations to avoid races.
if [ "${CONTAINER_ROLE:-web}" = "web" ]; then
    echo "Running database migrations ..."
    php artisan migrate --force
fi

echo "🚀 Starting: $*"
exec "$@"
