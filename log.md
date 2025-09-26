# Development Log - Noobz Cinema

## 2025-09-26 - User Activity Service Login Fix

### Issue Identified
- **Problem**: 500 Server Error pada saat login sebagai admin
- **Error Message**: `UserActivityService::logActivity(): Argument #1 ($userId) must be of type int, null given`
- **Root Cause**: Method `logActivity()` expects integer `$userId` but `logFailedLogin()` passes `null`

### Investigation Results
1. **Error Location**: `UserActivityService.php:26` - `logActivity()` method signature
2. **Trigger Point**: `UserActivityService.php:74` - `logFailedLogin()` method calling `logActivity(null, ...)`
3. **Call Stack**: LoginController calls `logFailedLogin()` for various failure scenarios (user not found, account suspended, wrong password)

### Solution Implemented

#### File Modified: `app/Services/UserActivityService.php`
**BEFORE (Type Error)**:
```php
public function logActivity(
    int $userId,  // <- Strict type, cannot accept null
    string $activityType,
    string $description,
    // ...
) {
    return UserActivity::create([
        'user_id' => $userId,
        // ...
    ]);
}
```

**AFTER (Nullable Fix)**:
```php
public function logActivity(
    ?int $userId, // <- Now accepts null for failed login attempts
    string $activityType,
    string $description,
    // ...
) {
    return UserActivity::create([
        'user_id' => $userId,
        // ...
    ]);
}
```

#### Database Migration Created: `2025_09_26_040217_make_user_id_nullable_in_user_activities_table.php`
**Migration Changes**:
1. Drop existing foreign key constraint on `user_id`
2. Make `user_id` field nullable to support failed login entries
3. Re-add foreign key constraint with nullable support

```php
public function up(): void
{
    Schema::table('user_activities', function (Blueprint $table) {
        // Drop foreign key constraint first
        $table->dropForeign(['user_id']);
        // Make user_id nullable
        $table->foreignId('user_id')->nullable()->change();
        // Re-add foreign key constraint with nullable
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
}
```

### Technical Impact
1. **Failed Login Tracking**: Now properly logs failed attempts with `user_id = null`
2. **Security Audit**: Maintains security logging for suspicious activities
3. **Database Integrity**: Preserves relational integrity with nullable foreign key
4. **No Breaking Changes**: Existing functionality remains intact

### Fixed Login Scenarios
- ✅ **User Not Found**: `logFailedLogin()` can log with null user_id
- ✅ **Account Suspended**: Failed login attempts properly recorded
- ✅ **Wrong Password**: Tracking works for authentication failures
- ✅ **Successful Login**: Normal login flow unaffected

## 2025-09-25 - Sort By Functionality Fix

### Issue Identified
- **Problem**: Sort By dropdown pada Homepage tidak berfungsi
- **User Report**: Fitur Sort By tidak mengubah urutan konten meskipun dropdown sudah dipilih
- **Root Cause**: HomeController menerapkan sorting pada query movies tapi kemudian di-override dengan hardcoded `sortByDesc('created_at')`

### Investigation Results
1. **View Layer**: Sort dropdown di `home.blade.php` sudah implementasi dengan benar (lines 80-89)
   - Options: Latest Added, Oldest First, Highest Rated, Lowest Rated, A-Z
   - Form submission berfungsi normal dengan parameter `sort`

2. **Controller Issue**: `HomeController::index()` bug critical di line 74-124
   - Sorting diterapkan ke `$query` movies (lines 74-92) ✅
   - Tapi kemudian di-override dengan hardcoded `->sortByDesc('created_at')` (line 124) ❌
   - Merged collection mengabaikan user selection

### Solution Implemented

#### File Modified: `app/Http/Controllers/HomeController.php`

**BEFORE (Broken Logic)**:
```php
// SORT OPTIONS applied to movie query
$sortBy = $request->get('sort', 'latest');
switch ($sortBy) {
    case 'oldest': $query->oldest(); break;
    case 'rating_high': $query->orderBy('rating', 'desc'); break;
    // ... other options
}

// Get movies and series
$movies = $query->get();
$series = $seriesQuery->get();

// BUG: Hardcoded sorting ignoring user selection
$merged = $movies->concat($series)->sortByDesc('created_at')->values();
```

**AFTER (Fixed Logic)**:
```php
// Get movies with filters (no sorting yet)
$movies = $query->get();
$series = $seriesQuery->get();

// Merge first, then apply user-selected sorting
$merged = $movies->concat($series);

// SORT OPTIONS for merged collection - RESPECTS USER CHOICE
$sortBy = $request->get('sort', 'latest');
switch ($sortBy) {
    case 'oldest': $merged = $merged->sortBy('created_at')->values(); break;
    case 'rating_high': $merged = $merged->sortByDesc('rating')->values(); break;
    case 'rating_low': $merged = $merged->sortBy('rating')->values(); break;
    case 'alphabetical': $merged = $merged->sortBy('title')->values(); break;
    case 'latest':
    default: $merged = $merged->sortByDesc('created_at')->values(); break;
}
```

### Technical Changes

#### Code Structure Improvement:
1. **Moved sorting logic** after collection merge (lines 107-125)
2. **Applied sorting to merged collection** instead of individual queries
3. **Preserved user sort selection** throughout the entire process
4. **Added `->values()`** to reindex collection after sorting

#### Sorting Options Fixed:
- ✅ **Latest Added** (`latest`): `sortByDesc('created_at')`
- ✅ **Oldest First** (`oldest`): `sortBy('created_at')`
- ✅ **Highest Rated** (`rating_high`): `sortByDesc('rating')`
- ✅ **Lowest Rated** (`rating_low`): `sortBy('rating')`
- ✅ **A-Z** (`alphabetical`): `sortBy('title')`

### Testing Verification

#### Manual Testing Results:
1. ✅ **Latest Added**: Content sorted by newest `created_at` first
2. ✅ **Oldest First**: Content sorted by oldest `created_at` first
3. ✅ **Highest Rated**: Content sorted by highest `rating` first
4. ✅ **Lowest Rated**: Content sorted by lowest `rating` first
5. ✅ **A-Z**: Content sorted alphabetically by `title`
6. ✅ **Pagination**: Maintains sort order across paginated pages
7. ✅ **Combined Content**: Movies and TV series properly sorted together

#### PHP Syntax Check:
```bash
php -l app/Http/Controllers/HomeController.php
# Result: No syntax errors detected
```

### Performance Impact
- **Positive**: No additional database queries
- **Neutral**: Collection sorting overhead minimal for typical dataset sizes
- **Maintained**: All existing caching mechanisms preserved

### Architecture Consistency
- **Filter Logic**: Maintained consistent filtering for both movies and series
- **Pagination**: LengthAwarePaginator continues to work properly
- **Caching**: All existing cache strategies remain functional
- **View Layer**: No changes required to template files

### User Experience Improvement
**Before Fix**: User selects "Highest Rated" → Content still shows in Latest Added order
**After Fix**: User selects "Highest Rated" → Content properly sorted by rating DESC

## Previous Updates

### 2025-09-25 - Favicon Implementation
- Added custom Noobz Cinema favicon from GitHub repository
- Updated app.blade.php and admin.blade.php with favicon links
- Cross-platform compatibility (desktop + mobile browsers)

### 2025-09-25 - Series Tracking Implementation
- Fixed Series Watched statistics tracking
- Added SeriesEpisodeView::logView() method
- Enhanced UserActivityService for dual logging
- Added AJAX episode view tracking endpoint

### 2025-09-25 - Movie View Tracking Fix
- Fixed Total Views and Movies Watched statistics
- Enhanced UserActivityService::logMovieWatch()
- Added comprehensive movie view tracking
- Implemented AJAX view duration tracking

### 2025-09-25 - Security Implementation
- Added NoXssRule and NoSqlInjectionRule validation
- Enhanced login and registration form security
- Implemented timing attack protection
- Added failed login attempt logging system