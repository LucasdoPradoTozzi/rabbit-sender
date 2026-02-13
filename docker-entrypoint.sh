#!/bin/bash
set -e

echo "=== Starting deployment ==="

# Clean up any existing socket files
rm -f /var/run/php-fpm.sock

echo "Running database migrations..."
php artisan migrate --force --no-interaction

echo "=== Starting PHP-FPM in foreground mode ==="
php-fpm -F &
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

# Function to check if process is running
check_process() {
    if ! kill -0 $1 2>/dev/null; then
        echo "Process $1 ($2) has died!"
        return 1
    fi
    return 0
}

# Monitor both processes
while true; do
    if ! check_process $PHP_FPM_PID "PHP-FPM"; then
        echo "PHP-FPM died, checking logs..."
        kill $NGINX_PID 2>/dev/null
        exit 1
    fi
    
    if ! check_process $NGINX_PID "Nginx"; then
        echo "Nginx died, checking logs..."
        kill $PHP_FPM_PID 2>/dev/null
        exit 1
    fi
    
    sleep 5
done
