# TMDB Image Download Matrix

## Overview
This document tracks which images need to be downloaded to local storage when refreshing from TMDB.

## Image Fields by Model

### Movie Model
| Field Name | Type | Download? | Job Dispatched | Notes |
|-----------|------|-----------|----------------|-------|
| `poster_path` | Metadata | ❌ No | - | TMDB relative path (e.g., `/abc.jpg`) |
| `poster_url` | Metadata | ❌ No | - | Full TMDB URL (e.g., `https://image.tmdb.org/t/p/w500/abc.jpg`) |
| **`local_poster_path`** | **Local Storage** | **✅ YES** | **DownloadTmdbImageJob** | **Downloaded to `public/storage/movies/{tmdb_id}/poster.jpg`** |
| `backdrop_path` | Metadata | ❌ No | - | TMDB relative path |
| `backdrop_url` | Metadata | ❌ No | - | Full TMDB URL |
| **`local_backdrop_path`** | **Local Storage** | **✅ YES** | **DownloadTmdbImageJob** | **Downloaded to `public/storage/movies/{tmdb_id}/backdrop.jpg`** |

**Total Downloads per Movie:** 2 images (poster + backdrop)

---

### Series Model
| Field Name | Type | Download? | Job Dispatched | Notes |
|-----------|------|-----------|----------------|-------|
| `poster_path` | Metadata | ❌ No | - | TMDB relative path |
| `poster_url` | Metadata | ❌ No | - | Full TMDB URL |
| **`local_poster_path`** | **Local Storage** | **✅ YES** | **DownloadTmdbImageJob** | **Downloaded to `public/storage/series/{tmdb_id}/poster.jpg`** |
| `backdrop_path` | Metadata | ❌ No | - | TMDB relative path |
| `backdrop_url` | Metadata | ❌ No | - | Full TMDB URL |
| **`local_backdrop_path`** | **Local Storage** | **✅ YES** | **DownloadTmdbImageJob** | **Downloaded to `public/storage/series/{tmdb_id}/backdrop.jpg`** |

**Total Downloads per Series:** 2 images (poster + backdrop)

---

### SeriesSeason Model
| Field Name | Type | Download? | Job Dispatched | Notes |
|-----------|------|-----------|----------------|-------|
| `poster_path` | Metadata | ❌ No | - | TMDB relative path |
| **`local_poster_path`** | **Local Storage** | **✅ YES** | **DownloadTmdbImageJob** | **Downloaded to `public/storage/series/{tmdb_id}/season_{num}/poster.jpg`** |

**Total Downloads per Season:** 1 image (poster only)

---

### SeriesEpisode Model
| Field Name | Type | Download? | Job Dispatched | Notes |
|-----------|------|-----------|----------------|-------|
| `still_path` | Metadata | ❌ No | - | TMDB relative path (episode thumbnail) |
| **`local_still_path`** | **Local Storage** | **✅ YES** | **DownloadTmdbImageJob** | **Downloaded to `public/storage/series/{tmdb_id}/season_{num}/episode_{num}.jpg`** |

**Total Downloads per Episode:** 1 image (still/thumbnail)

---

## Refresh TMDB Implementation Status

### ✅ Movies (COMPLETE)
- [x] Update `poster_path` metadata
- [x] Update `poster_url` metadata
- [x] **Dispatch download job for `local_poster_path`** (if changed)
- [x] Update `backdrop_path` metadata
- [x] Update `backdrop_url` metadata
- [x] **Dispatch download job for `local_backdrop_path`** (if changed)

### ✅ Series (COMPLETE)
- [x] Update `poster_path` metadata
- [x] Update `poster_url` metadata
- [x] **Dispatch download job for `local_poster_path`** (if changed)
- [x] Update `backdrop_path` metadata
- [x] Update `backdrop_url` metadata
- [x] **Dispatch download job for `local_backdrop_path`** (if changed)

### ✅ Series Seasons (FIXED in this commit)
- [x] Update `name` metadata
- [x] Update `overview` metadata
- [x] Update `poster_path` metadata
- [x] Update `air_date` metadata
- [x] **Dispatch download job for `local_poster_path`** (if changed) ← **NEW!**

### ✅ Series Episodes (COMPLETE)
- [x] Update `name` metadata
- [x] Update `overview` metadata
- [x] Update `still_path` metadata
- [x] Update `air_date`, `runtime`, ratings metadata
- [x] **Dispatch download job for `local_still_path`** (if changed)

---

## Total Images Downloaded per Refresh TMDB

### For a Movie:
- **2 images**: poster + backdrop

### For a Series (with 3 seasons, 12 episodes per season):
- **Series**: 2 images (poster + backdrop)
- **Seasons**: 3 images (1 poster per season)
- **Episodes**: 36 images (1 still per episode × 36 episodes)
- **Total**: **41 images**

---

## Queue Worker Configuration

**Worker:** `worker-562248`
- Queue Connection: `database`
- Queue Name: `default`
- Processes: 2
- Timeout: 3600s (1 hour)
- Status: Must be running

**Job:** `DownloadTmdbImageJob`
- Timeout: 60 seconds per image
- Retries: 3 attempts
- Backoff: 10 seconds between retries

---

## Code Locations

### Job Dispatch
- **Movies**: `app/Services/ContentBulkOperationService.php` → `updateMovieFromTMDB()`
- **Series**: `app/Services/ContentBulkOperationService.php` → `updateSeriesFromTMDB()`
- **Seasons**: `app/Services/ContentBulkOperationService.php` → `refreshSeriesEpisodes()`
- **Episodes**: `app/Services/ContentBulkOperationService.php` → `refreshSeriesEpisodes()`

### Job Handler
- `app/Jobs/DownloadTmdbImageJob.php`

### Download Service
- `app/Services/TmdbImageDownloadService.php`

---

## Testing Checklist

### Movies
- [ ] Refresh movie → Check `local_poster_path` updated
- [ ] Refresh movie → Check `local_backdrop_path` updated
- [ ] Verify job dispatched in logs
- [ ] Verify image downloaded to `public/storage/movies/{tmdb_id}/`

### Series
- [ ] Refresh series → Check series `local_poster_path` updated
- [ ] Refresh series → Check series `local_backdrop_path` updated
- [ ] Refresh series → Check season `local_poster_path` updated (NEW!)
- [ ] Refresh series → Check episode `local_still_path` updated
- [ ] Verify all jobs dispatched in logs
- [ ] Verify images downloaded to correct paths

### Verification
- [ ] Check Laravel logs for "Dispatched poster download job"
- [ ] Check `jobs` table for DownloadTmdbImageJob entries
- [ ] Check `failed_jobs` table for any failures
- [ ] Check website renders new images (not old cached ones)

---

## Benefits of Local Storage

1. **Performance**: Serve images from own server (faster)
2. **Bandwidth**: No hotlinking TMDB (save their bandwidth)
3. **Reliability**: No dependency on TMDB uptime
4. **CDN Ready**: Can add CDN later without code changes
5. **Offline**: Images available even if TMDB down
6. **Control**: Can optimize/compress images if needed

---

## Troubleshooting

### Images not updating after Refresh TMDB
1. Check if queue worker is running: `supervisorctl status worker-562248`
2. Check Laravel logs for job dispatch: `grep "Dispatched.*download" storage/logs/laravel.log`
3. Check jobs table: `SELECT * FROM jobs WHERE queue = 'default'`
4. Check failed_jobs: `SELECT * FROM failed_jobs ORDER BY failed_at DESC`

### Worker not processing jobs
1. Restart worker: `supervisorctl restart worker-562248:*`
2. Check worker logs: `tail -f storage/logs/worker.log`
3. Check Forge daemon status

### Download job failing
1. Check TMDB API key configured: `php artisan tinker` → `config('services.tmdb.api_key')`
2. Check storage permissions: `ls -la public/storage`
3. Check disk space: `df -h`
4. Check network connectivity to TMDB

---

**Last Updated:** October 13, 2025
**Author:** AI Assistant
**Status:** ✅ Complete - All image types handled
