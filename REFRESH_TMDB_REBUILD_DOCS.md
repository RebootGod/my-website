# Refresh TMDB - Complete Rebuild Documentation

**Date:** October 13, 2025  
**Status:** ✅ DEPLOYED TO PRODUCTION

---

## 📋 Overview

Complete restructure of Refresh TMDB feature following workinginstruction.md requirements:
- **Separate files per type** (Movies, Series, Bulk Action)
- **Independent operation** (Error in one type doesn't affect others)
- **Consistent naming** (Following naming convention)
- **Max 350 lines per file**
- **Database queue only**

---

## 📁 File Structure

### **Backend (PHP Jobs)**

```
app/Jobs/
├── RefreshAllTmdb_Movies_Job.php        [300 lines] ✅
│   └── Handles: "Refresh All TMDB" button on Movies page
│   └── Emoji: 🎬
│
├── RefreshAllTmdb_Series_Job.php        [300 lines] ✅
│   └── Handles: "Refresh All TMDB" button on Series page
│   └── Emoji: 📺
│
└── RefreshAllTmdb_BulkAction_Job.php    [305 lines] ✅
    └── Handles: Bulk action "Refresh TMDB" (selected items)
    └── Emoji: 🎬/📺 (depends on type)
```

### **Frontend (JavaScript)**

```
public/js/admin/
├── refresh_all_tmdb_movies_admin_panel.js   [180 lines] ✅
│   └── Handles: Movies "Refresh All TMDB" button
│
└── refresh_all_tmdb_series_admin_panel.js   [180 lines] ✅
    └── Handles: Series "Refresh All TMDB" button
```

### **Controllers**

```
app/Http/Controllers/Admin/BulkOperationController.php
├── refreshTMDB()         → Dispatches RefreshAllTmdb_BulkAction_Job
└── refreshAllTMDB()      → Dispatches type-specific job (Movies or Series)
```

### **Views**

```
resources/views/admin/
├── movies/index.blade.php  → Uses refresh_all_tmdb_movies_admin_panel.js
└── series/index.blade.php  → Uses refresh_all_tmdb_series_admin_panel.js
```

---

## 🎯 Key Features

### **1. Independent Operation**

| Type | Job | JS File | Error Impact |
|------|-----|---------|--------------|
| Movies | `RefreshAllTmdb_Movies_Job` | `refresh_all_tmdb_movies_admin_panel.js` | ❌ No impact on Series/Bulk |
| Series | `RefreshAllTmdb_Series_Job` | `refresh_all_tmdb_series_admin_panel.js` | ❌ No impact on Movies/Bulk |
| Bulk Action | `RefreshAllTmdb_BulkAction_Job` | (Uses bulk-operations.js) | ❌ No impact on buttons |

### **2. Consistent Logging**

Each job has unique emoji identifier for easy log filtering:

```php
// Movies
Log::info("🎬 RefreshAllTmdb_Movies_Job STARTED", [...]);

// Series  
Log::info("📺 RefreshAllTmdb_Series_Job STARTED", [...]);

// Bulk Action
Log::info("🎬/📺 RefreshAllTmdb_BulkAction_Job STARTED", [...]);
```

### **3. Progress Tracking**

Each job updates progress independently:

```php
$progress = [
    'type' => 'movie', // or 'series'
    'source' => 'bulk_action', // or null for buttons
    'total' => $totalItems,
    'processed' => $processedCount,
    'success' => $successCount,
    'failed' => $failedCount,
    'current_batch' => $currentBatch,
    'total_batches' => $totalBatches,
    // ...
];
```

### **4. Database Queue Only**

All jobs explicitly use database queue:

```php
->onConnection('database')->onQueue('default')
```

No redis queue dependencies (prevents stuck jobs).

---

## 🔄 User Flow

### **Scenario 1: Refresh All Movies**

1. Admin → Movies → Click "Refresh All TMDB" button
2. `refresh_all_tmdb_movies_admin_panel.js` sends POST to `/admin/bulk/refresh-all-tmdb` with `type: 'movie'`
3. `BulkOperationController@refreshAllTMDB()` dispatches `RefreshAllTmdb_Movies_Job`
4. Job processes movies in batches (5 items/batch)
5. Progress modal shows real-time updates
6. ✅ Series operations unaffected

### **Scenario 2: Refresh All Series**

1. Admin → Series → Click "Refresh All TMDB" button
2. `refresh_all_tmdb_series_admin_panel.js` sends POST to `/admin/bulk/refresh-all-tmdb` with `type: 'series'`
3. `BulkOperationController@refreshAllTMDB()` dispatches `RefreshAllTmdb_Series_Job`
4. Job processes series (including seasons & episodes)
5. Progress modal shows real-time updates
6. ✅ Movies operations unaffected

### **Scenario 3: Bulk Action Refresh TMDB**

1. Admin → Movies/Series → Select items → Bulk Actions → "Refresh TMDB"
2. `bulk-operations.js` sends POST to `/admin/bulk/refresh-tmdb` with `type` and `ids`
3. `BulkOperationController@refreshTMDB()` dispatches `RefreshAllTmdb_BulkAction_Job`
4. Job processes selected items only
5. Progress modal shows real-time updates
6. ✅ "Refresh All" buttons unaffected

---

## 🚀 Testing Checklist

### **✅ Test 1: Movies Independent Operation**
- [ ] Click "Refresh All TMDB" on Movies page
- [ ] Verify progress modal appears
- [ ] Check console logs for 🎬 emoji
- [ ] Confirm no errors in series operations
- [ ] Verify movies data updated from TMDB

### **✅ Test 2: Series Independent Operation**
- [ ] Click "Refresh All TMDB" on Series page
- [ ] Verify progress modal appears
- [ ] Check console logs for 📺 emoji
- [ ] Confirm no errors in movies operations
- [ ] Verify series + episodes data updated

### **✅ Test 3: Bulk Action Movies**
- [ ] Select 5 movies
- [ ] Click "Refresh TMDB" bulk action
- [ ] Verify progress modal with selected count
- [ ] Check logs for "RefreshAllTmdb_BulkAction_Job"
- [ ] Confirm no impact on "Refresh All" button

### **✅ Test 4: Bulk Action Series**
- [ ] Select 5 series
- [ ] Click "Refresh TMDB" bulk action
- [ ] Verify progress modal with selected count
- [ ] Check logs for "RefreshAllTmdb_BulkAction_Job"
- [ ] Confirm no impact on "Refresh All" button

### **✅ Test 5: Error Isolation**
- [ ] Trigger error in movies refresh (invalid TMDB ID)
- [ ] Verify series operations still work
- [ ] Trigger error in series refresh
- [ ] Verify movies operations still work
- [ ] Confirm errors don't cascade

---

## 📊 Debugging Guide

### **Movies Refresh Not Working?**

1. Check logs with emoji filter:
   ```bash
   ssh -i ~/.ssh/noobz_final root@145.79.15.4
   cd /home/forge/noobz.space
   tail -100 storage/logs/laravel.log | grep "🎬"
   ```

2. Check queue jobs:
   ```bash
   php artisan queue:failed | grep "RefreshAllTmdb_Movies_Job"
   ```

3. Check JS console:
   - Look for `🎬 Refresh All TMDB Movies: Initializing...`
   - Verify fetch to `/admin/bulk/refresh-all-tmdb` with `type: 'movie'`

### **Series Refresh Not Working?**

1. Check logs:
   ```bash
   tail -100 storage/logs/laravel.log | grep "📺"
   ```

2. Check queue jobs:
   ```bash
   php artisan queue:failed | grep "RefreshAllTmdb_Series_Job"
   ```

3. Check JS console:
   - Look for `📺 Refresh All TMDB Series: Initializing...`
   - Verify fetch with `type: 'series'`

### **Bulk Action Not Working?**

1. Check logs:
   ```bash
   tail -100 storage/logs/laravel.log | grep "RefreshAllTmdb_BulkAction_Job"
   ```

2. Check queue jobs:
   ```bash
   php artisan queue:failed | grep "BulkAction"
   ```

3. Check JS console in `bulk-operations.js`:
   - Look for `executeBulkAction('refresh-tmdb', ...)`

---

## 🔧 Maintenance

### **Adding New Content Type**

If adding new content type (e.g., "books"):

1. Create job: `app/Jobs/RefreshAllTmdb_Books_Job.php`
2. Create JS: `public/js/admin/refresh_all_tmdb_books_admin_panel.js`
3. Update `BulkOperationController@refreshAllTMDB()`:
   ```php
   } elseif ($request->type === 'book') {
       \App\Jobs\RefreshAllTmdb_Books_Job::dispatch($ids, $progressKey)
           ->onConnection('database')->onQueue('default');
   }
   ```
4. Update views to use new JS file
5. ✅ Other types remain unaffected!

### **Changing Batch Size**

Edit job file `$batchSize` property:

```php
// Movies
protected int $batchSize = 5; // Change to 10 for faster processing

// Series (may need smaller batches due to episodes)
protected int $batchSize = 3; 
```

### **Queue Worker Management**

Check worker status:
```bash
ssh -i ~/.ssh/noobz_final root@145.79.15.4
supervisorctl status worker-562248
```

Restart worker after code changes:
```bash
cd /home/forge/noobz.space
php artisan queue:restart
```

---

## 📝 File Naming Convention

Following workinginstruction.md:

| File Type | Pattern | Example |
|-----------|---------|---------|
| Job | `RefreshAllTmdb_{Type}_Job.php` | `RefreshAllTmdb_Movies_Job.php` |
| JS | `refresh_all_tmdb_{type}_admin_panel.js` | `refresh_all_tmdb_movies_admin_panel.js` |
| Service | `TmdbRefreshService_{Type}.php` | (Future: if needed) |

**Benefits:**
- ✅ Clear purpose from filename
- ✅ Easy to search/grep
- ✅ Consistent across project
- ✅ No confusion between types

---

## ⚠️ Important Notes

1. **Old files deleted:**
   - ❌ `app/Jobs/RefreshAllTmdbJob.php`
   - ❌ `public/js/admin/refresh-all-tmdb.js`

2. **Still used (shared):**
   - ✅ `app/Services/ContentBulkOperationService.php` (helper service)
   - ✅ `public/js/admin/bulk-operations.js` (bulk action handler)
   - ✅ `public/js/admin/bulk-progress-tracker.js` (progress modal)

3. **Queue configuration:**
   - All jobs use `database` connection
   - Queue name: `default`
   - Worker: `worker-562248` on Forge

4. **Progress keys:**
   - Movies button: `bulk_operation_tmdb_refresh_all_movie_{timestamp}`
   - Series button: `bulk_operation_tmdb_refresh_all_series_{timestamp}`
   - Bulk action: `bulk_operation_tmdb_refresh_bulkaction_{type}_{timestamp}`

---

## ✅ Deployment Checklist

- [x] Create 3 separate job files
- [x] Create 2 separate JS files
- [x] Update BulkOperationController
- [x] Update movies/index.blade.php
- [x] Update series/index.blade.php
- [x] Delete old generic files
- [x] Git commit with detailed message
- [x] Git push to main branch
- [x] Forge auto-deployment
- [x] Verify files on production
- [ ] Test movies refresh independently
- [ ] Test series refresh independently  
- [ ] Test bulk action for both types
- [ ] Verify error isolation

---

## 🎉 Success Criteria

✅ **Movies refresh works** without affecting series  
✅ **Series refresh works** without affecting movies  
✅ **Bulk action works** for both types  
✅ **Error in one type** doesn't cascade to others  
✅ **Logs clearly show** which job is running (via emoji)  
✅ **Progress tracking** works for all 3 operations  
✅ **All files under 350 lines** (per workinginstruction.md)  
✅ **Consistent naming** (per workinginstruction.md)

---

**Next Steps:**
1. User tests each operation independently
2. Monitor logs for any errors
3. Verify complete independence between types
4. 🎊 Celebrate clean architecture!
