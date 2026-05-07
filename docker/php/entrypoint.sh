#!/bin/bash
set -e

echo "🚀 Starting Dayflow backend..."

cd /var/www/html

# Install composer dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Generate app key only if missing in .env (shell has no APP_KEY when unset in compose)
if ! grep -qE '^APP_KEY=base64:.+' .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate --no-interaction
fi

# Wait for MySQL to be truly ready (migrate:status fails before first migrate — empty DB)
echo "⏳ Waiting for database connection..."
until php artisan db:show > /dev/null 2>&1; do
    sleep 2
done

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --no-interaction --force

# Seed when superadmin row is missing (Role model/table were removed; old Role::count() never triggered seed).
# Optional: DAYFLOW_DB_SEED=always runs full DatabaseSeeder on every start (idempotent seeders; slower startup).
SEED_MODE="${DAYFLOW_DB_SEED:-auto}"
if [ "$SEED_MODE" = "always" ]; then
    echo "🌱 Running database seeders (DAYFLOW_DB_SEED=always)..."
    php artisan db:seed --no-interaction --force
else
    SUPERADMIN_EXISTS=$(php artisan tinker --execute="echo \App\Models\User::query()->where('email', config('dayflow.superadmin.email'))->exists() ? '1' : '0';" 2>/dev/null | tail -n1 | tr -d '\r\n[:space:]' || echo "0")
    if [ "$SUPERADMIN_EXISTS" != "1" ]; then
        echo "🌱 Running database seeders (superadmin user not found)..."
        php artisan db:seed --no-interaction --force
    fi
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Set storage permissions
echo "📁 Setting storage permissions..."
php artisan storage:link --force 2>/dev/null || true

echo "✅ Backend ready!"

exec "$@"
