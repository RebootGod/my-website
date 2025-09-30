#!/bin/bash
# Laravel Forge Deployment Script
# Auto-executed on git push to main branch

set -e

echo "🚀 Starting deployment..."

# Navigate to project directory
cd /home/forge/noobz.space

# Pull latest changes
echo "📥 Pulling latest changes from git..."
git pull origin main

# Install/Update Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear all caches (IMPORTANT for CSS updates)
echo "🧹 Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations (safe mode)
echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

# Restart queue workers
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# Clear OPcache (if available)
if command -v php-fpm &> /dev/null; then
    echo "🔥 Clearing OPcache..."
    sudo service php8.3-fpm reload || true
fi

# Set proper permissions
echo "🔒 Setting proper permissions..."
chmod -R 755 storage bootstrap/cache
chown -R forge:forge storage bootstrap/cache

echo "✅ Deployment completed successfully!"
echo "🎉 Cache cleared, CSS updates should be visible now!"