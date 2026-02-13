#!/bin/bash
set -e

echo "=== Starting deployment ==="
echo "Running database migrations..."
php artisan migrate --force --no-interaction

echo "=== Starting supervisor ==="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
