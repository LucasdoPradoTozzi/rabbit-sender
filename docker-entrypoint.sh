#!/bin/bash
set -e

echo "Running database migrations..."
php artisan migrate --force --no-interaction || echo "Migrations failed or already run"

echo "Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
