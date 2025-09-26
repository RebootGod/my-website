# Hostinger Email Configuration Checklist

## Error: `535 5.7.8 Error: authentication failed`

### Possible Causes & Solutions:

## 1. 🔐 **Password Issues**
**Check:**
- [ ] Is `MAIL_PASSWORD="secret"` the actual password?
- [ ] Try the real email account password
- [ ] Check for special characters that need escaping

**Fix:**
```bash
# Update in Laravel Forge Environment
MAIL_PASSWORD="YourRealPasswordHere"
```

## 2. 👤 **Username Format Issues**
**Current:** `admin@hahacosmos.xyz`

**Try these alternatives:**
- [ ] Full email: `admin@hahacosmos.xyz`
- [ ] Username only: `admin`
- [ ] From address: `noobz@hahacosmos.xyz`

## 3. 🌐 **Hostinger Account Setup**
**Verify in Hostinger Panel:**
- [ ] Domain `hahacosmos.xyz` is added and verified
- [ ] Email account `admin@hahacosmos.xyz` exists
- [ ] Email account is active and not suspended
- [ ] SMTP is enabled for the account

## 4. 🔒 **Two-Factor Authentication**
**If 2FA is enabled:**
- [ ] Generate app-specific password
- [ ] Use app password instead of account password
- [ ] Disable 2FA temporarily for testing

## 5. 📧 **Test Webmail Access**
**Verify credentials work:**
- [ ] Login to Hostinger webmail with same credentials
- [ ] URL: Usually `webmail.yourdomain.com` or Hostinger webmail
- [ ] If webmail login fails, password is definitely wrong

## 6. 🔧 **Alternative SMTP Settings**
**Try different ports/encryption:**

### Option A: Port 465 (SSL)
```
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl  # Try 'ssl' instead of 'tls'
```

### Option B: Port 587 (TLS)
```
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

### Option C: Different Hostinger SMTP
```
MAIL_HOST=smtp.titan.email  # Alternative Hostinger SMTP
MAIL_PORT=465
MAIL_ENCRYPTION=tls
```

## 7. 🎯 **Quick Debug Steps**

### Step 1: Verify Account Exists
```bash
# In Hostinger control panel, check:
# Email > Email Accounts > admin@hahacosmos.xyz
```

### Step 2: Test Different Username
```bash
# Try in Laravel Forge Environment:
MAIL_USERNAME="noobz@hahacosmos.xyz"  # Use the alias
```

### Step 3: Check Password
```bash
# Make sure password is correct and properly escaped
MAIL_PASSWORD="YourActualPassword123!"
```

### Step 4: Test SMTP Connection
```bash
# Run on server:
php debug-hostinger-auth.php
```

## 8. 🔍 **Common Hostinger Issues**

### Issue: Domain not verified
**Solution:** Verify domain in Hostinger DNS settings

### Issue: Email account not created
**Solution:** Create email account in Hostinger panel

### Issue: SMTP not enabled
**Solution:** Enable SMTP in email account settings

### Issue: IP restrictions
**Solution:** Check if Hostinger blocks server IP

## 9. 💡 **Working Example**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=admin@hahacosmos.xyz
MAIL_PASSWORD=RealPasswordHere123
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noobz@hahacosmos.xyz
MAIL_FROM_NAME="Noobz Cinema"
```

## 10. 🆘 **If Still Failing**
- Contact Hostinger support with server IP
- Check Hostinger email logs
- Try creating new email account for testing
- Consider using different email provider (Gmail, SendGrid, etc.)