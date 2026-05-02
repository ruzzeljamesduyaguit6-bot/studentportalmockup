#!/bin/sh
set -e

# Run Laravel bootstrap tasks
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

# Start Supervisor (manages PHP-FPM + Nginx)
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
