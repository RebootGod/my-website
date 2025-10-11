# VPS Deployment Guide - Bot Upload System

## Setup Instructions

### SSH ke VPS
```bash
ssh forge@your-vps-ip
# atau
ssh forge@noobz.space
```

---

## Laravel Backend Setup

### 1. Add Token to Laravel .env
```bash
cd /home/forge/noobz.space
nano .env
```

Add this line (di bawah TMDB_API_KEY atau di bagian bawah):
```env
# Telegram Bot Token (untuk bot upload system)
TELEGRAM_BOT_TOKEN=qIWmjsXGt5TGX/TwryairVfDzSQgv2XhPeUptkLbKjk=
```

Save: `Ctrl+O`, Enter, `Ctrl+X`

### 2. Clear Laravel Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 3. Test API Endpoint
```bash
curl -X POST https://noobz.space/api/bot/movies \
  -H "Authorization: Bearer qIWmjsXGt5TGX/TwryairVfDzSQgv2XhPeUptkLbKjk=" \
  -H "Content-Type: application/json" \
  -d '{"tmdb_id": 550, "embed_url": "https://example.com", "telegram_username": "test"}'
```

Expected response: JSON with "success" or validation errors (bukan 401 Unauthorized)

### 4. Ensure Queue Worker Running
```bash
# Check supervisor status
sudo supervisorctl status

# If not running, start it
sudo supervisorctl start all

# Or restart
sudo supervisorctl restart all
```

---

## Python Bot Setup

### 1. Update Bot Code
```bash
cd ~/noobz-bot
git pull origin main
```

### 2. Backup Current .env
```bash
cp .env .env.backup.$(date +%Y%m%d)
```

### 3. Add New Environment Variables

**Option A: Edit manually**
```bash
nano .env
```

Add these lines at the end:
```env
# Noobz API Configuration (Bot Upload System)
NOOBZ_API_URL=https://noobz.space
NOOBZ_BOT_TOKEN=qIWmjsXGt5TGX/TwryairVfDzSQgv2XhPeUptkLbKjk=

# Upload Authorization (Telegram User IDs)
TELEGRAM_UPLOAD_WHITELIST=8008454014,8139108475
TELEGRAM_ADMIN_IDS=8008454014

# API Request Configuration (optional)
API_REQUEST_TIMEOUT=30
API_MAX_RETRIES=3
API_RETRY_DELAY=5
```

Save: `Ctrl+O`, Enter, `Ctrl+X`

**Option B: Append automatically**
```bash
cat >> .env << 'EOF'

# Noobz API Configuration (Bot Upload System)
NOOBZ_API_URL=https://noobz.space
NOOBZ_BOT_TOKEN=qIWmjsXGt5TGX/TwryairVfDzSQgv2XhPeUptkLbKjk=

# Upload Authorization (Telegram User IDs)
TELEGRAM_UPLOAD_WHITELIST=8008454014,8139108475
TELEGRAM_ADMIN_IDS=8008454014

# API Request Configuration (optional)
API_REQUEST_TIMEOUT=30
API_MAX_RETRIES=3
API_RETRY_DELAY=5
EOF
```

### 4. Verify .env File
```bash
cat .env
```

Check bahwa semua variable ada.

### 5. Restart Bot Service
```bash
sudo systemctl restart noobz-bot
```

### 6. Check Bot Status
```bash
sudo systemctl status noobz-bot
```

Should show: `Active: active (running)`

### 7. Monitor Logs
```bash
sudo journalctl -u noobz-bot -f
```

Look for:
- "Initializing upload handlers..."
- No errors during startup
- `Ctrl+C` to exit

---

## Testing

### 1. Test in Telegram

Open Telegram, go to **Saved Messages**, send:

```
/uploadhelp
```

Expected response: Help message dengan upload command formats

### 2. Test Movie Upload

```
/uploadmovie 550
embed_url: https://example.com/embed/fight-club
```

Expected response:
- ✅ Success: "Movie upload queued successfully" atau "Movie already exists"
- ❌ Error: Check logs

### 3. Check Laravel Logs (if errors)
```bash
cd /home/forge/noobz.space
tail -f storage/logs/laravel.log
```

### 4. Check Bot Logs (if errors)
```bash
cd ~/noobz-bot
tail -f bot.log
```

Or:
```bash
sudo journalctl -u noobz-bot -n 100
```

---

## Troubleshooting

### "Unauthorized" Error in Telegram
→ Your Telegram User ID not in whitelist
```bash
# Add your ID to .env
nano ~/noobz-bot/.env
# Add your ID to: TELEGRAM_UPLOAD_WHITELIST=8008454014,8139108475,YOUR_ID
sudo systemctl restart noobz-bot
```

### "401 Authentication Failed"
→ Token mismatch between Laravel and Bot
```bash
# Check Laravel token
grep TELEGRAM_BOT_TOKEN /home/forge/noobz.space/.env

# Check Bot token
grep NOOBZ_BOT_TOKEN ~/noobz-bot/.env

# They MUST match!
```

### "Queue not processing"
→ Queue worker not running
```bash
sudo supervisorctl status
sudo supervisorctl restart all

# Or manually start queue
cd /home/forge/noobz.space
php artisan queue:work --queue=bot-uploads --tries=3 --timeout=120 &
```

### Bot not starting
```bash
# Check logs
sudo journalctl -u noobz-bot -n 50

# Check service file
sudo systemctl cat noobz-bot

# Test run manually
cd ~/noobz-bot
python3 main.py
```

---

## Quick Commands Summary

```bash
# SSH
ssh forge@noobz.space

# Update Laravel
cd /home/forge/noobz.space
nano .env  # Add TELEGRAM_BOT_TOKEN
php artisan config:clear

# Update Bot
cd ~/noobz-bot
git pull
nano .env  # Add NOOBZ_BOT_TOKEN, TELEGRAM_UPLOAD_WHITELIST
sudo systemctl restart noobz-bot

# Check Status
sudo systemctl status noobz-bot
sudo journalctl -u noobz-bot -f

# Test in Telegram
/uploadhelp
```

---

## Environment Variables Summary

**Laravel `.env`:**
```env
TELEGRAM_BOT_TOKEN=qIWmjsXGt5TGX/TwryairVfDzSQgv2XhPeUptkLbKjk=
```

**Bot `.env`:**
```env
NOOBZ_API_URL=https://noobz.space
NOOBZ_BOT_TOKEN=qIWmjsXGt5TGX/TwryairVfDzSQgv2XhPeUptkLbKjk=
TELEGRAM_UPLOAD_WHITELIST=8008454014,8139108475
TELEGRAM_ADMIN_IDS=8008454014
```

---

**Done!** 🚀

If everything works, you should be able to upload movies/series via Telegram bot commands.
