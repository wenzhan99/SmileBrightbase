# SmileBright Dental - Project Refactoring Plan

## Current Issues
- Files scattered in root directory
- Mixed concerns (HTML, PHP, assets, config)
- No clear separation of frontend/backend
- Configuration files in root

## Proposed Clean Structure

```
SmileBright/
├── public/                     # Web-accessible files only
│   ├── index.html             # Main website pages
│   ├── aboutus.html
│   ├── clinics.html
│   ├── services.html
│   ├── FAQ.html
│   ├── Book-Appointment.html
│   ├── booking/               # Booking flow pages
│   │   ├── dentists.html
│   │   ├── schedule.html
│   │   ├── book-appointment.html
│   │   └── booking-success.html
│   ├── assets/                # Static assets
│   │   ├── images/
│   │   │   ├── hero-dental-team.jpg
│   │   │   ├── GeneralDentistry.jpg
│   │   │   ├── ComesticDentistry.jpg
│   │   │   ├── DentalImplants.jpg
│   │   │   ├── EmergencyDentistry.jpg
│   │   │   ├── OrthodonticsBraces.jpg
│   │   │   ├── PediatricDentistry.jpg
│   │   │   └── clinic-maps/
│   │   │       ├── bukittimahclinicmap.jpg
│   │   │       ├── jurongclinicmap.jpg
│   │   │       ├── marinabayclinicmap.jpg
│   │   │       ├── orchardclinicmap.jpg
│   │   │       └── tampinesclinicmap.jpg
│   │   ├── css/               # Stylesheets
│   │   └── js/                # JavaScript files
│   └── api/                   # API endpoints (web-accessible)
│       ├── bookings.php
│       └── availability.php
├── src/                       # PHP backend code
│   ├── config/               # Configuration files
│   │   ├── database.php
│   │   ├── email.php
│   │   └── app.php
│   ├── models/               # Data models
│   ├── controllers/          # Business logic
│   ├── services/             # Service classes
│   │   ├── BookingService.php
│   │   ├── EmailService.php
│   │   └── NotificationService.php
│   └── utils/                # Utility functions
├── database/                 # Database files
│   ├── migrations/
│   │   ├── setup_database.sql
│   │   ├── setup_bookings_table.sql
│   │   ├── enhanced_database_schema.sql
│   │   ├── migrate_bookings_table.sql
│   │   └── migration_add_reschedule_tokens.sql
│   └── seeds/
├── templates/                # Email templates
│   └── email/
│       ├── booking_created.html
│       ├── booking_created.txt
│       ├── clinic_adjusted.html
│       ├── clinic_adjusted.txt
│       ├── rescheduled_by_client.html
│       └── rescheduled_by_client.txt
├── logs/                     # Application logs
│   ├── error.log
│   └── combined.log
├── scripts/                  # Utility scripts
│   ├── run_migration.php
│   ├── start_notifications.bat
│   ├── start_notifications.sh
│   └── repair_mysql.bat
├── vendor/                   # Composer dependencies
├── docs/                     # Documentation
│   ├── README.md
│   ├── QUICK_START.md
│   ├── EMAIL_SETUP_GUIDE.md
│   ├── NOTIFICATION_SETUP_GUIDE.md
│   ├── SECURITY_UPDATE_GUIDE.md
│   ├── NAVIGATION_IMPLEMENTATION_SUMMARY.md
│   ├── SYSTEM_SUMMARY.md
│   └── README_NOTIFICATIONS.md
├── .env.example              # Environment configuration template
├── composer.json             # PHP dependencies
├── composer.lock
└── composer.phar
```

## Benefits of This Structure
1. **Clear Separation**: Frontend (public/) vs Backend (src/)
2. **Organized Assets**: All images, CSS, JS in dedicated folders
3. **Modular Backend**: Controllers, services, models separated
4. **Clean API**: API endpoints in public/api/ for web access
5. **Documentation**: All docs in one place
6. **Configuration**: All config files centralized
7. **Database**: All DB files organized in database/
8. **Security**: Sensitive files outside public web root

## Migration Steps
1. Create new folder structure
2. Move files to appropriate locations
3. Update all file paths and references
4. Test functionality
5. Update documentation
