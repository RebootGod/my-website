# Cookie Security Configuration for Production

## 🔒 Critical Security ENV Variables for Laravel Forge

### **REQUIRED for HttpOnly & Secure Cookie Flags**

Add these environment variables to Laravel Forge dashboard:

```bash
# ========================================
# SESSION SECURITY CONFIGURATION
# ========================================

# Force HTTPS-only cookies (CRITICAL for production)
SESSION_SECURE_COOKIE=true

# Prevent JavaScript access to cookies (XSS protection)
SESSION_HTTP_ONLY=true

# Strict same-site policy (CSRF protection)
SESSION_SAME_SITE=strict

# Session configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

# Application environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://noobz.space

# ========================================
# ADDITIONAL SECURITY HEADERS
# ========================================

# These are already configured in code but verify:
# - Session cookies: laravel_session (auto HttpOnly + Secure)
# - XSRF-TOKEN: Laravel CSRF token (auto HttpOnly + Secure)
# - remember_web_* : Remember me token (auto HttpOnly + Secure)
```

---

## 📋 How to Apply in Laravel Forge

### **Step 1: Login to Laravel Forge**
https://forge.laravel.com

### **Step 2: Select noobz.space Site**

### **Step 3: Navigate to Environment**
Click on **"Environment"** tab in site dashboard

### **Step 4: Add/Update ENV Variables**

Find these lines in your `.env` editor and update/add:

```bash
# Search for existing SESSION variables and update them:
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Verify APP_ENV is set to production:
APP_ENV=production
APP_DEBUG=false
```

### **Step 5: Save Changes**
Click **"Save"** button at bottom

### **Step 6: Restart PHP-FPM**
Laravel Forge will show option to restart - click it, or run:
```bash
# This happens automatically after saving ENV
```

---

## 🧪 Verification After Deployment

### **Test 1: Check Cookie Headers with Browser DevTools**

1. Open https://noobz.space in Chrome/Firefox
2. Press **F12** to open DevTools
3. Go to **Application** tab (Chrome) or **Storage** tab (Firefox)
4. Click **Cookies** → `https://noobz.space`
5. Check each cookie:

**Expected Cookie Attributes:**

| Cookie Name | HttpOnly | Secure | SameSite |
|-------------|----------|--------|----------|
| `laravel_session` | ✅ Yes | ✅ Yes | ✅ Strict |
| `XSRF-TOKEN` | ⚠️ No* | ✅ Yes | ✅ Strict |
| `remember_web_*` | ✅ Yes | ✅ Yes | ✅ Strict |

*Note: XSRF-TOKEN intentionally NOT HttpOnly (needs to be read by JavaScript for AJAX requests)

---

### **Test 2: cURL Header Check**

```bash
curl -I https://noobz.space/login

# Look for Set-Cookie headers:
# Set-Cookie: laravel_session=...; path=/; secure; HttpOnly; SameSite=Strict
# Set-Cookie: XSRF-TOKEN=...; path=/; secure; SameSite=Strict
```

---

### **Test 3: Security Headers Test (securityheaders.com)**

Visit: https://securityheaders.com/?q=https://noobz.space

Should show:
- ✅ Cookies with HttpOnly flag
- ✅ Cookies with Secure flag  
- ✅ Cookies with SameSite=Strict

---

## 🛡️ Security Impact

### **What These Flags Protect Against:**

**HttpOnly Flag:**
- ✅ Prevents XSS attacks from stealing session cookies
- ✅ JavaScript cannot access `document.cookie` for HttpOnly cookies
- ✅ Mitigates session hijacking via XSS

**Secure Flag:**
- ✅ Cookies only transmitted over HTTPS
- ✅ Prevents man-in-the-middle (MITM) attacks
- ✅ Protects against network sniffing

**SameSite=Strict:**
- ✅ Prevents CSRF attacks
- ✅ Cookies not sent on cross-site requests
- ✅ Additional layer beyond CSRF tokens

---

## 📊 Cookie Configuration Summary

| Cookie | Purpose | HttpOnly | Secure | SameSite | Expires |
|--------|---------|----------|--------|----------|---------|
| `laravel_session` | User session | ✅ Yes | ✅ Yes | Strict | 2 hours |
| `XSRF-TOKEN` | CSRF protection | ❌ No | ✅ Yes | Strict | 2 hours |
| `remember_web_*` | Remember me | ✅ Yes | ✅ Yes | Strict | 5 years |

---

## 🚨 Common Issues & Troubleshooting

### **Issue 1: Cookies Still Not Secure After ENV Update**

**Solution:**
```bash
# SSH into server via Laravel Forge
cd /home/forge/noobz.space

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Restart PHP-FPM
# (Do this via Forge dashboard)
```

---

### **Issue 2: "Secure" Flag Not Working**

**Cause:** Not using HTTPS or `APP_URL` not set to https://

**Solution:**
```bash
# In .env file:
APP_URL=https://noobz.space  # NOT http://
SESSION_SECURE_COOKIE=true
```

---

### **Issue 3: Users Getting Logged Out Frequently**

**Cause:** Too strict SameSite policy or session lifetime too short

**Solution:**
```bash
# Increase session lifetime if needed:
SESSION_LIFETIME=240  # 4 hours instead of 2

# Or adjust SameSite (less secure):
SESSION_SAME_SITE=lax  # Instead of strict (not recommended)
```

---

## 🔍 Code References

Configuration files that control cookie security:

1. **config/session.php** (Lines 172-203)
   - `secure` flag configuration
   - `http_only` flag configuration
   - `same_site` policy configuration

2. **config/sanctum.php** (Lines 75-77)
   - Uses `EncryptCookies` middleware
   - Inherits session cookie settings

3. **app/Http/Kernel.php** (Lines 30-36)
   - `EncryptCookies` middleware in web group
   - `StartSession` middleware

---

## ✅ Compliance Status

After applying these configurations:

**OWASP Top 10 2024/2025:**
- ✅ A01:2021 - Broken Access Control (SameSite protection)
- ✅ A03:2021 - Injection (HttpOnly prevents XSS cookie theft)
- ✅ A05:2021 - Security Misconfiguration (Secure flags enforced)
- ✅ A07:2021 - Identification and Authentication Failures (Session protection)

**PCI DSS Requirements:**
- ✅ Requirement 6.5.10: Broken Authentication and Session Management
- ✅ Requirement 4.1: Use strong cryptography (Secure flag + HTTPS)

---

## 📅 Last Updated

October 9, 2025

## 🔗 References

- [Laravel Session Configuration](https://laravel.com/docs/11.x/session)
- [OWASP Secure Cookie Attribute](https://owasp.org/www-community/controls/SecureCookieAttribute)
- [MDN Set-Cookie HttpOnly](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie)
- [SameSite Cookie Explained](https://web.dev/samesite-cookies-explained/)
