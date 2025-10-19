# 🚨 SECURITY UPDATE: Gmail App Password Compromised

## ⚠️ IMMEDIATE ACTION REQUIRED

Your Gmail App Password has been exposed and must be treated as compromised. Follow these steps immediately:

## Step 1: Revoke Compromised Password (URGENT)

### 1.1 Access Google Account Security
- Go to: https://myaccount.google.com/security
- Sign in with your Gmail account

### 1.2 Navigate to App Passwords
- Click "2-Step Verification" (must be enabled)
- Scroll down to "App passwords"
- Find the existing SmileBright app password
- **DELETE IT IMMEDIATELY**

## Step 2: Generate New App Password

### 2.1 Create New App Password
- In the App passwords section
- Click "Create" or "+"
- Enter name: "SmileBright Nodemailer"
- Click "Create"

### 2.2 Save New Password
- Google shows a 16-character password
- **Copy it immediately** (you won't see it again)
- **Remove spaces** when using it
- Store in a password manager

## Step 3: Update Configuration

### 3.1 Create/Update .env File
```bash
# Copy the secure template
cp env.example .env
```

### 3.2 Edit .env with New Password
```env
# Gmail SMTP (SECURE CONFIGURATION)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_SECURE=true
SMTP_USER=smilebrightclinic@gmail.com
SMTP_PASS=YOUR_NEW_16_CHAR_APP_PASSWORD_NO_SPACES

# Email branding
EMAIL_FROM="SmileBright Clinic" <smilebrightclinic@gmail.com>
EMAIL_REPLY_TO=frontdesk@smilebrightdental.sg
```

### 3.3 Verify .env is in .gitignore
```bash
# Check if .env is ignored
echo ".env" >> .gitignore
```

## Step 4: Test New Configuration

### 4.1 Test Email Service
```bash
node test_email.js
```

### 4.2 Test Complete System
```bash
node test_notifications.js
```

### 4.3 Test PHP Integration
```bash
php test_php_bridge.php
```

## Step 5: Security Hardening

### 5.1 Environment Security
- ✅ Never commit .env to Git
- ✅ Use strong, unique passwords
- ✅ Store secrets in password manager
- ✅ Rotate credentials regularly

### 5.2 Account Security
- ✅ Enable 2-Step Verification (already on)
- ✅ Limit account access to admins
- ✅ Monitor account activity
- ✅ Use dedicated SMTP for production

## Common Issues & Fixes

### "Username and Password not accepted"
- **Cause**: Using old password or normal password
- **Fix**: Use NEW App Password with no spaces

### "TLS or handshake error"
- **Cause**: Port/secure mismatch
- **Fix**: Use 465 + secure=true (or 587 + secure=false)

### "Emails going to spam"
- **Cause**: Gmail reputation issues
- **Fix**: Consider dedicated SMTP provider (SendGrid/Mailgun)

## Production Recommendations

### Move to Dedicated SMTP Provider
For production, consider:
- **SendGrid**: Better deliverability, analytics
- **Mailgun**: Developer-friendly, good APIs
- **Amazon SES**: Cost-effective, scalable

### DNS Configuration
Set up proper email authentication:
- **SPF**: `v=spf1 include:_spf.google.com ~all`
- **DKIM**: Add provider TXT records
- **DMARC**: `v=DMARC1; p=quarantine; rua=mailto:dmarc@your-domain.tld`

## Final Security Checklist

- [ ] Old app password revoked
- [ ] New app password generated
- [ ] .env updated with new password
- [ ] .env added to .gitignore
- [ ] Test email successful
- [ ] Secrets not in Git or chats
- [ ] 2-Step Verification enabled
- [ ] Account access limited to admins

## Emergency Contacts

If you need immediate assistance:
- Google Account Recovery: https://accounts.google.com/signin/recovery
- Gmail Support: https://support.google.com/mail

---

## 🛡️ Security Best Practices Going Forward

1. **Never share credentials in chat or code**
2. **Use environment variables for all secrets**
3. **Regularly rotate passwords and API keys**
4. **Monitor account activity**
5. **Use dedicated SMTP providers for production**
6. **Implement proper DNS records (SPF/DKIM/DMARC)**

Remember: Security is an ongoing process, not a one-time setup!
