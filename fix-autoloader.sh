#!/bin/bash
echo "=== FIXING AUTOLOADER & CACHE ISSUES ==="

# Clear all Laravel caches
echo "1. Clearing Laravel caches..."
php artisan config:clear --force
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize:clear

# Regenerate autoloader
echo "2. Regenerating autoloader..."
composer dump-autoload --optimize

# Clear opcache if available
echo "3. Clearing opcache..."
php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; } else { echo 'OPcache not available'; }"

# Restart PHP-FPM
echo "4. Restarting PHP-FPM..."
sudo service php8.2-fpm restart

echo "5. Recaching optimized files..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== AUTOLOADER FIX COMPLETE ==="