# Queue Worker Setup for Image Downloads

## Overview
Sistem import TMDB sekarang menggunakan **queue jobs** untuk download poster dan backdrop ke local storage.

---

## How It Works

### 1. Import Process Flow
```
User imports movie/series from TMDB
    ↓
Controller saves to database (with TMDB paths)
    ↓
Dispatches DownloadTmdbImageJob to queue
    ↓
Job downloads image from TMDB
    ↓
Saves to storage/app/public/tmdb_images/
    ↓
Updates database with local_poster_path / local_backdrop_path
```

### 2. Queue Configuration

**Queue Name:** `image-downloads`

**Driver:** Check `config/queue.php`
- Production should use: `redis` or `database`
- For Laravel Forge: Usually `redis`

---

## Production Setup (Laravel Forge)

### Step 1: Check Current Queue Driver

SSH into server:
```bash
ssh forge@noobz.space
cd /home/forge/noobz.space
cat .env | grep QUEUE_CONNECTION
```

Should show: `QUEUE_CONNECTION=redis` or `QUEUE_CONNECTION=database`

### Step 2: Ensure Queue Worker is Running

In **Laravel Forge Dashboard**:
1. Go to your site: noobz.space
2. Click "Queue" tab
3. Ensure worker is configured:

```
Connection: redis (or database)
Queue: default,image-downloads
Processes: 3
Max Seconds: 60
Memory: 512
Sleep: 3
Max Tries: 3
```

### Step 3: Start/Restart Queue Worker

In Forge dashboard, click **"Restart Queue"** button.

Or manually via SSH:
```bash
php artisan queue:restart
```

### Step 4: Monitor Queue

Check queue status:
```bash
php artisan queue:work --queue=image-downloads,default --tries=3 --timeout=60
```

Check failed jobs:
```bash
php artisan queue:failed
```

Retry failed jobs:
```bash
php artisan queue:retry all
```

---

## Manual Command (If Queue Not Working)

If queue worker is not set up, you can download images manually:

```bash
# Download all missing TMDB images
php artisan tmdb:download-images

# Download for specific movie
php artisan tmdb:download-images --movie=123

# Download for specific series
php artisan tmdb:download-images --series=456
```

---

## Testing

### 1. Import a Movie
1. Go to Admin → Import Movies from TMDB
2. Search for a movie
3. Import it
4. Check logs: `storage/logs/laravel.log`

Should see:
```
Dispatched poster download job [movie_id: 123]
Dispatched backdrop download job [movie_id: 123]
```

### 2. Check Queue Jobs
```bash
# Via tinker
php artisan tinker
>>> \DB::table('jobs')->count();
>>> \DB::table('jobs')->get();
```

### 3. Check Downloaded Images
```bash
ls -la storage/app/public/tmdb_images/posters/movies/
ls -la storage/app/public/tmdb_images/backdrops/movies/
```

### 4. Check Database
```bash
php artisan tinker
>>> $movie = \App\Models\Movie::find(123);
>>> $movie->local_poster_path;
>>> $movie->local_backdrop_path;
```

---

## Troubleshooting

### Images Not Downloading?

**Check 1:** Is queue worker running?
```bash
ps aux | grep queue:work
```

**Check 2:** Check failed jobs
```bash
php artisan queue:failed
```

**Check 3:** Check logs
```bash
tail -f storage/logs/laravel.log
```

**Check 4:** Try manual download
```bash
php artisan tmdb:download-images
```

### Storage Permission Issues?

```bash
chmod -R 755 storage/app/public/tmdb_images
chown -R forge:forge storage/app/public/tmdb_images
```

### Symlink Missing?

```bash
php artisan storage:link
```

---

## Queue Commands Reference

```bash
# Start worker (foreground - for testing)
php artisan queue:work --queue=image-downloads,default

# Start worker (background - via supervisor/systemd)
php artisan queue:work --queue=image-downloads,default --daemon

# Restart all workers
php artisan queue:restart

# Clear all jobs
php artisan queue:flush

# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Delete failed job
php artisan queue:forget <job-id>
```

---

## Expected Behavior

✅ **After Import:**
- Movie/Series created in database
- `poster_path` and `backdrop_path` contain TMDB paths (e.g., `/abc123.jpg`)
- Jobs dispatched to `image-downloads` queue
- Success message: "Images are being downloaded in the background"

✅ **After Job Processed (30-60 seconds):**
- Images downloaded to `storage/app/public/tmdb_images/`
- `local_poster_path` and `local_backdrop_path` updated in database
- Images accessible via `/storage/tmdb_images/...`

✅ **Accessor Priority:**
```php
$movie->poster_url → Returns:
1. local_poster_path (if exists) → /storage/tmdb_images/posters/movies/123.jpg
2. poster_url field (if set) → custom URL
3. Placeholder → https://placehold.co/500x750?text=No+Poster
```

---

## Production Deployment Checklist

Before deploying to production:

- [ ] Queue driver set to `redis` or `database` (not `sync`)
- [ ] Queue worker configured in Laravel Forge
- [ ] Queue worker running and active
- [ ] Storage directories exist with correct permissions
- [ ] Symlink created: `public/storage` → `storage/app/public`
- [ ] Test import and verify images download
- [ ] Monitor logs for any errors
- [ ] Check failed jobs queue is empty

---

## Contact

If issues persist after following this guide:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check queue worker logs (in Forge or systemd)
3. Review failed jobs: `php artisan queue:failed`
