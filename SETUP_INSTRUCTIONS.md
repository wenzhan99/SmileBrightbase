# SmileBright Booking Rebuild - Setup Instructions

## Version: 2025-10-31-1

## Database Setup

**IMPORTANT:** Run the database setup SQL script using phpMyAdmin or MySQL command line.

1. **Remove existing database** in phpMyAdmin (if it exists):
   - Select `smilebrightbase` database
   - Click "Operations" tab
   - Click "Drop the database" → Confirm

2. **Create new database**:
   - Click on "SQL" tab in phpMyAdmin
   - Copy and paste the entire contents of `database/setup_no_slugs_in_bookings.sql`
   - Click "Go" to execute

This will:
- Create `smilebrightbase` database with all tables
- Create `clinics`, `doctors`, `services`, and `bookings` tables
- **Bookings table has NO doctor_slug or location_slug** - only doctor_name and location_name
- Insert all required reference data (clinics, doctors, services)
- Set up foreign key relationships (service_key only, no slug foreign keys in bookings)

## File Structure

All required files are in place:

```
SmileBrightbase/
├─ api/
│  ├─ config.php                    ✓ Updated to connect to both databases
│  └─ booking/
│     └─ create.php                 ✓ Handles POST, generates reference_id, validates, inserts
└─ public/
   └─ booking/
      ├─ book_appointmentbase.html   ✓ Updated form action and date validation
      └─ booking_success.php         ✓ Displays booking by reference_id
```

## Configuration

- **Database Host:** 127.0.0.1
- **Database User:** root (XAMPP default)
- **Database Password:** (empty, XAMPP default)
- **Database:** smilebrightbase (contains all tables: clinics, doctors, services, bookings)

## Form Submission Flow

1. User fills form at `/public/booking/book_appointmentbase.html`
2. Form submits POST to `/api/booking/create.php`
3. Backend:
   - Validates all inputs
   - Generates unique reference_id (format: SB-YYYYMMDD-XXXXXX)
   - Checks for duplicate doctor/date/time slot
   - Inserts booking into `smilebrightbase.bookings` table
   - Redirects to `/public/booking/booking_success.php?ref={reference_id}`
4. Success page displays booking details

## Key Features

- **Reference ID Format:** SB-YYYYMMDD-XXXXXX (e.g., SB-20251031-3A9FBC)
- **Allowed Time Slots:** 09:00, 11:00, 14:00, 16:00
- **Date Validation:** Must be today or in the future
- **Unique Constraints:** 
  - reference_id must be unique
  - (doctor_slug, date, time) combination must be unique
- **Auto-fill:** Clinic name auto-fills when dentist is selected
- **Conditional Fields:** "Service Other" field appears when "Others" is selected

## Testing Checklist

1. ✅ Run database setup SQL script
2. ✅ Verify `smilebrightbase` database exists in phpMyAdmin with all 4 tables
3. ✅ Open `/public/booking/book_appointmentbase.html` in browser
4. ✅ Select dentist - verify clinic auto-fills
5. ✅ Select date (must be today or future)
6. ✅ Select time slot (09:00, 11:00, 14:00, or 16:00)
7. ✅ Fill all required fields
8. ✅ Submit form - should redirect to success page with reference_id
9. ✅ Verify booking appears in `smilebrightbase.bookings` table
10. ✅ Try submitting same doctor/date/time again - should fail with "slot no longer available"

## Notes

- The form uses **non-AJAX POST submission** (traditional form post)
- All paths use absolute paths from document root (`/api/...`, `/public/...`)
- If your Apache DocumentRoot is set to `C:\xampp\htdocs\SmileBrightbase\`, the paths should work as-is
- If your DocumentRoot is `C:\xampp\htdocs\`, you may need to adjust paths to include `/SmileBrightbase/` prefix

