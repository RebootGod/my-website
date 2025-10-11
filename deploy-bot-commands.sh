#!/bin/bash
# Commands to update noobz-bot on VPS

# 1. Update bot code from GitHub
cd ~/noobz-bot
git pull origin main

# 2. Backup existing .env
cp .env .env.backup

# 3. Add new environment variables to .env
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

# 4. Show updated .env
echo "=== Updated .env file ==="
cat .env

# 5. Restart bot service
sudo systemctl restart noobz-bot

# 6. Check bot status
sudo systemctl status noobz-bot

# 7. View logs
echo ""
echo "=== Bot Logs (Ctrl+C to exit) ==="
sudo journalctl -u noobz-bot -f
