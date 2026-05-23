#!/bin/sh
# Container entrypoint — Apache ALWAYS starts, even if artisan fails.
cd /var/www/html

# ── 1. Permissions ────────────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# ── 2. Ensure APP_KEY is set (auto-generate ephemeral key if missing) ─────────
if [ -z "${APP_KEY:-}" ]; then
    echo "WARNING: APP_KEY is not set — generating an ephemeral key for this boot."
    echo "IMPORTANT: Set a stable APP_KEY in Dokploy env vars to prevent session"
    echo "           invalidation on every container restart."
    # Generate base64:... key without calling artisan (artisan needs APP_KEY to boot)
    EPHEMERAL_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
    export APP_KEY="$EPHEMERAL_KEY"
    echo "Ephemeral APP_KEY generated (export to env to make permanent)."
fi

# ── 3. Clear stale caches from previous build ─────────────────────────────────
php artisan optimize:clear --quiet 2>/dev/null || true

# ── 4. Wait for DB (up to 30 s), then migrate ────────────────────────────────
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
WAIT=0
DB_READY=0
echo "Checking database ${DB_HOST}:${DB_PORT}..."
while [ "$WAIT" -lt 30 ]; do
    if php -r "exit(@fsockopen('${DB_HOST}', ${DB_PORT}) ? 0 : 1);" 2>/dev/null; then
        DB_READY=1
        break
    fi
    sleep 2
    WAIT=$((WAIT + 2))
done

if [ "$DB_READY" = "1" ]; then
    echo "Database reachable. Running migrations..."
    php artisan migrate --force --no-interaction 2>&1 || echo "WARNING: migrate failed"
else
    echo "WARNING: database not reachable after ${WAIT}s — skipping migrations"
fi

# ── 5. Build caches (non-fatal — stale cache is better than no container) ─────
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Building caches..."
    php artisan config:cache 2>&1 || echo "WARNING: config:cache failed"
    php artisan route:cache  2>&1 || echo "WARNING: route:cache failed"
    php artisan view:cache   2>&1 || echo "WARNING: view:cache failed"
fi

# ── 6. Verify Laravel can bootstrap before handing off to Apache ─────────────
echo "Verifying Laravel bootstrap..."
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Http\Kernel::class);
echo 'PHP ' . PHP_VERSION . ' — Laravel bootstrap OK' . PHP_EOL;
" 2>&1 || echo "WARNING: Laravel bootstrap check failed — Apache will start anyway"

echo "Starting Apache..."
exec apache2-foreground
