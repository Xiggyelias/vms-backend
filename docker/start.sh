#!/bin/sh
# Container entrypoint for the VRS Laravel backend.
# Runs on every container start (fresh image OR restart).
set -e

cd /var/www/html

# ── 1. Permissions ────────────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache || true

# ── 2. Validate required env vars before doing anything ───────────────────────
if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is not set. Generate one with: php artisan key:generate"
    exit 1
fi

# ── 3. Clear any stale bootstrap caches from a previous build ────────────────
php artisan optimize:clear --quiet || true

# ── 4. Run pending migrations (safe on a running DB; no-op if up to date) ─────
echo "Running migrations..."
php artisan migrate --force --no-interaction

# ── 5. Build production caches only when APP_ENV=production ──────────────────
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Caching config, routes and views..."
    php artisan config:cache  || { echo "ERROR: config:cache failed — check APP_KEY and .env"; exit 1; }
    php artisan route:cache   || { echo "ERROR: route:cache failed — check your routes/"; exit 1; }
    php artisan view:cache    || true   # blade errors are non-fatal at startup
fi

echo "Backend ready."
exec apache2-foreground
