#!/bin/sh
set -e

echo "[entrypoint] Starting Laravel bootstrap..."

# Generate app key if not already set
php artisan key:generate --no-interaction --force 2>/dev/null || true

# Cache configuration, routes, and views for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure the storage symlink exists (public/storage -> storage/app/public)
php artisan storage:link --no-interaction 2>/dev/null || true

# Run database migrations (idempotent — safe to run on every boot)
php artisan migrate --force
echo "Seeding database..."
if ! php artisan db:seed --force --verbose; then
    echo "ERROR: Database seeding failed. See output above." >&2
    exit 1
fi
echo "Database seeding completed successfully."

# Start Supervisor (manages PHP-FPM + Nginx)
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
