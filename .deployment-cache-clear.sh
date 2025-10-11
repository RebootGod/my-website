#!/bin/bash
# Force clear all Laravel caches after deployment

echo "🧹 Clearing all Laravel caches..."

# Clear compiled views
php artisan view:clear
echo "✅ View cache cleared"

# Clear route cache
php artisan route:clear
echo "✅ Route cache cleared"

# Clear config cache
php artisan config:clear
echo "✅ Config cache cleared"

# Clear application cache
php artisan cache:clear
echo "✅ Application cache cleared"

# Clear compiled files
php artisan clear-compiled
echo "✅ Compiled files cleared"

# Rebuild caches for optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🚀 All caches cleared and rebuilt!"
