#!/bin/bash
set -e

echo "=== Starting deployment ==="

# Clean up any existing socket files
rm -f /var/run/php-fpm.sock

echo "Running database migrations..."
php artisan migrate --force --no-interaction

echo "=== Starting PHP-FPM in background ==="
php-fpm &
PHP_FPM_PID=$!

echo "=== Waiting for PHP-FPM socket ==="
timeout=10
while [ ! -S /var/run/php-fpm.sock ] && [ $timeout -gt 0 ]; do
    echo "Waiting for socket... ($timeout)"
    sleep 1
    timeout=$((timeout-1))
done

if [ -S /var/run/php-fpm.sock ]; then
    echo "✓ PHP-FPM socket ready"
    ls -la /var/run/php-fpm.sock
else
    echo "✗ PHP-FPM socket not found!"
    exit 1
fi

echo "=== Starting Nginx in background ==="
nginx -g "daemon off;" &
NGINX_PID=$!

echo "=== Services started, monitoring processes ==="
echo "PHP-FPM PID: $PHP_FPM_PID"
echo "Nginx PID: $NGINX_PID"

# Wait for either process to exit
wait -n

# If we get here, one process died, so kill the other and exit
kill $PHP_FPM_PID $NGINX_PID 2>/dev/null
exit 1
