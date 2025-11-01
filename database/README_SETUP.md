# Database Setup Instructions

## Current Configuration

The system uses a **single database** (`smilebrightbase`) with **no slugs in the bookings table**.

## Quick Setup

Run this SQL file in phpMyAdmin or MySQL command line:

```sql
database/setup_no_slugs_in_bookings.sql
```

This creates:
- Single database: `smilebrightbase`
- Reference tables with slugs: `clinics`, `doctors`, `services` 
- Bookings table: **NO SLUGS**, uses pure names only

## If You Have Existing Database

If you already have a database with slug columns in bookings table, run the migration:

```sql
database/migrations/remove_slugs_from_bookings.sql
```

This will:
- Drop `doctor_slug` and `location_slug` columns from bookings
- Remove foreign key constraints for those columns
- Update unique constraint to use `doctor_name` instead

## Database Schema

### Reference Tables (slugs kept for lookups)

**clinics:**
- `slug` (PK) - e.g., "orchard"
- `name` - e.g., "Orchard Clinic"

**doctors:**
- `slug` (PK) - e.g., "dr-chua-wen-zhan"
- `name` - e.g., "Dr. Chua Wen Zhan"
- `clinic_slug` (FK) - references clinics.slug

**services:**
- `service_key` (PK) - e.g., "general"
- `label` - e.g., "General Checkup"

### Bookings Table (NO SLUGS)

**bookings:**
- `id` (PK, AUTO_INCREMENT)
- `reference_id` (UNIQUE) - e.g., "SB-20250101-3A9FBC"
- `doctor_name` (NO SLUG) - e.g., "Dr. Chua Wen Zhan"
- `location_name` (NO SLUG) - e.g., "Orchard Clinic"
- `service_key` (FK) - references services.service_key
- `patient_type` - "first-time", "regular", "returning"
- `date` - DATE
- `time` - TIME
- `first_name`, `last_name`, `email`, `phone`, `notes`
- `status` - "confirmed", "rescheduled", "cancelled", "completed"
- `created_at`, `updated_at`

**Unique Constraint:** `uq_doctor_slot (doctor_name, date, time)` prevents double-booking

## How It Works

1. **Forms** submit pure display names (e.g., "Dr. Chua Wen Zhan", "General Checkup")
2. **API** looks up slugs from reference tables to validate
3. **Database** stores only names in bookings (no slugs stored)
4. **Foreign keys** only for `service_key` (doctors/clinics use names, not IDs)

## Verification

After setup, verify with:

```sql
DESCRIBE bookings;
SHOW TABLES;
```

You should see:
- No `doctor_slug` or `location_slug` in bookings table
- Only `fk_b_service` foreign key (no fk_b_doctor or fk_b_location)

