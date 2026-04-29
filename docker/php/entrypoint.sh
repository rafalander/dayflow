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

# Run seeders if first time (empty DB)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\Role::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "🌱 Running database seeders..."
    php artisan db:seed --no-interaction --force
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
