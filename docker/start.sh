#!/bin/sh
# Container entrypoint for the VRS Laravel backend.
set -e

cd /var/www/html

# ── 1. Permissions ────────────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache || true

# ── 2. Require APP_KEY ────────────────────────────────────────────────────────
if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is not set. Generate one with: php artisan key:generate"
    exit 1
fi

# ── 3. Wait for the database (max 60 s) ───────────────────────────────────────
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
WAIT=0
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME:-root}', '${DB_PASSWORD:-}');" 2>/dev/null; do
    if [ "$WAIT" -ge 60 ]; then
        echo "WARNING: database not reachable after 60 s — starting without migrations."
        break
    fi
    sleep 2
    WAIT=$((WAIT + 2))
done

# ── 4. Clear stale bootstrap caches ──────────────────────────────────────────
php artisan optimize:clear --quiet || true

# ── 5. Run migrations (non-fatal — app starts even if this fails) ─────────────
echo "Running migrations..."
php artisan migrate --force --no-interaction || echo "WARNING: migrate failed — check DB credentials / connectivity."

# ── 6. Build production caches ───────────────────────────────────────────────
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Caching config, routes and views..."
    php artisan config:cache || { echo "ERROR: config:cache failed"; exit 1; }
    php artisan route:cache  || { echo "ERROR: route:cache failed";  exit 1; }
    php artisan view:cache   || true
fi

echo "Backend ready."
exec apache2-foreground
