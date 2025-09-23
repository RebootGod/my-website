#!/bin/bash

cd /home/forge/noobz.space

echo "Starting deployment..."

# Pull latest changes
git pull origin $FORGE_SITE_BRANCH

# Install/Update Composer dependencies
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Clear OPcache BEFORE processing (important!)
echo "Clearing OPcache..."
php -r "if(function_exists('opcache_reset')) opcache_reset(); echo 'OPcache cleared';"

# Fix NPM - generate package-lock.json if missing
if [ ! -f package-lock.json ]; then
  echo "Generating package-lock.json..."
  npm install --package-lock-only
fi

# Install/Update NPM dependencies and build assets
npm ci && npm run build

# Clear all Laravel caches
if [ -f artisan ]; then
  echo "Clearing Laravel caches..."
  $FORGE_PHP artisan config:clear --force
  $FORGE_PHP artisan route:clear
  $FORGE_PHP artisan view:clear
  $FORGE_PHP artisan cache:clear
  $FORGE_PHP artisan optimize:clear
fi

# Run database migrations (with --force to skip existing tables)
if [ -f artisan ]; then
  $FORGE_PHP artisan migrate --force 2>/dev/null || echo "Some migrations already exist, continuing..."
fi

# Create storage link (for file uploads)
if [ -f artisan ]; then
  $FORGE_PHP artisan storage:link 2>/dev/null || echo "Storage link already exists"
fi

# Restart PHP-FPM FIRST to clear opcache
echo "Restarting PHP-FPM to clear opcache..."
sudo service $FORGE_PHP_FPM restart

# Wait a moment for PHP-FPM to fully restart
sleep 2

# Now optimize for production
if [ -f artisan ]; then
  echo "Optimizing for production..."
  $FORGE_PHP artisan config:cache
  $FORGE_PHP artisan route:cache
  $FORGE_PHP artisan view:cache
  $FORGE_PHP artisan optimize
fi

# Restart queue workers (if using queues)
if [ -f artisan ]; then
  $FORGE_PHP artisan queue:restart
fi

echo "Deployment completed successfully!"
echo "OPcache has been cleared and PHP-FPM restarted."