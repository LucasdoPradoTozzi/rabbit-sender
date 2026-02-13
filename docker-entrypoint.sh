#!/bin/bash
set -e

echo "=== Starting deployment ==="

# Clean up any existing socket files
rm -f /var/run/php-fpm.sock

echo "Running database migrations..."
php artisan migrate --force --no-interaction

echo "=== Starting PHP-FPM in background ==="
php-fpm -D

echo "=== Starting Nginx in foreground ==="
nginx -g "daemon off;"
