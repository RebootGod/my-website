# Debug Queue Worker Issue - Job Stuck at 0%

## Issue
Job berhasil di-queue (response 200) tapi stuck di 0% progress.
Console log menunjukkan job masuk queue dengan progressKey tapi tidak di-process.

## Debug Commands

Run these commands via SSH:

```bash
# SSH to server
ssh forge@noobz.space
cd /home/forge/noobz.space

# 1. Check if queue worker is running
ps aux | grep "queue:work"

# Expected output:
# forge    12345  0.0  1.2  123456  78901 ?  S    Oct12   0:01 php artisan queue:work database --sleep=3 --timeout=3600

# If NO OUTPUT → Worker not running! Need to start it.

# 2. Check jobs in database
php artisan tinker
# Then run:
DB::table('jobs')->count();
DB::table('jobs')->get();
exit

# Expected: Should see jobs with payload containing RefreshAllTmdbJob

# 3. Check failed jobs
php artisan queue:failed

# Expected: Empty (no failed jobs yet) OR see error messages

# 4. Try to process ONE job manually
php artisan queue:work database --once

# Expected: Should process 1 job and show output
# If error → Will show error message
# If no output → No jobs in queue or connection issue

# 5. Check Laravel logs
tail -100 /home/forge/noobz.space/storage/logs/laravel.log

# Look for:
# - "RefreshAllTmdbJob STARTED"
# - Any error messages
# - Connection errors

# 6. Check if database queue table exists
php artisan tinker
# Then run:
Schema::hasTable('jobs');  // Should return: true
Schema::hasTable('failed_jobs');  // Should return: true
exit

# 7. Check queue configuration
cat .env | grep QUEUE

# Expected:
# QUEUE_CONNECTION=database

# 8. Test queue with simple job
php artisan queue:work database --once --verbose

# This will show detailed output of what's happening
```

## Most Common Issues & Solutions

### Issue 1: Worker Not Running
**Symptoms:** `ps aux | grep "queue:work"` returns nothing
**Solution:** 
- Go to Forge → Daemons → Check "TMDB Refresh" status
- If "Failed" or "Stopped" → Click "Restart"
- If "Unknown" → Wait 30 seconds, refresh page
- If still not running → Delete daemon, recreate with correct config

### Issue 2: Connection Field Empty
**Symptoms:** Worker running but not processing jobs
**Solution:**
- Go to Forge → Daemons → Click "TMDB Refresh"
- Check command preview, should be:
  `php artisan queue:work database --sleep=3 --timeout=3600`
- If command is `php artisan queue:work --sleep=3` (no "database")
  → Connection field was empty!
  → Edit daemon, set connection to: `database`
  → Save and restart daemon

### Issue 3: Jobs Table Not Migrated
**Symptoms:** Error "Table 'noobz_space.jobs' doesn't exist"
**Solution:**
```bash
php artisan migrate
```

### Issue 4: Permission Issues
**Symptoms:** Worker can't write to storage/logs
**Solution:**
```bash
sudo chown -R forge:forge /home/forge/noobz.space/storage
sudo chmod -R 775 /home/forge/noobz.space/storage
```

### Issue 5: Code Not Updated
**Symptoms:** Worker using old code (before RefreshAllTmdbJob exists)
**Solution:**
```bash
# Pull latest code
cd /home/forge/noobz.space
git pull origin main

# Restart worker
php artisan queue:restart

# Or via Forge → Daemons → Restart "TMDB Refresh"
```

## Quick Fix (Most Likely Solution)

Based on the screenshot, most likely issue is **connection field was empty** when creating daemon.

### Fix Steps:
1. **Go to Forge** → Daemons
2. **Click** "TMDB Refresh" daemon
3. **Check command**, should show:
   ```
   php artisan queue:work database --sleep=3 --timeout=3600 --tries=1 --memory=128
   ```
4. **If "database" is missing:**
   - Edit daemon
   - Fill "connection" field with: `database`
   - Save
   - Restart daemon
5. **Wait 10 seconds**
6. **Go back to website** → Click "Refresh All TMDB" again
7. **Should work now!**

## Manual Job Processing (Emergency)

If you need to process jobs NOW while troubleshooting daemon:

```bash
# SSH to server
ssh forge@noobz.space
cd /home/forge/noobz.space

# Process all queued jobs (will run until queue is empty)
php artisan queue:work database --stop-when-empty

# This will:
# 1. Pick up all queued jobs
# 2. Process them one by one
# 3. Stop when queue is empty
# 4. You can see progress in real-time on website
```

## Verification After Fix

After fixing, verify worker is processing:

```bash
# Check worker is running
ps aux | grep "queue:work"

# Watch logs in real-time
tail -f /home/forge/noobz.space/storage/logs/laravel.log

# Then go to website and click "Refresh All TMDB"
# You should see in logs:
# 🚀 RefreshAllTmdbJob STARTED
# [timestamp] Processing: App\Jobs\RefreshAllTmdbJob
# [timestamp] Processed: App\Jobs\RefreshAllTmdbJob
# ✅ RefreshAllTmdbJob COMPLETED
```

## Report Back

After running debug commands, report:
1. Output of `ps aux | grep "queue:work"`
2. Output of `php artisan queue:work database --once`
3. Any error messages from logs
4. Current daemon status in Forge dashboard

This will help identify exact issue!

---

**Generated:** October 12, 2025
**Issue:** Job stuck at 0% - worker not processing
