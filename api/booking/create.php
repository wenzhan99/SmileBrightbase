<?php
// SmileBright Booking API - Create Booking Endpoint
// /api/booking/create.php

// Set JSON response headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display to prevent HTML output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start output buffering to capture any accidental output
ob_start();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit();
}

// Initialize response
$response = ['ok' => false, 'error' => 'Unknown error'];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

// Validate required fields
$requiredFields = [
    'appointment' => ['dentistId', 'dentistName', 'clinicId', 'clinicName', 'serviceCode', 'serviceLabel', 'experienceCode', 'experienceLabel', 'dateIso', 'time24'],
    'patient' => ['firstName', 'lastName', 'email', 'phone'],
    'consent' => ['agreePolicy', 'agreeTerms']
];

foreach ($requiredFields as $section => $fields) {
    if (!isset($input[$section])) {
        echo json_encode(['ok' => false, 'error' => "Missing section: $section"]);
        exit();
    }
    foreach ($fields as $field) {
        if (!isset($input[$section][$field])) {
            echo json_encode(['ok' => false, 'error' => "Missing field: $section.$field"]);
            exit();
        }
    }
}

// Validate consent
if (!$input['consent']['agreePolicy'] || !$input['consent']['agreeTerms']) {
    echo json_encode(['ok' => false, 'error' => 'Consent required for privacy policy and terms']);
    exit();
}

// Generate unique reference ID
function generateReferenceId() {
    return 'SB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Database connection
try {
    $mysqli = new mysqli('127.0.0.1', 'root', '', 'smilebright', 3306);
    if ($mysqli->connect_errno) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit();
}

// Insert booking with retry for unique reference_id
$maxRetries = 5;
$referenceId = null;
$bookingId = null;

for ($i = 0; $i < $maxRetries; $i++) {
    $referenceId = generateReferenceId();
    
    try {
        $stmt = $mysqli->prepare("
            INSERT INTO bookings (
                reference_id, dentist_id, dentist_name, clinic_id, clinic_name,
                service_code, service_label, experience_code, experience_label,
                preferred_date, preferred_time, first_name, last_name, email, phone, notes,
                agree_policy, agree_terms, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $mysqli->error);
        }
        
        $status = 'scheduled';
        $agreePolicy = $input['consent']['agreePolicy'] ? 1 : 0;
        $agreeTerms = $input['consent']['agreeTerms'] ? 1 : 0;
        
        $stmt->bind_param('ssssssssssssssssiis',
            $referenceId,
            $input['appointment']['dentistId'],
            $input['appointment']['dentistName'],
            $input['appointment']['clinicId'],
            $input['appointment']['clinicName'],
            $input['appointment']['serviceCode'],
            $input['appointment']['serviceLabel'],
            $input['appointment']['experienceCode'],
            $input['appointment']['experienceLabel'],
            $input['appointment']['dateIso'],
            $input['appointment']['time24'],
            $input['patient']['firstName'],
            $input['patient']['lastName'],
            $input['patient']['email'],
            $input['patient']['phone'],
            $input['patient']['notes'],
            $agreePolicy,
            $agreeTerms,
            $status
        );
        
        if ($stmt->execute()) {
            $bookingId = $mysqli->insert_id;
            $stmt->close();
            break; // Success, exit retry loop
        } else {
            $stmt->close();
            if ($i === $maxRetries - 1) {
                throw new Exception('Database insert failed: ' . $mysqli->error);
            }
            // Continue retry loop for duplicate key error
        }
        
    } catch (Exception $e) {
        if ($i === $maxRetries - 1) {
            echo json_encode(['ok' => false, 'error' => 'Failed to create booking']);
            exit();
        }
    }
}

    $mysqli->close();

    // Send confirmation emails via Node.js email service
    $emailSent = false;
    try {
        // Prepare email data for Node.js service
        $emailData = [
            'referenceId' => $referenceId,
            'patient' => [
                'firstName' => $input['patient']['firstName'],
                'lastName' => $input['patient']['lastName'],
                'email' => $input['patient']['email'],
                'phone' => $input['patient']['phone']
            ],
            'appointment' => [
                'dentistId' => $input['appointment']['dentistId'],
                'dentistName' => $input['appointment']['dentistName'],
                'clinicId' => $input['appointment']['clinicId'],
                'clinicName' => $input['appointment']['clinicName'],
                'serviceCode' => $input['appointment']['serviceCode'],
                'serviceLabel' => $input['appointment']['serviceLabel'],
                'experienceCode' => $input['appointment']['experienceCode'],
                'experienceLabel' => $input['appointment']['experienceLabel'],
                'dateIso' => $input['appointment']['dateIso'],
                'time24' => $input['appointment']['time24'],
                'dateDisplay' => $input['appointment']['dateIso'], // You might want to format this
                'timeDisplay' => $input['appointment']['time24']   // You might want to format this
            ],
            'notes' => $input['patient']['notes'] ?? '',
            'consent' => [
                'agreePolicy' => $input['consent']['agreePolicy'],
                'agreeTerms' => $input['consent']['agreeTerms']
            ]
        ];
        
        // Call Node.js email service
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:4001/send-booking-emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Email-Token: sb_email_token_use_this_exact_string'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && !$curlError) {
            $emailSent = true;
            error_log('Email service: Successfully sent confirmation emails for booking ' . $referenceId);
        } else {
            error_log('Email service failed: HTTP ' . $httpCode . ', Error: ' . $curlError . ', Response: ' . $response);
        }
        
    } catch (Exception $e) {
        // Log error but don't fail the booking
        error_log('Email sending failed: ' . $e->getMessage());
    }

    // Set success response
    $response = [
        'ok' => true,
        'referenceId' => $referenceId,
        'bookingId' => (string)$bookingId,
        'redirectUrl' => '/SmileBright/public/booking/booking_success.html?ref=' . $referenceId,
        'emailStatus' => $emailSent ? 'sent' : 'queued'
    ];

} catch (Exception $e) {
    $response = ['ok' => false, 'error' => $e->getMessage()];
} catch (Throwable $e) {
    $response = ['ok' => false, 'error' => 'Server error: ' . $e->getMessage()];
} finally {
    // Clean any accidental output
    $noise = ob_get_clean();
    if ($noise) {
        error_log('[create.php noise] ' . substr($noise, 0, 500));
    }
    
    // Return response
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
?>
