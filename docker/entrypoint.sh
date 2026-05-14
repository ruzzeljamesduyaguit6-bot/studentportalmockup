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

# Seed only when the users table is empty (first-boot only)
USER_COUNT=$(php artisan tinker --no-interaction --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1 | tr -d '[:space:]')
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "[entrypoint] Empty database detected — running seeders..."
    php artisan db:seed --force
else
    echo "[entrypoint] Database already seeded (${USER_COUNT} users found) — skipping."
fi

echo "[entrypoint] Bootstrap complete. Starting Supervisor..."

# Start Supervisor (manages PHP-FPM + Nginx)
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
