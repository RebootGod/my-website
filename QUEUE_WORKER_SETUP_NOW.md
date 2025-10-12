# URGENT: Queue Worker Setup Required

## ⚠️ CRITICAL SETUP NEEDED

The "Refresh All TMDB" feature now uses **Queue Jobs** to prevent timeouts. Queue worker MUST be running in production for this feature to work.

## Quick Setup Steps

### Step 1: Check if Queue Worker is Running

SSH ke server:
```bash
ssh forge@noobz.space
cd /home/forge/noobz.space
ps aux | grep "queue:work"
```

Jika **TIDAK ADA OUTPUT** atau **TIDAK RUNNING** → Lanjut ke Step 2

### Step 2: Setup Queue Worker via Laravel Forge

1. Login ke **Laravel Forge Dashboard**
2. Navigate ke **Site → noobz.space**
3. Klik tab **"Daemons"** di sidebar
4. Klik **"Create Daemon"**
5. Fill form:
   ```
   Command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
   User: forge
   Directory: /home/forge/noobz.space
   Processes: 1
   ```
6. Klik **"Create Daemon"**

### Step 3: Verify Queue Worker Started

Wait 30 seconds, then check:
```bash
ssh forge@noobz.space
ps aux | grep "queue:work"
```

You should see output like:
```
forge    12345  0.0  1.2  123456  78901 ?  S    Oct12   0:01 php artisan queue:work --sleep=3
```

### Step 4: Update Deployment Script (IMPORTANT!)

Di Laravel Forge Dashboard:
1. Navigate ke **Site → noobz.space**
2. Klik tab **"Deployment"**
3. Edit **"Deploy Script"**
4. Add this AFTER composer install:

```bash
# Restart queue worker after deployment (IMPORTANT!)
( flock -w 10 9 || exit 1
    echo 'Restarting queue worker...'
    php artisan queue:restart
) 9>/tmp/queue-restart-lock
```

Full example:
```bash
cd /home/forge/noobz.space
git pull origin main
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader

# Restart queue worker after deployment (IMPORTANT!)
( flock -w 10 9 || exit 1
    echo 'Restarting queue worker...'
    php artisan queue:restart
) 9>/tmp/queue-restart-lock

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

5. Save changes

## Testing

After setup, test the feature:

1. Go to https://noobz.space/admin/movies
2. Click **"Refresh All TMDB"** button
3. Confirm dialog
4. Progress modal should appear immediately
5. Check progress updates in real-time

### Check Queue in Database

```bash
ssh forge@noobz.space
cd /home/forge/noobz.space
php artisan tinker
```

Then in tinker:
```php
DB::table('jobs')->count();  // Should show jobs
DB::table('jobs')->get();    // Show all queued jobs
```

### Check Queue Logs

```bash
tail -f /home/forge/noobz.space/storage/logs/laravel.log | grep "RefreshAllTmdb"
```

You should see:
```
🚀 RefreshAllTmdbJob STARTED
✅ RefreshAllTmdbJob COMPLETED
```

## Troubleshooting

### Problem: Jobs stuck in database, not processing

**Solution:**
```bash
ssh forge@noobz.space
cd /home/forge/noobz.space

# Check if worker is running
ps aux | grep "queue:work"

# If not running, restart via Forge or manually
php artisan queue:work &
```

### Problem: 504 Timeout still happening

**Possible causes:**
1. Queue worker not running → Check Step 1
2. Queue worker needs restart → Run `php artisan queue:restart`
3. Jobs failing → Check `php artisan queue:failed`

### Problem: Progress modal stuck at 0%

**Solution:**
```bash
# Check if job is processing
php artisan queue:failed

# If jobs failed, check error:
php artisan queue:failed --verbose

# Clear failed jobs after fixing issue:
php artisan queue:retry all
```

## Manual Queue Processing (Emergency)

If daemon is down and you need to process jobs NOW:

```bash
ssh forge@noobz.space
cd /home/forge/noobz.space
php artisan queue:work --once  # Process 1 job
# OR
php artisan queue:work --max-time=60  # Process for 60 seconds
```

## Next Deployment

After next `git push`, Forge will auto-deploy and restart queue worker (if you added restart command to deployment script).

Monitor first deployment:
```bash
ssh forge@noobz.space
tail -f /home/forge/noobz.space/storage/logs/laravel.log
```

Look for:
```
Restarting queue worker...
```

## Important Notes

- ✅ Queue worker runs 24/7 in background
- ✅ Automatically starts on server reboot (via Forge daemon)
- ✅ Automatically restarts after deployment (via deployment script)
- ✅ No more 504 timeouts for large operations
- ✅ Progress tracking works in real-time via cache

## Questions?

Check full documentation: **QUEUE_SETUP.md**

---

**Setup by:** Admin
**Date:** October 2025
**Priority:** CRITICAL (feature won't work without this)
