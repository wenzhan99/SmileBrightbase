# 📧📱 SmileBright Email + Messaging Setup Guide

## 🎯 Overview

This guide will help you set up the complete email and messaging system for SmileBright Dental, including:

- ✅ **Nodemailer Email Service** - Professional HTML emails with templates
- ✅ **SMS/WhatsApp Messaging** - Via Twilio or other providers
- ✅ **Webhook Delivery Tracking** - Real-time status updates
- ✅ **PHP Integration** - Seamless integration with existing booking system

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    NOTIFICATION SYSTEM                       │
└─────────────────────────────────────────────────────────────┘

Patient submits booking form
           ↓
    showpost.php (PHP)
           ↓
  notification_bridge.php
           ↓
    Node.js Service (Port 3001)
           ↓
┌─────────────────┬─────────────────┬─────────────────┐
│   Email Service │ Messaging Svc   │  Webhook Svc    │
│   (Nodemailer)  │ (Twilio/etc)    │ (Status Track)  │
└─────────────────┴─────────────────┴─────────────────┘
           ↓
    Patient receives:
    ✉️ Email + 📱 SMS/WhatsApp
```

## 🚀 Quick Start (15 minutes)

### Step 1: Install Node.js Dependencies

```bash
# In your SmileBright directory
npm install
```

### Step 2: Configure Environment Variables

1. Copy the example environment file:
```bash
cp env.example .env
```

2. Edit `.env` with your settings:

```env
# Email Configuration (Gmail for testing)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=false
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password

# Messaging Configuration (Twilio)
MSG_PROVIDER=twilio
TWILIO_ACCOUNT_SID=your-twilio-account-sid
TWILIO_AUTH_TOKEN=your-twilio-auth-token
TWILIO_PHONE_NUMBER=+1234567890

# Application Settings
PORT=3001
NODE_ENV=development
WEBSITE_URL=https://smilebrightdental.sg
```

### Step 3: Start the Node.js Service

```bash
# Development mode (with auto-restart)
npm run dev

# Production mode
npm start
```

### Step 4: Test the System

```bash
# Test the complete flow
node test_notifications.js
```

## 📧 Email Setup

### Gmail SMTP (Recommended for Testing)

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and generate password
   - Use this password in `SMTP_PASS`

3. **Configure `.env`**:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=false
SMTP_USER=youremail@gmail.com
SMTP_PASS=your-16-char-app-password
```

### Production SMTP (Recommended for Live Site)

For production, use a dedicated email service:

#### SendGrid
```env
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_SECURE=false
SMTP_USER=apikey
SMTP_PASS=your-sendgrid-api-key
```

#### Mailgun
```env
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_SECURE=false
SMTP_USER=postmaster@your-domain.mailgun.org
SMTP_PASS=your-mailgun-password
```

## 📱 SMS/WhatsApp Setup

### Twilio (Recommended)

1. **Sign up** at https://twilio.com
2. **Get credentials** from Twilio Console:
   - Account SID
   - Auth Token
   - Phone Number

3. **Configure `.env`**:
```env
MSG_PROVIDER=twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_PHONE_NUMBER=+1234567890
```

### Alternative Providers

#### Vonage (Nexmo)
```env
MSG_PROVIDER=vonage
VONAGE_API_KEY=your-api-key
VONAGE_API_SECRET=your-api-secret
VONAGE_SENDER_ID=SmileBright
```

#### MessageBird
```env
MSG_PROVIDER=messagebird
MESSAGEBIRD_API_KEY=your-api-key
MESSAGEBIRD_SENDER_ID=SmileBright
```

## 🔧 Configuration Options

### Feature Flags

Control which services are enabled:

```env
ENABLE_EMAIL=true
ENABLE_SMS=true
ENABLE_WHATSAPP=true
ENABLE_WEBHOOKS=true
ENABLE_RATE_LIMITING=true
```

### Rate Limiting

```env
RATE_LIMIT_WINDOW_MS=900000    # 15 minutes
RATE_LIMIT_MAX_REQUESTS=100    # Max requests per window
```

### Logging

```env
LOG_LEVEL=info                 # debug, info, warn, error
LOG_FILE=logs/notifications.log
```

## 🧪 Testing

### 1. Test Node.js Service

```bash
# Check if service is running
curl http://localhost:3001/health

# Expected response:
{
  "status": "healthy",
  "timestamp": "2025-01-15T10:30:00.000Z",
  "version": "1.0.0",
  "services": {
    "email": true,
    "sms": true,
    "whatsapp": true,
    "webhooks": true
  }
}
```

### 2. Test Email Configuration

```bash
node test_email.js
```

### 3. Test SMS Configuration

```bash
node test_sms.js
```

### 4. Test Complete Flow

```bash
node test_notifications.js
```

### 5. Test PHP Integration

```php
<?php
require 'notification_bridge.php';
testNotificationBridge();
?>
```

## 📊 Monitoring & Logs

### View Logs

```bash
# Real-time logs
tail -f logs/combined.log

# Error logs only
tail -f logs/error.log
```

### Health Monitoring

- **Service Health**: `GET /health`
- **Email Status**: Check SMTP connection
- **SMS Status**: Check Twilio account balance
- **Webhook Status**: Monitor delivery callbacks

## 🔒 Security Best Practices

### 1. Environment Variables

- ✅ Never commit `.env` file to git
- ✅ Use strong, unique passwords
- ✅ Rotate API keys regularly
- ✅ Use different credentials for dev/prod

### 2. Webhook Security

```env
# Set strong webhook secrets
API_SECRET_KEY=your-strong-secret-key
WEBHOOK_SECRET=your-webhook-secret
```

### 3. Rate Limiting

- ✅ Enable rate limiting in production
- ✅ Monitor for abuse
- ✅ Set appropriate limits

### 4. Data Privacy

- ✅ Redact sensitive data in logs
- ✅ Don't log full message bodies
- ✅ Use HTTPS in production
- ✅ Implement opt-in for SMS/WhatsApp

## 🚨 Troubleshooting

### Common Issues

#### 1. "SMTP connection failed"
```bash
# Check Gmail app password
# Verify 2FA is enabled
# Check firewall/antivirus blocking port 587
```

#### 2. "Twilio authentication failed"
```bash
# Verify Account SID and Auth Token
# Check phone number format (+1234567890)
# Ensure account has sufficient balance
```

#### 3. "Node.js service not responding"
```bash
# Check if port 3001 is available
# Verify Node.js is installed (node --version)
# Check logs for errors
```

#### 4. "PHP bridge connection failed"
```bash
# Verify Node.js service is running
# Check firewall blocking localhost:3001
# Test with: curl http://localhost:3001/health
```

### Debug Mode

Enable debug logging:

```env
LOG_LEVEL=debug
NODE_ENV=development
```

## 📈 Production Deployment

### 1. Environment Setup

```env
NODE_ENV=production
PORT=3001
LOG_LEVEL=info
ENABLE_RATE_LIMITING=true
```

### 2. Process Management

Use PM2 for production:

```bash
npm install -g pm2
pm2 start server.js --name "smilebright-notifications"
pm2 startup
pm2 save
```

### 3. Reverse Proxy (Nginx)

```nginx
location /api/ {
    proxy_pass http://localhost:3001;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

### 4. SSL/HTTPS

- ✅ Use Let's Encrypt for free SSL
- ✅ Redirect HTTP to HTTPS
- ✅ Use secure SMTP (port 465/587)

## 📋 Checklist

### Pre-Launch Checklist

- [ ] Node.js service running on port 3001
- [ ] Email configuration tested
- [ ] SMS/WhatsApp configuration tested
- [ ] Webhook endpoints accessible
- [ ] PHP bridge integration working
- [ ] Rate limiting configured
- [ ] Logging configured
- [ ] Security settings applied
- [ ] Production SMTP configured
- [ ] Monitoring in place

### Post-Launch Monitoring

- [ ] Monitor email delivery rates
- [ ] Monitor SMS delivery rates
- [ ] Check webhook delivery status
- [ ] Monitor error logs
- [ ] Check provider account balances
- [ ] Monitor rate limiting
- [ ] Review security logs

## 🆘 Support

### Logs Location
- `logs/combined.log` - All logs
- `logs/error.log` - Error logs only

### Test Files
- `test_notifications.js` - Complete flow test
- `test_email.js` - Email service test
- `test_sms.js` - SMS service test

### Configuration Files
- `.env` - Environment variables
- `package.json` - Node.js dependencies
- `server.js` - Main Node.js service
- `notification_bridge.php` - PHP integration

---

## 🎉 You're Ready!

Your SmileBright notification system is now configured and ready to send professional emails and SMS/WhatsApp messages to your patients!

**Next Steps:**
1. Test with a real booking
2. Monitor the logs
3. Configure production SMTP
4. Set up monitoring alerts

**Need Help?** Check the logs first, then review this guide for troubleshooting steps.
