#!/bin/bash
set -e

echo "=== Starting deployment ==="

# Clean up any existing socket files
rm -f /var/run/php-fpm.sock

echo "Running database migrations..."
php artisan migrate --force --no-interaction

echo "=== Caching configs with runtime environment ==="
php artisan config:cache
# php artisan event:cache
# php artisan route:cache  # Disabled - may break Flux dynamic routes
php artisan view:cache

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

# Comprehensive diagnostics
echo "=== Running diagnostics ==="
echo "--- PHP Version ---"
php -r "echo 'PHP: ' . PHP_VERSION . PHP_EOL;"

echo "--- Laravel Environment ---"
php artisan --version || echo "Laravel artisan failed!"
php artisan env || echo "Env check failed"

echo "--- Database Connection ---"
php artisan db:show 2>&1 || echo "Database show failed"

echo "--- File Permissions ---"
ls -la /var/www/html/storage/
ls -la /var/www/html/database/

echo "--- Environment Variables ---"
php -r "echo 'APP_ENV: ' . getenv('APP_ENV') . PHP_EOL;"
php -r "echo 'APP_DEBUG: ' . getenv('APP_DEBUG') . PHP_EOL;"
php -r "echo 'APP_KEY: ' . (getenv('APP_KEY') ? 'SET' : 'NOT SET') . PHP_EOL;"
php -r "echo 'DB_CONNECTION: ' . getenv('DB_CONNECTION') . PHP_EOL;"

echo "--- Testing PHP file directly ---"
sleep 2
curl -v http://localhost/test.php 2>&1 | head -30

echo "--- Testing Laravel index ---"
curl -v http://localhost/index.php 2>&1 | head -50

echo "--- Checking Laravel error log ---"
if [ -f /var/www/html/storage/logs/laravel.log ]; then
    echo "Laravel log found, showing last 50 lines:"
    tail -50 /var/www/html/storage/logs/laravel.log
else
    echo "No Laravel log file found yet"
fi

echo "--- Checking PHP-FPM error log ---"
tail -20 /proc/$PHP_FPM_PID/fd/2 2>&1 || echo "Could not read PHP-FPM stderr"

echo "=== Diagnostics complete, entering monitor loop ==="

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
