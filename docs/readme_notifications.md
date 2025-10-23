# 📧📱 SmileBright Email + Messaging System

A comprehensive notification system for SmileBright Dental that sends professional emails and SMS/WhatsApp messages to patients for booking confirmations, adjustments, and reschedules.

## 🎯 Features

- ✅ **Professional Email Templates** - HTML + plain text emails with clinic branding
- ✅ **SMS/WhatsApp Messaging** - Via Twilio, Vonage, MessageBird, or AWS SNS
- ✅ **Real-time Delivery Tracking** - Webhook endpoints for status updates
- ✅ **PHP Integration** - Seamless integration with existing booking system
- ✅ **Multi-clinic Support** - Automatic clinic information mapping
- ✅ **Security & Privacy** - Token-based authentication, data redaction
- ✅ **Rate Limiting** - Protection against abuse
- ✅ **Comprehensive Logging** - Detailed logs with sensitive data redaction

## 🏗️ System Architecture

```
Patient Booking Form (PHP)
           ↓
    showpost.php
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

## 🚀 Quick Start

### 1. Install Dependencies

```bash
npm install
```

### 2. Configure Environment

```bash
# Copy example configuration
cp env.example .env

# Edit with your settings
nano .env
```

### 3. Start the Service

**Windows:**
```bash
start_notifications.bat
```

**Linux/Mac:**
```bash
./start_notifications.sh
```

**Manual:**
```bash
npm start
```

### 4. Test the System

```bash
# Test complete system
node test_notifications.js

# Test email only
node test_email.js

# Test SMS only
node test_sms.js

# Test PHP integration
php test_php_bridge.php
```

## 📧 Email Configuration

### Gmail SMTP (Testing)

1. Enable 2FA on Gmail
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Configure `.env`:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=false
SMTP_USER=youremail@gmail.com
SMTP_PASS=your-16-char-app-password
```

### Production SMTP

**SendGrid:**
```env
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USER=apikey
SMTP_PASS=your-sendgrid-api-key
```

**Mailgun:**
```env
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USER=postmaster@your-domain.mailgun.org
SMTP_PASS=your-mailgun-password
```

## 📱 SMS/WhatsApp Configuration

### Twilio (Recommended)

1. Sign up at https://twilio.com
2. Get credentials from Twilio Console
3. Configure `.env`:

```env
MSG_PROVIDER=twilio
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_PHONE_NUMBER=+1234567890
```

### Alternative Providers

**Vonage:**
```env
MSG_PROVIDER=vonage
VONAGE_API_KEY=your-api-key
VONAGE_API_SECRET=your-api-secret
```

**MessageBird:**
```env
MSG_PROVIDER=messagebird
MESSAGEBIRD_API_KEY=your-api-key
```

## 📋 Email Templates

The system includes three professional email templates:

### 1. Booking Created
- **Subject**: "Your SmileBright booking — Ref {{reference_id}}"
- **Content**: Appointment details, reschedule button, clinic info
- **Trigger**: When patient submits booking form

### 2. Clinic Adjusted
- **Subject**: "Appointment adjusted — Ref {{reference_id}}"
- **Content**: Change summary (old → new), reason, updated link
- **Trigger**: When admin adjusts appointment

### 3. Rescheduled by Client
- **Subject**: "Rescheduled confirmed — Ref {{reference_id}}"
- **Content**: New date/time, view link, policy reminder
- **Trigger**: When patient reschedules appointment

## 📱 SMS Templates

Short, concise SMS messages for quick notifications:

- **Booking Created**: "SmileBright: Ref {{reference_id}} on {{date}} {{time}} at {{clinic}}. View: {{short_link}}"
- **Clinic Adjusted**: "SmileBright: Your appt changed {{old_date}} {{old_time}} → {{date}} {{time}}. View: {{short_link}}"
- **Rescheduled**: "SmileBright: Rescheduled to {{date}} {{time}}. Ref {{reference_id}}. View: {{short_link}}"

## 🔗 Webhook Endpoints

Track delivery status in real-time:

- **Twilio Webhooks**: `POST /webhooks/twilio`
- **Generic Webhooks**: `POST /webhooks/generic`
- **Email Webhooks**: `POST /webhooks/email`
- **Status Lookup**: `GET /webhooks/status/:messageId`

## 🛡️ Security Features

- **Environment Variables**: All secrets stored in `.env`
- **Data Redaction**: Sensitive data redacted in logs
- **Rate Limiting**: Protection against abuse
- **Webhook Verification**: Signature validation for webhooks
- **Token-based URLs**: Secure reschedule/cancel links

## 📊 Monitoring & Logs

### Log Files
- `logs/combined.log` - All logs
- `logs/error.log` - Error logs only

### Health Checks
- **Service Health**: `GET /health`
- **Webhook Health**: `GET /webhooks/health`

### Monitoring Commands
```bash
# Real-time logs
tail -f logs/combined.log

# Check service status
curl http://localhost:3001/health

# Test configuration
node test_notifications.js
```

## 🔧 Configuration Options

### Feature Flags
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

### Automated Tests
```bash
# Complete system test
node test_notifications.js

# Individual service tests
node test_email.js
node test_sms.js
php test_php_bridge.php
```

### Manual Testing
1. Submit a booking form
2. Check email delivery
3. Verify SMS/WhatsApp delivery
4. Monitor webhook callbacks
5. Check logs for any errors

## 🚨 Troubleshooting

### Common Issues

**"SMTP connection failed"**
- Check Gmail app password
- Verify 2FA is enabled
- Check firewall blocking port 587

**"Twilio authentication failed"**
- Verify Account SID and Auth Token
- Check phone number format (+1234567890)
- Ensure account has sufficient balance

**"Node.js service not responding"**
- Check if port 3001 is available
- Verify Node.js is installed
- Check logs for errors

**"PHP bridge connection failed"**
- Verify Node.js service is running
- Check firewall blocking localhost:3001
- Test with: `curl http://localhost:3001/health`

### Debug Mode
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

### 2. Process Management (PM2)
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
- Use Let's Encrypt for free SSL
- Redirect HTTP to HTTPS
- Use secure SMTP (port 465/587)

## 📋 File Structure

```
SmileBright/
├── Node.js Service
│   ├── server.js                 # Main server
│   ├── package.json              # Dependencies
│   ├── .env                      # Configuration
│   └── services/
│       ├── emailService.js       # Email service
│       ├── messagingService.js   # SMS/WhatsApp service
│       └── webhookService.js     # Webhook handling
├── Templates
│   └── templates/email/
│       ├── booking_created.html  # Booking email template
│       ├── clinic_adjusted.html  # Adjustment email template
│       └── rescheduled_by_client.html # Reschedule email template
├── PHP Integration
│   ├── notification_bridge.php   # PHP to Node.js bridge
│   └── showpost.php              # Updated booking handler
├── Testing
│   ├── test_notifications.js     # Complete system test
│   ├── test_email.js             # Email service test
│   ├── test_sms.js               # SMS service test
│   └── test_php_bridge.php       # PHP integration test
├── Scripts
│   ├── start_notifications.bat   # Windows startup script
│   └── start_notifications.sh    # Linux/Mac startup script
└── Documentation
    ├── README_NOTIFICATIONS.md   # This file
    └── NOTIFICATION_SETUP_GUIDE.md # Detailed setup guide
```

## 🎉 Success!

Your SmileBright notification system is now ready to send professional emails and SMS/WhatsApp messages to your patients!

**What happens next:**
1. Patient submits booking form
2. PHP saves to database
3. PHP calls Node.js service
4. Node.js sends email + SMS/WhatsApp
5. Patient receives confirmation
6. Webhooks track delivery status

**Need help?** Check the logs first, then review the troubleshooting section.

---

*Built with ❤️ for SmileBright Dental*
