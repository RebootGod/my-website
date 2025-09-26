# Email Configuration Fix for Hostinger SMTP

## Issue Identified
Error: `The "ssl" scheme is not supported; supported schemes for mailer "smtp" are: "smtp", "smtps".`

## Current Config (WRONG):
```
MAIL_MAILER=smtp
MAIL_SCHEME=ssl          # ❌ WRONG - Not supported
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME="admin@hahacosmos.xyz"
MAIL_PASSWORD="secret"
MAIL_FROM_ADDRESS="noobz@hahacosmos.xyz"
MAIL_FROM_NAME="${APP_NAME}"
```

## Fixed Config (CORRECT):
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME="admin@hahacosmos.xyz"
MAIL_PASSWORD="secret"
MAIL_ENCRYPTION=tls      # ✅ CORRECT - Use 'tls' for port 465
MAIL_FROM_ADDRESS="noobz@hahacosmos.xyz"
MAIL_FROM_NAME="${APP_NAME}"
```

## Alternative Config (Also Works):
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587            # Alternative: Use port 587 with STARTTLS
MAIL_USERNAME="admin@hahacosmos.xyz"
MAIL_PASSWORD="secret"
MAIL_ENCRYPTION=tls      # For port 587
MAIL_FROM_ADDRESS="noobz@hahacosmos.xyz"
MAIL_FROM_NAME="${APP_NAME}"
```

## Instructions:
1. Update Laravel Forge Environment Variables:
   - Remove: MAIL_SCHEME=ssl
   - Add: MAIL_ENCRYPTION=tls

2. Or change to:
   - MAIL_PORT=587
   - MAIL_ENCRYPTION=tls

## Hostinger SMTP Settings:
- **SSL/TLS (Port 465)**: MAIL_ENCRYPTION=tls
- **STARTTLS (Port 587)**: MAIL_ENCRYPTION=tls
- **No encryption (Port 25)**: MAIL_ENCRYPTION=null (not recommended)