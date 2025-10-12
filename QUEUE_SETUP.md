# Queue Worker Setup for Production

## Overview
Application menggunakan Laravel Queue untuk background processing, khususnya untuk:
- **Refresh All TMDB** - Process bulk TMDB refresh tanpa timeout
- **Movie/Series Upload** - Process media uploads dari bot
- **Email Notifications** - Send emails asynchronously

## Configuration

### Queue Driver
```env
QUEUE_CONNECTION=database
```

Database driver digunakan untuk queue jobs, storing jobs di `jobs` table.

## Setup di Laravel Forge

### 1. Enable Queue Worker (Daemon)

Di Laravel Forge dashboard:

1. **Navigate to** → Site → Daemons
2. **Click** → Create Daemon
3. **Configure:**
   ```
   Command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
   User: forge
   Directory: /home/forge/noobz.space
   Processes: 1
   ```
4. **Click** → Create Daemon

### 2. Alternative: Supervisor Configuration

Jika tidak menggunakan Forge daemon, setup Supervisor manually:

```ini
[program:noobz-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/noobz.space/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=forge
numprocs=1
redirect_stderr=true
stdout_logfile=/home/forge/noobz.space/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Apply configuration:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start noobz-queue-worker:*
```

## Queue Worker Commands

### Check Queue Status
```bash
php artisan queue:work --once  # Process one job
php artisan queue:listen       # Listen for new jobs
php artisan queue:work         # Process jobs continuously (recommended)
```

### Monitor Queue
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Restart Queue Worker (After Deployment)
```bash
php artisan queue:restart
```

**IMPORTANT:** Always restart queue worker after deployment to load new code changes.

## Laravel Forge Deployment Script

Add to deployment script untuk auto-restart queue worker:

```bash
cd /home/forge/noobz.space
git pull origin main
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader

# Restart queue worker after deployment
( flock -w 10 9 || exit 1
    echo 'Restarting queue worker...'
    php artisan queue:restart
) 9>/tmp/queue-restart-lock

# Other deployment tasks...
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Monitoring & Troubleshooting

### Check if Queue Worker is Running
```bash
ps aux | grep "queue:work"
```

### Check Queue Logs
```bash
tail -f storage/logs/laravel.log | grep "Queue"
```

### Common Issues

#### 1. Jobs Not Processing
**Problem:** Jobs stuck in `queued` status
**Solution:** 
```bash
# Check if worker is running
ps aux | grep "queue:work"

# If not running, start it
php artisan queue:work &

# Or restart supervisor
sudo supervisorctl restart noobz-queue-worker:*
```

#### 2. Jobs Failing Silently
**Problem:** Jobs fail without error message
**Solution:**
```bash
# Check failed jobs table
php artisan queue:failed

# Enable verbose logging
php artisan queue:work --verbose
```

#### 3. Memory Leaks
**Problem:** Worker consumes too much memory
**Solution:**
```bash
# Restart worker after X jobs
php artisan queue:work --max-jobs=100

# Restart worker after X seconds
php artisan queue:work --max-time=3600
```

## Queue Job Classes

### Current Jobs
- `RefreshAllTmdbJob` - Bulk TMDB refresh (max 1 hour timeout)
- `ProcessMovieUploadJob` - Process movie uploads (2 min timeout)
- `ProcessSeriesUploadJob` - Process series uploads (2 min timeout)
- `ProcessEpisodeUploadJob` - Process episode uploads (2 min timeout)
- `SendWelcomeEmailJob` - Send welcome emails
- `SendPasswordResetEmailJob` - Send password reset emails
- `BackupDatabaseJob` - Backup database
- `CleanupExpiredInviteCodesJob` - Cleanup expired codes

### Job Timeouts
```php
public $timeout = 3600;  // 1 hour for RefreshAllTmdbJob
public $timeout = 120;   // 2 minutes for upload jobs
public $tries = 1;       // No retry for RefreshAllTmdbJob (manual re-trigger)
public $tries = 3;       // 3 retries for other jobs
```

## Best Practices

1. **Always restart queue worker after deployment**
   ```bash
   php artisan queue:restart
   ```

2. **Monitor failed jobs regularly**
   ```bash
   php artisan queue:failed
   ```

3. **Use dedicated queue for time-critical jobs**
   ```php
   RefreshAllTmdbJob::dispatch(...)->onQueue('tmdb');
   ```

4. **Set appropriate timeouts**
   - Short tasks: 120 seconds
   - Medium tasks: 600 seconds (10 min)
   - Long tasks: 3600 seconds (1 hour)

5. **Handle job failures gracefully**
   ```php
   public function failed(\Throwable $exception): void
   {
       // Update progress, notify admins, log error
   }
   ```

## Security Notes

- Queue jobs run as `forge` user
- Jobs have access to all application resources
- Validate all inputs before dispatching jobs
- Use CSRF protection on endpoints that dispatch jobs
- Log all queue activities for audit trail

## Performance Optimization

### Multiple Workers
For high-traffic applications, run multiple workers:

```ini
numprocs=3  # Run 3 workers in parallel
```

### Redis Queue (Optional Upgrade)
For better performance, consider Redis queue:

```env
QUEUE_CONNECTION=redis
REDIS_QUEUE_CONNECTION=queue
```

## Scheduled Tasks

Queue worker should run continuously. For scheduled tasks, use Laravel Scheduler:

```bash
# Add to crontab
* * * * * cd /home/forge/noobz.space && php artisan schedule:run >> /dev/null 2>&1
```

---

**Last Updated:** October 2025
**Maintained By:** Development Team
**Laravel Version:** 11.x
