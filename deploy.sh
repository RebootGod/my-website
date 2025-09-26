#!/bin/bash

# Laravel Forge Deployment Script for Noobz Cinema
# Handles npm permission issues and ensures robust deployment

set -e

echo "🚀 Starting Noobz Cinema deployment..."

# Step 1: Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Step 2: Handle npm permission issues
echo "🔧 Fixing npm permissions and installing dependencies..."

# Remove node_modules with proper permissions
if [ -d "node_modules" ]; then
    echo "🗑️ Cleaning existing node_modules..."
    chmod -R 755 node_modules 2>/dev/null || true
    rm -rf node_modules 2>/dev/null || true
fi

# Clear npm cache
npm cache clean --force 2>/dev/null || true

# Install npm dependencies with fresh start
echo "📦 Installing npm dependencies..."
npm install --no-audit --no-fund --prefer-offline

# Build assets
echo "🏗️ Building production assets..."
npm run build

# Step 3: Laravel optimizations
echo "⚡ Optimizing Laravel application..."

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Run migrations (safe)
php artisan migrate --force

# Create storage link (ignore if exists)
php artisan storage:link 2>/dev/null || echo "Storage link already exists"

# Cache optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Step 4: Queue restart
echo "🔄 Restarting queues..."
php artisan queue:restart

echo "✅ Noobz Cinema deployed successfully!"