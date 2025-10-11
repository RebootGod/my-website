# Bot Upload System - Implementation Summary

## Overview
Telegram bot commands untuk upload Movies/Series ke Noobz website via TMDB API integration.

**Created:** October 11, 2025
**Status:** Development Complete - Ready for Testing
**Total Files Created:** 26 files (~5,800 lines)

---

## 📦 Architecture

### Laravel Backend API (15 files)

#### 1. Middleware (1 file)
- `app/Http/Middleware/AuthenticateTelegramBot.php` (75 lines)
  - Validates Bearer token from bot requests
  - Token must match TELEGRAM_BOT_TOKEN in .env
  - Uses hash_equals() for timing-safe comparison

#### 2. Form Requests (4 files, 475 lines)
- `app/Http/Requests/Bot/UploadMovieRequest.php` (126 lines)
- `app/Http/Requests/Bot/UploadSeriesRequest.php` (99 lines)
- `app/Http/Requests/Bot/UploadSeasonRequest.php` (114 lines)
- `app/Http/Requests/Bot/UploadEpisodeRequest.php` (136 lines)

**Validation:**
- TMDB ID: required integer
- URLs: HTTPS only, max 1000 characters
- Season/Episode numbers: validated ranges
- XSS protection via filter_var()

#### 3. Services (2 files, 545 lines)
- `app/Services/TmdbDataService.php` (265 lines)
  - Fetch data from TMDB API
  - Caching: 1 hour TTL
  - HTTP timeout: 30 seconds
  - Methods: fetchMovie(), fetchSeries(), fetchSeason(), fetchEpisode()

- `app/Services/ContentUploadService.php` (280 lines)
  - Duplicate checking by tmdb_id
  - Slug generation with uniqueness
  - Data preparation for all content types
  - Returns "what already exists" info for skip logic

#### 4. Queue Jobs (4 files, 626 lines)
- `app/Jobs/ProcessMovieUploadJob.php` (166 lines)
- `app/Jobs/ProcessSeriesUploadJob.php` (135 lines)
- `app/Jobs/ProcessSeasonUploadJob.php` (148 lines)
- `app/Jobs/ProcessEpisodeUploadJob.php` (177 lines)

**Configuration:**
- Queue: 'bot-uploads'
- Timeout: 120 seconds
- Retries: 3 attempts
- Backoff: 30 seconds
- Transaction-based with rollback

**Behavior:**
- Movie: Creates Movie + MovieSource (embed + download)
- Series: Creates Series ONLY (no seasons/episodes)
- Season: Creates Season ONLY (no episodes)
- Episode: Creates Episode with URLs

#### 5. Controllers (4 files, ~680 lines)
- `app/Http/Controllers/Api/Bot/BotMovieController.php` (134 lines)
- `app/Http/Controllers/Api/Bot/BotSeriesController.php` (122 lines)
- `app/Http/Controllers/Api/Bot/BotSeasonController.php` (158 lines)
- `app/Http/Controllers/Api/Bot/BotEpisodeController.php` (185 lines)

**Features:**
- Pre-upload duplicate checking
- UUID job tracking
- Detailed response messages
- HTTP 202 (Accepted) for queued jobs
- HTTP 200 for skipped (already exists)

#### 6. Routes
- `routes/api.php` (updated)

**API Endpoints:**
```php
POST /api/bot/movies
POST /api/bot/series
POST /api/bot/series/{tmdbId}/seasons
POST /api/bot/series/{tmdbId}/episodes
```

**Middleware:**
- auth.bot (Bearer token validation)
- throttle:100,1 (100 requests per minute)

#### 7. Configuration
- `bootstrap/app.php` - Registered auth.bot middleware
- `config/services.php` - Added telegram_bot.token config

---

### Python Bot Client (11 files)

#### 1. Config Files (2 files)
- `noobz-bot/config/auth_config.py` (118 lines)
  - Whitelist management
  - Authorization checking
  - Admin user tracking
  - Env: TELEGRAM_UPLOAD_WHITELIST, TELEGRAM_ADMIN_IDS

- `noobz-bot/config/api_config.py` (141 lines)
  - API connection settings
  - Endpoint URLs
  - Request configuration (timeout, retries)
  - Env: NOOBZ_API_URL, NOOBZ_BOT_TOKEN

#### 2. Services (3 files)
- `noobz-bot/services/noobz_api_client.py` (288 lines)
  - Async HTTP client (aiohttp)
  - Methods: upload_movie(), upload_series(), upload_season(), upload_episode()
  - Retry logic with exponential backoff
  - Timeout: 30 seconds (configurable)
  - Error handling for all HTTP status codes

- `noobz-bot/services/upload_validator.py` (236 lines)
  - Pre-API validation
  - TMDB ID format checking
  - URL validation (HTTPS requirement)
  - Season/episode number ranges
  - Helper methods for each upload type

- `noobz-bot/services/tmdb_fetch_service.py` (215 lines)
  - Lightweight TMDB API client
  - Existence checking before upload
  - Methods: check_movie_exists(), check_series_exists(), check_season_exists(), check_episode_exists()
  - Returns tuple: (exists, title/name)

#### 3. Utils (2 files)
- `noobz-bot/utils/upload_parser.py` (268 lines)
  - Parse message input from users
  - Extract TMDB ID from text or URL
  - Parse URLs, season/episode numbers
  - Support formats: "S01E05", "season: 1", "episode: 5"
  - TMDB URL pattern matching

- `noobz-bot/utils/upload_formatter.py` (256 lines)
  - Format response messages for Telegram
  - Success messages with emojis
  - Error messages with context
  - Help text generation
  - Skipped (already exists) messages

#### 4. Handlers (5 files)
- `noobz-bot/handlers/upload_movie_handler.py` (151 lines)
- `noobz-bot/handlers/upload_series_handler.py` (138 lines)
- `noobz-bot/handlers/upload_season_handler.py` (168 lines)
- `noobz-bot/handlers/upload_episode_handler.py` (195 lines)
- `noobz-bot/handlers/upload_help_handler.py` (44 lines)

**Handler Flow:**
1. Check authorization (whitelist)
2. Parse message input
3. Validate data
4. Check TMDB existence
5. Call API client
6. Handle response (success/error/skip)
7. Return formatted message

#### 5. Main Application
- `noobz-bot/main.py` (updated)
  - Registered 5 new handlers
  - Added user_id and username to parsed commands
  - Command routing for /uploadmovie, /uploadseries, /uploadseason, /uploadepisode, /uploadhelp

- `noobz-bot/utils/message_parser.py` (updated)
  - Added upload command parsing
  - New method: _parse_upload_command()

#### 6. Environment
- `noobz-bot/.env.example` (updated)
  - Added NOOBZ_API_URL, NOOBZ_BOT_TOKEN
  - Added TELEGRAM_UPLOAD_WHITELIST, TELEGRAM_ADMIN_IDS
  - Added API_REQUEST_TIMEOUT, API_MAX_RETRIES, API_RETRY_DELAY

---

## 🚀 Bot Commands

### /uploadmovie
**Format:**
```
/uploadmovie 12345
embed_url: https://example.com/embed/movie
download_url: https://example.com/download/movie
```

**Alternative format:**
```
/uploadmovie https://www.themoviedb.org/movie/12345
https://example.com/embed/movie
https://example.com/download/movie
```

**Behavior:**
- Creates Movie + MovieSource (embed + download)
- Status: draft
- Queue job for processing

---

### /uploadseries
**Format:**
```
/uploadseries 54321
```

**Alternative:**
```
/uploadseries https://www.themoviedb.org/tv/54321
```

**Behavior:**
- Creates Series ONLY
- NO seasons or episodes created
- User must upload seasons/episodes separately
- Status: draft

---

### /uploadseason
**Format:**
```
/uploadseason 54321 season: 1
```

**Alternative formats:**
```
/uploadseason 54321 S01
/uploadseason https://www.themoviedb.org/tv/54321 season: 1
```

**Behavior:**
- Creates Season ONLY
- NO episodes created
- User must upload episodes separately
- Requires series to exist first

---

### /uploadepisode
**Format:**
```
/uploadepisode 54321 S01E05
embed_url: https://example.com/embed/episode
download_url: https://example.com/download/episode
```

**Alternative formats:**
```
/uploadepisode 54321 season: 1 episode: 5
embed_url: https://example.com/embed/episode
```

**Behavior:**
- Creates Episode with URLs
- Requires series AND season to exist first
- Status: draft
- Queue job for processing

---

### /uploadhelp
**Format:**
```
/uploadhelp
```

**Behavior:**
- Displays help message with all command formats
- Shows examples and tips

---

## 🔐 Security

### Laravel API
- **Authentication:** Bearer token validation
  - Middleware: AuthenticateTelegramBot
  - Token from header: `Authorization: Bearer {token}`
  - Comparison: hash_equals() for timing safety

- **Authorization:** None (handled by bot)
  - Laravel accepts all authenticated bot requests
  - Bot handles user whitelist

- **Rate Limiting:** 100 requests/minute

- **Input Validation:**
  - FormRequest classes with strict rules
  - XSS prevention: filter_var(), strip_tags()
  - SQL injection prevention: Eloquent ORM

- **HTTPS Enforcement:**
  - All URLs must use HTTPS protocol
  - Validated in FormRequests

### Python Bot
- **Authorization:** Whitelist-based
  - Env: TELEGRAM_UPLOAD_WHITELIST (comma-separated user IDs)
  - Checked before processing any command
  - Unauthorized users receive error message

- **Input Validation:**
  - UploadValidator service validates all inputs
  - URL format checking
  - TMDB ID format checking
  - Range validation for season/episode numbers

- **TMDB Verification:**
  - All TMDB IDs verified before upload
  - Prevents uploading non-existent content

---

## 📊 Response Format

### Success Response (202 Accepted)
```json
{
  "success": true,
  "message": "Movie upload queued successfully",
  "skipped": false,
  "data": {
    "job_id": "uuid-here",
    "tmdb_id": 12345,
    "status": "queued",
    "queue": "bot-uploads"
  }
}
```

### Skipped Response (200 OK)
```json
{
  "success": true,
  "message": "Movie already exists in database",
  "skipped": true,
  "data": {
    "movie_id": 123,
    "title": "Movie Title",
    "year": 2024,
    "slug": "movie-title-2024",
    "status": "published",
    "tmdb_id": 12345
  }
}
```

### Error Response (422 Validation)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "embed_url": [
      "The embed url must be a valid URL.",
      "The embed url must use HTTPS protocol."
    ]
  }
}
```

### Error Response (404 Not Found)
```json
{
  "success": false,
  "message": "Series not found. Please upload the series first using /uploadseries",
  "error": "Series with TMDB ID 54321 does not exist"
}
```

---

## 🔧 Environment Variables

### Laravel (.env)
```env
# Must match Python bot token
TELEGRAM_BOT_TOKEN=your_secure_token_here

# TMDB API (already configured)
TMDB_API_KEY=your_tmdb_api_key
```

### Python Bot (.env)
```env
# Noobz API Configuration
NOOBZ_API_URL=https://noobz.space
NOOBZ_BOT_TOKEN=your_secure_token_here

# Must match Laravel token
# Generate: openssl rand -base64 32

# API Request Configuration (optional)
API_REQUEST_TIMEOUT=30
API_MAX_RETRIES=3
API_RETRY_DELAY=5

# Authorization Whitelist (required)
TELEGRAM_UPLOAD_WHITELIST=123456789,987654321
TELEGRAM_ADMIN_IDS=123456789
```

---

## 📝 Testing Checklist

### Laravel API Testing

- [ ] **Authentication**
  - [ ] Valid token → 202/200 response
  - [ ] Invalid token → 401 response
  - [ ] Missing token → 401 response

- [ ] **Movie Upload**
  - [ ] Valid movie → 202 (queued)
  - [ ] Duplicate movie → 200 (skipped)
  - [ ] Invalid TMDB ID → 422 (validation)
  - [ ] Non-HTTPS URL → 422 (validation)
  - [ ] Missing embed URL → 422 (validation)
  - [ ] Optional download URL works
  - [ ] Queue job processes correctly
  - [ ] Movie created as draft
  - [ ] MovieSource records created

- [ ] **Series Upload**
  - [ ] Valid series → 202 (queued)
  - [ ] Duplicate series → 200 (skipped)
  - [ ] No seasons/episodes created
  - [ ] Series created as draft

- [ ] **Season Upload**
  - [ ] Valid season → 202 (queued)
  - [ ] Duplicate season → 200 (skipped)
  - [ ] Series not found → 404
  - [ ] Invalid season number → 422
  - [ ] Season 0 (specials) works
  - [ ] No episodes created

- [ ] **Episode Upload**
  - [ ] Valid episode → 202 (queued)
  - [ ] Duplicate episode → 200 (skipped)
  - [ ] Series not found → 404
  - [ ] Season not found → 404
  - [ ] Invalid episode number → 422
  - [ ] Embed URL required → 422
  - [ ] Optional download URL works
  - [ ] Episode created as draft

- [ ] **Rate Limiting**
  - [ ] 100 requests/minute enforced
  - [ ] 429 response when exceeded

### Python Bot Testing

- [ ] **Authorization**
  - [ ] Whitelisted user can upload
  - [ ] Non-whitelisted user rejected
  - [ ] Unauthorized message displayed

- [ ] **Movie Upload**
  - [ ] Parse TMDB ID from number
  - [ ] Parse TMDB ID from URL
  - [ ] Parse embed URL
  - [ ] Parse download URL (optional)
  - [ ] Validate URLs (HTTPS)
  - [ ] Check TMDB existence
  - [ ] API call successful
  - [ ] Success message formatted
  - [ ] Skipped message formatted
  - [ ] Error message formatted

- [ ] **Series Upload**
  - [ ] Parse TMDB ID
  - [ ] Validate TMDB ID
  - [ ] Check TMDB existence
  - [ ] API call successful
  - [ ] Note about manual seasons displayed

- [ ] **Season Upload**
  - [ ] Parse TMDB ID and season number
  - [ ] Parse "S01" format
  - [ ] Parse "season: 1" format
  - [ ] Validate season number (0-100)
  - [ ] Check series exists in TMDB
  - [ ] Check season exists in TMDB
  - [ ] API call successful
  - [ ] Series not in DB error handled
  - [ ] Note about manual episodes displayed

- [ ] **Episode Upload**
  - [ ] Parse "S01E05" format
  - [ ] Parse "season: 1 episode: 5" format
  - [ ] Parse TMDB ID, URLs
  - [ ] Validate all inputs
  - [ ] Check series exists in TMDB
  - [ ] Check season exists in TMDB
  - [ ] Check episode exists in TMDB
  - [ ] API call successful
  - [ ] Series not in DB error handled
  - [ ] Season not in DB error handled

- [ ] **Help Command**
  - [ ] /uploadhelp displays help
  - [ ] Format examples shown
  - [ ] Tips displayed

### Integration Testing

- [ ] **Full Movie Flow**
  - [ ] Upload movie via bot
  - [ ] Verify queue job runs
  - [ ] Check movie in database
  - [ ] Check MovieSource records
  - [ ] Verify status = draft
  - [ ] Publish in admin panel
  - [ ] Verify visible on website

- [ ] **Full Series Flow**
  - [ ] Upload series via bot
  - [ ] Upload season via bot
  - [ ] Upload episode via bot
  - [ ] Verify queue jobs run
  - [ ] Check all records in database
  - [ ] Verify status = draft
  - [ ] Publish in admin panel
  - [ ] Verify visible on website

- [ ] **Error Handling**
  - [ ] Network timeout handled
  - [ ] API down handled
  - [ ] TMDB API down handled
  - [ ] Invalid JSON handled
  - [ ] Database error handled
  - [ ] Queue failure handled

---

## 🚀 Deployment Steps

### 1. Laravel Backend

```bash
# 1. Update .env
TELEGRAM_BOT_TOKEN=your_secure_token_here

# 2. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Run migrations (if any)
php artisan migrate

# 4. Test API endpoint
curl -X POST https://noobz.space/api/bot/movies \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{"tmdb_id": 12345, "embed_url": "https://example.com", "telegram_username": "test"}'

# 5. Start queue worker (or ensure supervisor is running)
php artisan queue:work --queue=bot-uploads --tries=3 --timeout=120

# 6. Monitor queue
php artisan queue:monitor bot-uploads

# 7. Commit and push
git add .
git commit -m "feat: Add Telegram bot upload system for movies/series"
git push origin main
```

### 2. Python Bot

```bash
# 1. Pull latest code
cd noobz-bot
git pull

# 2. Update .env
nano .env
# Add:
# NOOBZ_API_URL=https://noobz.space
# NOOBZ_BOT_TOKEN=your_secure_token_here
# TELEGRAM_UPLOAD_WHITELIST=your_telegram_user_id
# TELEGRAM_ADMIN_IDS=your_telegram_user_id

# 3. Install dependencies (if needed)
pip install -r requirements.txt

# 4. Test bot locally
python main.py

# 5. Test upload command
# Send in Telegram Saved Messages:
# /uploadhelp

# 6. Restart systemd service on VPS
sudo systemctl restart noobz-bot
sudo systemctl status noobz-bot

# 7. Check logs
sudo journalctl -u noobz-bot -f
```

### 3. Generate Secure Token

```bash
# Generate random token
openssl rand -base64 32

# Use same token in both:
# - Laravel: TELEGRAM_BOT_TOKEN
# - Python Bot: NOOBZ_BOT_TOKEN
```

### 4. Configure Whitelist

```bash
# Get your Telegram user ID
# 1. Send /start to @userinfobot
# 2. Copy your ID number
# 3. Add to .env:
TELEGRAM_UPLOAD_WHITELIST=123456789
```

---

## 📚 File Structure Summary

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Middleware/
│   │   │   └── AuthenticateTelegramBot.php (NEW)
│   │   ├── Controllers/Api/Bot/
│   │   │   ├── BotMovieController.php (NEW)
│   │   │   ├── BotSeriesController.php (NEW)
│   │   │   ├── BotSeasonController.php (NEW)
│   │   │   └── BotEpisodeController.php (NEW)
│   │   └── Requests/Bot/
│   │       ├── UploadMovieRequest.php (NEW)
│   │       ├── UploadSeriesRequest.php (NEW)
│   │       ├── UploadSeasonRequest.php (NEW)
│   │       └── UploadEpisodeRequest.php (NEW)
│   ├── Services/
│   │   ├── TmdbDataService.php (NEW)
│   │   └── ContentUploadService.php (NEW)
│   └── Jobs/
│       ├── ProcessMovieUploadJob.php (NEW)
│       ├── ProcessSeriesUploadJob.php (NEW)
│       ├── ProcessSeasonUploadJob.php (NEW)
│       └── ProcessEpisodeUploadJob.php (NEW)
├── bootstrap/
│   └── app.php (UPDATED)
├── config/
│   └── services.php (UPDATED)
└── routes/
    └── api.php (UPDATED)

noobz-bot/
├── config/
│   ├── auth_config.py (NEW)
│   └── api_config.py (NEW)
├── services/
│   ├── noobz_api_client.py (NEW)
│   ├── upload_validator.py (NEW)
│   └── tmdb_fetch_service.py (NEW)
├── utils/
│   ├── upload_parser.py (NEW)
│   ├── upload_formatter.py (NEW)
│   └── message_parser.py (UPDATED)
├── handlers/
│   ├── upload_movie_handler.py (NEW)
│   ├── upload_series_handler.py (NEW)
│   ├── upload_season_handler.py (NEW)
│   ├── upload_episode_handler.py (NEW)
│   └── upload_help_handler.py (NEW)
├── main.py (UPDATED)
└── .env.example (UPDATED)
```

---

## 💡 Usage Tips

### For Users

1. **Get TMDB ID:**
   - Visit themoviedb.org
   - Search for movie/series
   - Copy ID from URL: `/movie/12345` or `/tv/54321`
   - Or paste full URL in command

2. **Upload Movie:**
   - Need: TMDB ID + Embed URL
   - Optional: Download URL
   - Status: draft (publish in admin panel)

3. **Upload Series:**
   - Step 1: Upload series (creates series only)
   - Step 2: Upload seasons (creates season only)
   - Step 3: Upload episodes (creates episode with URLs)
   - Must follow order: series → season → episode

4. **Check Status:**
   - All uploads go to queue
   - Check admin panel for results
   - Status = draft by default

### For Developers

1. **Queue Processing:**
   - Queue: 'bot-uploads'
   - Ensure queue worker is running
   - Monitor with: `php artisan queue:monitor`

2. **Debugging:**
   - Laravel logs: `storage/logs/laravel.log`
   - Bot logs: `noobz-bot/bot.log`
   - Check queue failed jobs: `php artisan queue:failed`

3. **Extending:**
   - Add new endpoints in routes/api.php
   - Create new handlers in noobz-bot/handlers/
   - Register in main.py

4. **Security:**
   - Rotate token regularly
   - Update whitelist as needed
   - Monitor rate limiting

---

## 🎯 Success Criteria

✅ **Completed:**
- [x] Laravel API backend (15 files)
- [x] Python bot client (11 files)
- [x] Authentication & authorization
- [x] Input validation
- [x] Queue-based processing
- [x] Duplicate detection
- [x] Error handling
- [x] Response formatting
- [x] Documentation

🔄 **Next Steps:**
- [ ] Testing (see checklist above)
- [ ] Environment setup
- [ ] Token generation
- [ ] Whitelist configuration
- [ ] Deployment
- [ ] User documentation
- [ ] Commit & push

---

## 📞 Support

**Issues:**
- Check logs first (Laravel + Bot)
- Verify .env configuration
- Test API manually with curl
- Check queue worker status

**Common Errors:**
- 401 Unauthorized → Check token match
- 404 Not Found → Upload series/season first
- 422 Validation → Check URL format (HTTPS)
- Timeout → Check queue worker running

---

**End of Implementation Summary**
