# Database Structure - Noobz Cinema

## Overview

**Noobz Cinema** Laravel 12.x movie streaming platform database structure overview focusing on user statistics tracking and movie viewing analytics.

## Key Tables for User Statistics

### movie_views
Primary table for tracking user movie viewing history and statistics calculation.

```sql
CREATE TABLE movie_views (
    id bigint PRIMARY KEY AUTO_INCREMENT,
    user_id bigint NOT NULL,
    movie_id bigint NOT NULL,
    watched_at timestamp NOT NULL,
    watch_duration int NULL,
    ip_address varchar(45) NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (movie_id) REFERENCES movies(id),

    INDEX idx_user_movie (user_id, movie_id),
    INDEX idx_watched_at (watched_at),
    INDEX idx_user_watched (user_id, watched_at)
);
```

### series_episode_views
Tracking TV series episode views for series statistics.

```sql
CREATE TABLE series_episode_views (
    id bigint PRIMARY KEY AUTO_INCREMENT,
    user_id bigint NOT NULL,
    episode_id bigint NOT NULL,
    watched_at timestamp NOT NULL,
    watch_duration int NULL,
    ip_address varchar(45) NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (episode_id) REFERENCES series_episodes(id),

    INDEX idx_user_episode (user_id, episode_id),
    INDEX idx_watched_at (watched_at)
);
```

### user_activities
Comprehensive user activity logging for admin analytics.

```sql
CREATE TABLE user_activities (
    id bigint PRIMARY KEY AUTO_INCREMENT,
    user_id bigint NOT NULL,
    activity_type varchar(50) NOT NULL,
    description text NOT NULL,
    metadata json NULL,
    ip_address varchar(45) NULL,
    user_agent text NULL,
    activity_at timestamp NOT NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,

    FOREIGN KEY (user_id) REFERENCES users(id),

    INDEX idx_user_activity (user_id, activity_type),
    INDEX idx_activity_date (activity_at),
    INDEX idx_activity_type (activity_type)
);
```

## Statistics Calculation Queries

### User Statistics (UserStatsService)

**Total Views**: Count all movie views by user
```sql
SELECT COUNT(*) FROM movie_views WHERE user_id = ?
```

**Movies Watched**: Count unique movies viewed by user
```sql
SELECT COUNT(DISTINCT movie_id) FROM movie_views WHERE user_id = ?
```

**Series Watched**: Count unique series via episode views
```sql
SELECT COUNT(DISTINCT series_episodes.series_id)
FROM series_episode_views
JOIN series_episodes ON series_episode_views.episode_id = series_episodes.id
WHERE series_episode_views.user_id = ?
```

**Invite Codes Created**: Count invite codes created by user
```sql
SELECT COUNT(*) FROM invite_codes WHERE created_by = ?
```

## Key Relationships

- **Users** � **MovieViews**: One-to-many (user viewing history)
- **Movies** � **MovieViews**: One-to-many (movie popularity tracking)
- **Users** � **UserActivities**: One-to-many (comprehensive activity log)
- **Users** � **InviteCodes**: One-to-many (invitation management)
- **SeriesEpisodes** � **SeriesEpisodeViews**: One-to-many (episode tracking)

## Performance Optimizations

- **Database Indexes**: Strategic indexing on user_id, movie_id, watched_at columns
- **Redis Caching**: 5-minute TTL for user statistics
- **Query Optimization**: Separate queries instead of joins for accuracy
- **Eager Loading**: Prevents N+1 queries in statistics calculation

## Recent Updates (2025-09-25)

### Movie View Tracking Fixes
- **Issue Found**: Movie views were not being recorded to `movie_views` table
- **Root Cause**: Only `user_activities` was being updated, not `movie_views`
- **Solution Applied**:
  - Updated `UserActivityService::logMovieWatch()` to call `MovieView::logView()`
  - Added movie view tracking to `MovieController::play()` method
  - Created new `MovieController::trackView()` AJAX endpoint for accurate tracking
  - Added route `/movie/{movie}/track-view` for AJAX view tracking

### Implementation Changes
1. **UserActivityService.php**: Added `MovieView::logView()` and `movie->increment('view_count')`
2. **MovieController.php**: Added immediate view tracking in `play()` method
3. **MovieController.php**: Added `trackView()` method for AJAX tracking with duplicate prevention
4. **routes/web.php**: Added routes for view tracking and issue reporting

This ensures accurate user statistics calculation for:
- Total Views (from movie_views table)
- Movies Watched (unique movies from movie_views)
- Series Watched (unique series from series_episode_views)
- Invites Created (from invite_codes table)

## Data Flow

1. **User watches movie** � `MovieController::show()` logs to `user_activities`
2. **User plays movie** � `MovieController::play()` logs to `movie_views` + increments view count
3. **AJAX tracking** � `MovieController::trackView()` provides accurate duration tracking
4. **Statistics calculation** � `UserStatsService::getUserStats()` queries all relevant tables
5. **Admin display** � User details page shows calculated statistics with 5-minute cache

The database structure now properly supports comprehensive user statistics tracking with accurate movie view recording and efficient query performance.

---

## Series Tracking Fix Update (2025-09-25)

### Issue Resolved: Series Watched Statistics
**Problem**: Series Watched showing 0 despite users watching episodes
**Root Cause**: `SeriesPlayerController::playEpisode()` was not logging episode views to `series_episode_views` table

### Implementation Fix:

#### 1. Enhanced SeriesEpisodeView Model
```php
// Added static method for consistent tracking
public static function logView($episodeId, $userId = null)
{
    return self::create([
        'episode_id' => $episodeId,
        'user_id' => $userId ?? auth()->id(),
        'viewed_at' => now(),
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent()
    ]);
}
```

#### 2. Updated Series Data Flow
**New Series Tracking Flow**:
1. **User watches episode** → `SeriesPlayerController::playEpisode()`
2. **Episode view logged** → `SeriesEpisodeView::logView()` creates record
3. **Series activity logged** → `UserActivityService::logSeriesWatch()` with episodeId
4. **AJAX tracking** → `SeriesPlayerController::trackEpisodeView()` for duration
5. **Statistics calculation** → Queries `series_episode_views` for unique series count

#### 3. Database Impact
**Query Results After Fix**:
```sql
-- Series Watched now returns accurate count
SELECT COUNT(DISTINCT series_episodes.series_id)
FROM series_episode_views
JOIN series_episodes ON series_episode_views.episode_id = series_episodes.id
WHERE series_episode_views.user_id = ?
-- Result: Actual unique series count instead of 0
```

**Files Updated**:
- `app/Models/SeriesEpisodeView.php` - Added `logView()` method
- `app/Http/Controllers/SeriesPlayerController.php` - Added episode tracking and AJAX endpoint
- `app/Services/UserActivityService.php` - Enhanced series watch logging
- `routes/web.php` - Added `/series/{series}/episode/{episode}/track-view` route

**Result**: Series Watched statistics now accurately reflect user viewing behavior.