# SmileBright Dental Booking System

A comprehensive dental appointment booking system with email notifications, built with Node.js and Express.

## Project Structure

```
SmileBright-Copy/
│
├── server.js                # Main Express server (Node.js)
├── package.json             # Node dependencies and scripts
├── .env                     # Environment variables (DB credentials, etc.)
├── .gitignore               # Prevent commits of logs, node_modules, etc.
│
├── /public                  # Static frontend files served to browser
│   ├── index.html           # Your main page
│   ├── Book-Appointment.html # Booking form page
│   ├── aboutus.html         # About us page
│   ├── clinics.html         # Clinics page
│   ├── services.html        # Services page
│   ├── FAQ.html             # FAQ page
│   ├── /css                 # CSS stylesheets
│   ├── /js                  # Frontend JS (bookingForm.jsx)
│   ├── /images              # All clinic images and assets
│   └── /assets              # Fonts, icons, etc. (optional)
│
├── /routes                  # Express route handlers
│   ├── apiRoutes.js         # Main API routes
│   ├── get_booking.php      # PHP booking retrieval
│   └── find-booking.php     # PHP booking search
│
├── /services                # External services (email, messaging, webhooks)
│   ├── emailService.js      # Email service using Nodemailer
│   ├── messagingService.js  # SMS/WhatsApp service
│   └── webhookService.js    # Webhook handling
│
├── /utils                   # Utility functions, middlewares, etc.
│   └── logger.js            # Winston logging utility
│
├── /logs                    # Error/access logs and documentation
│   ├── *.md                 # Documentation files
│   ├── *.bat                # Windows batch scripts
│   └── *.sh                 # Shell scripts
│
├── /uploads                 # Uploaded files (if any)
│
├── /php                     # PHP scripts for backend processing
│   ├── confirm.php          # Booking confirmation page
│   ├── db.php               # Database connection
│   ├── email_config.php     # Email configuration
│   ├── php_email_service.php # PHP email service
│   └── *.php                # Other PHP scripts
│
├── /templates               # Email templates (HTML and text)
│   └── /email
│       ├── booking_created.html
│       ├── booking_created.txt
│       ├── clinic_adjusted.html
│       ├── clinic_adjusted.txt
│       ├── rescheduled_by_client.html
│       └── rescheduled_by_client.txt
│
└── /sql                     # SQL schema, migration, or seed files
    ├── setup_database.sql
    ├── setup_bookings_table.sql
    ├── enhanced_database_schema.sql
    └── *.sql                # Other SQL files
```

## Features

- **Email Notifications**: Automated booking confirmations using Gmail SMTP
- **Multi-clinic Support**: Support for multiple clinic locations
- **Responsive Design**: Mobile-friendly booking interface
- **Email Templates**: Professional HTML and text email templates
- **API Endpoints**: RESTful API for booking management
- **Logging**: Comprehensive logging system
- **Security**: Rate limiting, CORS, and security headers

## Setup Instructions

1. **Install Dependencies**:
   ```bash
   npm install
   ```

2. **Environment Configuration**:
   - Copy `.env` file and update with your Gmail credentials
   - Set `SMTP_USER` to your Gmail address
   - Set `SMTP_PASS` to your Gmail App Password

3. **Start the Server**:
   ```bash
   npm start
   ```

4. **Access the Application**:
   - Main site: `http://localhost:3001`
   - API health check: `http://localhost:3001/health`
   - Booking form: `http://localhost:3001/Book-Appointment.html`

## API Endpoints

- `GET /health` - Health check
- `GET /api/health` - API health check
- `GET /api/test-email` - Test email configuration
- `POST /api/send-booking-email` - Send booking confirmation
- `POST /api/send-reschedule-email` - Send reschedule confirmation
- `POST /api/send-clinic-adjustment` - Send clinic adjustment notification

## Email Configuration

The system uses Gmail SMTP for sending emails. Configure your credentials in the `.env` file:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_SECURE=true
SMTP_USER=smilebright.info@gmail.com
SMTP_PASS=your_app_password_here
EMAIL_FROM="SmileBright Clinic" <smilebright.info@gmail.com>
EMAIL_REPLY_TO=smilebright.info@gmail.com
EMAIL_BCC_ADMIN=smilebright.info@gmail.com
SUPPORT_EMAIL=smilebright.info@gmail.com
```

## Development

- **Node.js**: Express server for API and static file serving
- **PHP**: Backend processing for booking forms
- **Email**: Nodemailer with Gmail SMTP
- **Templates**: Handlebars for email templating
- **Logging**: Winston for application logging

## License

Private - SmileBright Dental Clinic
