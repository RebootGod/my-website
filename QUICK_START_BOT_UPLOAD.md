# Quick Start Guide - Bot Upload System

## ⚡ 5-Minute Setup

### 1. Generate Token (30 seconds)
```bash
openssl rand -base64 32
```
Copy output: `abc123xyz789...`

---

### 2. Laravel .env (30 seconds)
```bash
nano .env
```
Add:
```env
TELEGRAM_BOT_TOKEN=abc123xyz789...
```
Save and exit.

---

### 3. Python Bot .env (1 minute)
```bash
cd noobz-bot
nano .env
```
Add:
```env
NOOBZ_API_URL=https://noobz.space
NOOBZ_BOT_TOKEN=abc123xyz789...
TELEGRAM_UPLOAD_WHITELIST=YOUR_TELEGRAM_USER_ID
```

**Get your Telegram User ID:**
1. Open Telegram
2. Search: `@userinfobot`
3. Send: `/start`
4. Copy your ID number

Save and exit.

---

### 4. Clear Laravel Cache (30 seconds)
```bash
cd /path/to/laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

### 5. Start Queue Worker (30 seconds)
```bash
php artisan queue:work --queue=bot-uploads --tries=3 --timeout=120 &
```

Or ensure Supervisor is running:
```bash
sudo supervisorctl status
```

---

### 6. Test Bot (1 minute)
```bash
cd noobz-bot
python main.py
```

In Telegram Saved Messages, send:
```
/uploadhelp
```

Should see help message with upload commands.

---

### 7. Test Upload (1 minute)
Try uploading a movie:
```
/uploadmovie 550
embed_url: https://example.com/embed/fight-club
```

Should get response: "✅ Movie upload queued successfully"

---

## 🎯 Commands Cheat Sheet

### Movie
```
/uploadmovie 12345
embed_url: https://...
download_url: https://...
```

### Series
```
/uploadseries 54321
```

### Season
```
/uploadseason 54321 S01
```

### Episode
```
/uploadepisode 54321 S01E05
embed_url: https://...
download_url: https://...
```

### Help
```
/uploadhelp
```

---

## 🔍 Troubleshooting

### "Unauthorized" Error
→ Add your Telegram user ID to whitelist in .env

### "401 Authentication Failed"
→ Check token matches in both Laravel and Bot .env

### "404 Series Not Found"
→ Upload series first: `/uploadseries <tmdb_id>`

### "422 Validation Failed"
→ Check URL uses HTTPS protocol

### "Upload queued" but nothing happens
→ Start queue worker: `php artisan queue:work`

---

## 📊 Monitor

### Check Queue
```bash
php artisan queue:monitor bot-uploads
```

### Check Logs
```bash
# Laravel
tail -f storage/logs/laravel.log

# Bot
tail -f noobz-bot/bot.log
```

### Check Failed Jobs
```bash
php artisan queue:failed
```

---

## 🚀 Deploy to VPS

### 1. Push Code
```bash
git add .
git commit -m "feat: Bot upload system"
git push origin main
```

### 2. Laravel Auto-deploys (Forge)
Wait for deployment notification.

### 3. Update Bot on VPS
```bash
ssh your-vps
cd /path/to/noobz-bot
git pull
nano .env  # Add tokens
sudo systemctl restart noobz-bot
sudo systemctl status noobz-bot
```

### 4. Test
Send `/uploadhelp` in Telegram.

---

## ✅ Done!

You can now upload movies/series via Telegram bot commands.

All uploads:
- Status: **draft**
- View in: Admin Panel
- Publish: Change status to "published"

---

**Questions?** Check BOT_UPLOAD_SYSTEM.md for full documentation.
