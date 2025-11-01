<?php
// Base booking API - Create Booking Endpoint
// /api/booking/base_create.php

require_once __DIR__ . '/../config.php';

// Set JSON response headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display
ini_set('display_errors', '0');
error_reporting(E_ALL);
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

// Check database connection
if ($mysqli->connect_errno) {
    echo json_encode(['ok' => false, 'message' => 'Database connection failed']);
    exit();
}

// Initialize response
$response = ['ok' => false, 'message' => 'Unknown error'];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields - NO SLUGS, only names
    $requiredFields = ['doctorName', 'locationName', 'date', 'time'];
    
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            throw new Exception("Missing or empty field: $field");
        }
    }

    // Validate date format
    $dateObj = DateTime::createFromFormat('Y-m-d', $input['date']);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $input['date']) {
        throw new Exception('Invalid date format');
    }

    // Validate time format
    $timeObj = DateTime::createFromFormat('H:i', $input['time']);
    if (!$timeObj || $timeObj->format('H:i') !== $input['time']) {
        throw new Exception('Invalid time format');
    }

    // Generate unique reference ID
    function generateReferenceId() {
        return 'SB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    // Set default service_key if not provided
    $serviceKey = $input['serviceName'] ?? 'general';

    // Insert booking with retry for unique reference_id
    $maxRetries = 5;
    $referenceId = null;
    $bookingId = null;

    for ($i = 0; $i < $maxRetries; $i++) {
        $referenceId = generateReferenceId();
        
        try {
            // Insert using doctor_name and location_name (NO SLUGS)
            $stmt = $mysqli->prepare("
                INSERT INTO bookings (
                    reference_id, doctor_name, location_name,
                    service_key, patient_type, date, time, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
            ");
            
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $mysqli->error);
            }
            
            $patientType = $input['patientType'] ?? 'first-time';
            $stmt->bind_param('sssssss',
                $referenceId,
                $input['doctorName'],
                $input['locationName'],
                $serviceKey,
                $patientType,
                $input['date'],
                $input['time']
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
                throw new Exception('Failed to create booking after retries');
            }
        }
    }

    // Set success response
    $response = [
        'ok' => true,
        'message' => 'Appointment booked successfully',
        'referenceId' => $referenceId,
        'bookingId' => $bookingId ? (string)$bookingId : null,
        'redirectUrl' => null
    ];

} catch (Exception $e) {
    $response = ['ok' => false, 'message' => $e->getMessage()];
} finally {
    // Clean any accidental output
    $noise = ob_get_clean();
    if ($noise) {
        error_log('[base_create.php noise] ' . substr($noise, 0, 500));
    }
    
    // Return response
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
?>

