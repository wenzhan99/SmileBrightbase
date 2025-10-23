<?php
/**
 * Email Configuration for Smile Bright Dental
 * Centralized configuration for all email-related settings
 */

// ============================================================
// SMTP / EMAIL SETTINGS
// ============================================================

define('EMAIL_FROM', 'smilebright.info@gmail.com');
define('EMAIL_FROM_NAME', 'Smile Bright Dental');
define('EMAIL_REPLY_TO', 'smilebright.info@gmail.com');
define('EMAIL_SUPPORT', 'smilebright.info@gmail.com');

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
    'Novena' => [
        'address' => 'Novena Medical Center, 10 Sinaran Drive #03-15, Singapore 307506',
        'phone' => '+65 6XXX XXXX',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Tampines' => [
        'address' => 'Tampines Plaza, 5 Tampines Central 6 #02-08, Singapore 529482',
        'phone' => '+65 6XXX XXXX',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Jurong East' => [
        'address' => 'JEM, 50 Jurong Gateway Road #03-14, Singapore 608549',
        'phone' => '+65 6XXX XXXX',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Woodlands' => [
        'address' => 'Causeway Point, 1 Woodlands Square #03-26, Singapore 738099',
        'phone' => '+65 6XXX XXXX',
        'email' => 'smilebright.info@gmail.com'
    ],
    'Punggol' => [
        'address' => 'Waterway Point, 83 Punggol Central #03-22, Singapore 828761',
        'phone' => '+65 6XXX XXXX',
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
    return $CLINIC_ADDRESSES[$clinicName] ?? [
        'address' => 'Address not available',
        'phone' => SUPPORT_PHONE,
        'email' => SUPPORT_EMAIL
    ];
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











