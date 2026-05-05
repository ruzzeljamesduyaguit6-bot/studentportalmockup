#!/bin/sh
set -e

# Run Laravel bootstrap tasks
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
echo "Seeding database..."
if ! php artisan db:seed --force --verbose; then
    echo "ERROR: Database seeding failed. See output above." >&2
    exit 1
fi
echo "Database seeding completed successfully."

# Start Supervisor (manages PHP-FPM + Nginx)
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
