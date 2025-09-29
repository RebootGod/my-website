#!/bin/bash

# Laravel Forge Deployment Script
# Clear all caches after security integration removal

echo "Starting deployment..."

# Pull latest changes
git pull origin main

# Install/update composer dependencies
composer install --no-dev --optimize-autoloader

# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers if any
php artisan queue:restart

echo "Deployment completed successfully!"