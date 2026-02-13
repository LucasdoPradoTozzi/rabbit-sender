#!/bin/bash
set -e

echo "=== Starting deployment ==="
echo "Running database migrations..."
php artisan migrate --force --no-interaction || echo "Migrations failed or already run"

echo "=== Clearing caches ==="
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "=== Verifying environment ==="
php artisan about || true

echo "=== Starting supervisor ==="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
