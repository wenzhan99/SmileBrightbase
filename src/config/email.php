<?php
/**
 * Email Configuration for Smile Bright Dental
 * Centralized configuration for all email-related settings
 */

// ============================================================
// SMTP / EMAIL SETTINGS
// ============================================================

// SMTP Configuration (can be overridden by environment variables)
// For Gmail: Port 587 uses STARTTLS (SMTP_SECURE = false), Port 465 uses SSL (SMTP_SECURE = true)
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', isset($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587);
// Port 587 = STARTTLS (false), Port 465 = SSL (true)
// For Gmail with port 587, use STARTTLS (false)
define('SMTP_SECURE', isset($_ENV['SMTP_SECURE']) ? ($_ENV['SMTP_SECURE'] === 'tls' || $_ENV['SMTP_SECURE'] === 'true' || $_ENV['SMTP_SECURE'] === true ? false : (filter_var($_ENV['SMTP_SECURE'], FILTER_VALIDATE_BOOLEAN) ?? false)) : false);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? 'smilebrightsg.info@gmail.com');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? 'wjdlgtljwmrtuijw');

// Email Addresses
define('EMAIL_FROM', $_ENV['EMAIL_FROM'] ?? 'smilebrightsg.info@gmail.com');
define('EMAIL_FROM_NAME', 'Smile Bright Dental');
define('EMAIL_REPLY_TO', $_ENV['EMAIL_REPLY_TO'] ?? 'smilebright.info@gmail.com');
define('EMAIL_SUPPORT', $_ENV['EMAIL_SUPPORT'] ?? 'smilebright.info@gmail.com');
define('EMAIL_BCC_ADMIN', $_ENV['EMAIL_BCC_ADMIN'] ?? 'smilebrightsg.info@gmail.com');

// ============================================================
// SUPPORT CONTACT INFORMATION
// ============================================================

define('SUPPORT_PHONE', '+65 6XXX XXXX');
define('SUPPORT_EMAIL', 'smilebright.info@gmail.com');

// ============================================================
// WEBSITE URLS
// ============================================================

define('WEBSITE_URL', 'https://smilebrightdental.sg');
define('RESCHEDULE_BASE_URL', WEBSITE_URL . '/appointments/reschedule');
define('CANCEL_BASE_URL', WEBSITE_URL . '/appointments/cancel');

// ============================================================
// CLINIC ADDRESSES
// ============================================================
// Update these with your actual clinic addresses

$CLINIC_ADDRESSES = [
    // Database clinic names (matching bookings table)
    'Orchard Clinic' => [
        'name' => 'Orchard Clinic',
        'address' => '123 Orchard Road, #03-01 Orchard Gateway, Singapore 238858',
        'phone' => '+65 6234 5678',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Marina Bay Clinic' => [
        'name' => 'Marina Bay Clinic',
        'address' => '10 Marina Bay Link, #02-15 Marina Bay Financial Centre, Singapore 018956',
        'phone' => '+65 6234 5679',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Bukit Timah Clinic' => [
        'name' => 'Bukit Timah Clinic',
        'address' => '360 Orchard Road, #03-02 International Building, Singapore 238869',
        'phone' => '+65 6234 5680',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Tampines Clinic' => [
        'name' => 'Tampines Clinic',
        'address' => '5 Tampines Central 6, #02-08 Tampines Plaza, Singapore 529482',
        'phone' => '+65 6234 5681',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Jurong Clinic' => [
        'name' => 'Jurong Clinic',
        'address' => '50 Jurong Gateway Road, #03-14 JEM, Singapore 608549',
        'phone' => '+65 6234 5682',
        'email' => 'smilebright.info@gmail.com'
    ],
    // Legacy names for backward compatibility
    'Novena' => [
        'name' => 'Novena Clinic',
        'address' => 'Novena Medical Center, 10 Sinaran Drive #03-15, Singapore 307506',
        'phone' => '+65 6XXX XXXX',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Tampines' => [
        'name' => 'Tampines Clinic',
        'address' => '5 Tampines Central 6, #02-08 Tampines Plaza, Singapore 529482',
        'phone' => '+65 6234 5681',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Jurong East' => [
        'name' => 'Jurong Clinic',
        'address' => '50 Jurong Gateway Road, #03-14 JEM, Singapore 608549',
        'phone' => '+65 6234 5682',
        'email' => 'smilebright.info@gmail.com'
    ]
];

// ============================================================
// TOKEN SETTINGS
// ============================================================

define('TOKEN_EXPIRY_DAYS', 30); // Days until reschedule token expires

// ============================================================
// EMAIL BRANDING / COLORS
// ============================================================

define('BRAND_PRIMARY_COLOR', '#1f4f86');    // Blue
define('BRAND_PRIMARY_DARK', '#173e6c');     // Darker blue
define('BRAND_LIGHT_BG', '#f5f7fb');         // Light gray background
define('BRAND_MUTED_TEXT', '#6b7a90');       // Muted text color
define('BRAND_BORDER', '#e7ebf3');           // Border color

// ============================================================
// TIMEZONE
// ============================================================

define('TIMEZONE', 'Asia/Singapore');
date_default_timezone_set(TIMEZONE);

// ============================================================
// EMAIL TEMPLATE SETTINGS
// ============================================================

define('EMAIL_TEMPLATE_VERSION', 'v1.0');
define('EMAIL_LOCALE', 'en-SG');
define('EMAIL_CATEGORY', 'appointment_confirmation');

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Get clinic information by name
 */
function getClinicInfo($clinicName) {
    global $CLINIC_ADDRESSES;
    $info = $CLINIC_ADDRESSES[$clinicName] ?? [
        'name' => $clinicName,
        'address' => 'Address not available',
        'phone' => SUPPORT_PHONE,
        'email' => SUPPORT_EMAIL
    ];
    // Ensure 'name' is set (for backward compatibility)
    if (!isset($info['name'])) {
        $info['name'] = $clinicName;
    }
    return $info;
}

/**
 * Generate reschedule URL
 */
function getRescheduleUrl($appointmentId, $token) {
    return RESCHEDULE_BASE_URL . "?appt_id={$appointmentId}&token={$token}";
}

/**
 * Generate cancel URL
 */
function getCancelUrl($appointmentId, $token) {
    return CANCEL_BASE_URL . "?appt_id={$appointmentId}&token={$token}";
}

/**
 * Format date for email display
 */
function formatEmailDate($date) {
    // Format: Monday, January 1, 2025
    return date('l, F j, Y', strtotime($date));
}

/**
 * Format time for email display
 */
function formatEmailTime($time) {
    // Format: 2:30 PM
    return date('g:i A', strtotime($time));
}

/**
 * Get token expiry timestamp
 */
function getTokenExpiryTimestamp() {
    return strtotime('+' . TOKEN_EXPIRY_DAYS . ' days');
}

/**
 * Format token expiry date
 */
function formatTokenExpiryDate($timestamp) {
    return date('M j, Y', $timestamp);
}











