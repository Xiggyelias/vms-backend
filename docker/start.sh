#!/bin/sh
set -eu

cd /var/www/html

chown -R www-data:www-data storage bootstrap/cache || true

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache >/dev/null 2>&1 || true
    php artisan route:cache >/dev/null 2>&1 || true
    php artisan view:cache >/dev/null 2>&1 || true
fi

exec apache2-foreground
