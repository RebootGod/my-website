# CRITICAL BUG FOUND: Queue Worker Timeout Too Short

## Root Cause Analysis

**Problem:** RefreshAllTmdbJob fails with `MaxAttemptsExceededException`

**Root Cause Found (via SSH):**

```bash
# Current queue workers running:
forge  146537  php8.3 /home/forge/noobz.space/artisan queue:work database --timeout=300
forge  146538  php8.3 /home/forge/noobz.space/artisan queue:work database --timeout=300
```

**Issue:**
- Job `RefreshAllTmdbJob` has `$timeout = 3600` (1 hour)
- Worker running with `--timeout=300` (5 minutes only!)
- Job processing 478 items takes longer than 5 minutes
- Worker kills job after 300 seconds → Job marked as failed
- Job retries → Same timeout → Fails again
- After multiple retries → `MaxAttemptsExceededException`

## The Fix

### Option 1: Update Daemon Configuration in Laravel Forge (Recommended)

1. **Go to** Laravel Forge Dashboard
2. **Navigate to** Site → Daemons
3. **Find** "TMDB Refresh" daemon (or similar name)
4. **Edit** daemon configuration
5. **Change `--timeout`** from `300` to `3600`
6. **Save** and **Restart** daemon

**Before:**
```
php artisan queue:work database --sleep=3 --timeout=300 --tries=1
```

**After:**
```
php artisan queue:work database --sleep=3 --timeout=3600 --tries=1
```

### Option 2: Stop and Restart Workers Manually (Emergency Fix)

```bash
ssh -i ~/.ssh/noobz_final root@145.79.15.4

# Kill existing workers with wrong timeout
pkill -f "queue:work database --timeout=300"

# Start new worker with correct timeout
cd /home/forge/noobz.space
nohup php artisan queue:work database --sleep=3 --timeout=3600 --tries=1 --memory=128 > /dev/null 2>&1 &

# Verify worker is running with correct config
ps aux | grep "queue:work database"
```

### Option 3: Fix via Supervisor Config

If using Supervisor (not Forge daemon):

```bash
# Edit supervisor config
sudo nano /etc/supervisor/conf.d/noobz-queue-worker.conf

# Change timeout from 300 to 3600 in command line
command=php /home/forge/noobz.space/artisan queue:work database --sleep=3 --tries=1 --timeout=3600

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart noobz-queue-worker:*
```

## After Fix: Retry Failed Jobs

Once worker is running with correct timeout:

```bash
ssh -i ~/.ssh/noobz_final root@145.79.15.4
cd /home/forge/noobz.space

# Check failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# OR retry specific job ID
php artisan queue:retry <id>

# Monitor progress
tail -f storage/logs/laravel.log | grep "RefreshAllTmdbJob"
```

## Verification

After fix, verify worker is running with correct timeout:

```bash
ps aux | grep "queue:work database"

# Should show:
# forge  XXXXX  php8.3 artisan queue:work database --sleep=3 --timeout=3600
```

## Additional Issues Found

Multiple queue workers running with different configurations:

```bash
# Worker 1 & 2: Using REDIS queue (not database!)
php8.3 artisan queue:work redis --timeout=120

# Worker 3 & 4: Using DATABASE queue but timeout=300 (too short!)
php8.3 artisan queue:work database --timeout=300
```

### Recommendation: Consolidate Workers

You have mixed queue configurations. Recommend:

1. **Decide on one queue driver:** Redis OR Database
   - Currently .env has `QUEUE_CONNECTION=database`
   - But some workers use Redis

2. **Update all workers to use same driver:**
   - If using Database: All workers should be `queue:work database`
   - If using Redis: Change .env to `QUEUE_CONNECTION=redis`

3. **Set appropriate timeouts per queue:**
   - TMDB refresh queue: `--timeout=3600` (1 hour)
   - Bot uploads queue: `--timeout=300` (5 minutes)
   - Email queue: `--timeout=60` (1 minute)

## Summary

**Problem:** Job timeout (3600s) > Worker timeout (300s)
**Solution:** Update worker timeout to 3600 seconds
**Action:** Edit daemon in Forge, change `--timeout` from 300 to 3600
**Then:** Retry failed jobs with `php artisan queue:retry all`

---

**Discovered:** October 12, 2025 16:50 UTC
**Status:** Awaiting fix in production
