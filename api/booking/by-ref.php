<?php
// SmileBright Booking API - Get Booking by Reference
// /api/booking/by-ref.php

require_once __DIR__ . '/../config.php';

// Set JSON response headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit();
}

// Check database connection
if ($mysqli->connect_errno) {
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit();
}

// Initialize response
$response = ['ok' => false, 'error' => 'Unknown error'];

try {
    // Get reference ID from query parameter
    $referenceId = $_GET['ref'] ?? null;
    
    if (!$referenceId) {
        echo json_encode(['ok' => false, 'error' => 'Missing reference ID']);
        exit();
    }

    // Fetch booking by reference ID
    $stmt = $mysqli->prepare("
        SELECT 
            reference_id, dentist_id, dentist_name, clinic_id, clinic_name,
            service_code, service_label, experience_code, experience_label,
            preferred_date, preferred_time, first_name, last_name, email, phone, 
            notes, agree_policy, agree_terms, status, created_at
        FROM bookings 
        WHERE reference_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }
    
    $stmt->bind_param('s', $referenceId);
    
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        echo json_encode(['ok' => false, 'error' => 'Booking not found']);
        exit();
    }

    // Format response
    $response = [
        'ok' => true,
        'booking' => [
            'referenceId' => $booking['reference_id'],
            'dentistId' => $booking['dentist_id'],
            'dentistName' => $booking['dentist_name'],
            'clinicId' => $booking['clinic_id'],
            'clinicName' => $booking['clinic_name'],
            'serviceCode' => $booking['service_code'],
            'serviceLabel' => $booking['service_label'],
            'experienceCode' => $booking['experience_code'],
            'experienceLabel' => $booking['experience_label'],
            'dateIso' => $booking['preferred_date'],
            'time24' => $booking['preferred_time'],
            'preferred_date' => $booking['preferred_date'], // Keep for backward compatibility
            'preferred_time' => $booking['preferred_time'], // Keep for backward compatibility
            'firstName' => $booking['first_name'],
            'lastName' => $booking['last_name'],
            'email' => $booking['email'],
            'phone' => $booking['phone'],
            'notes' => $booking['notes'],
            'status' => $booking['status'],
            'createdAt' => $booking['created_at']
        ]
    ];

} catch (Exception $e) {
    $response = ['ok' => false, 'error' => $e->getMessage()];
} catch (Throwable $e) {
    $response = ['ok' => false, 'error' => 'Server error: ' . $e->getMessage()];
} finally {
    // Clean any accidental output
    $noise = ob_get_clean();
    if ($noise) {
        error_log('[by-ref.php noise] ' . substr($noise, 0, 500));
    }
    
    // Return response
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
?>
