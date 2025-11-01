<?php
// SmileBright Booking API - Get Bookings by Doctor
// /api/booking/by-doctor.php

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
    // Get parameters from query string
    $doctorId = $_GET['doctorId'] ?? null;
    $status = $_GET['status'] ?? null;
    $date = $_GET['date'] ?? null;
    
    if (!$doctorId) {
        echo json_encode(['ok' => false, 'error' => 'Missing required parameter: doctorId']);
        exit();
    }

    // Build query with filters
    $whereConditions = ['dentist_id = ?'];
    $params = [$doctorId];
    $types = 's';
    
    if ($status) {
        $whereConditions[] = 'status = ?';
        $params[] = $status;
        $types .= 's';
    }
    
    if ($date) {
        $whereConditions[] = 'preferred_date = ?';
        $params[] = $date;
        $types .= 's';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Fetch bookings for the doctor
    $stmt = $mysqli->prepare("
        SELECT 
            reference_id, dentist_id, dentist_name, clinic_id, clinic_name,
            service_code, service_label, experience_code, experience_label,
            preferred_date, preferred_time, first_name, last_name, email, phone, 
            notes, agree_policy, agree_terms, status, created_at, updated_at
        FROM bookings 
        WHERE $whereClause
        ORDER BY preferred_date ASC, preferred_time ASC
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }
    
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $bookings = [];
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'referenceId' => $row['reference_id'],
            'dentistId' => $row['dentist_id'],
            'dentistName' => $row['dentist_name'],
            'clinicId' => $row['clinic_id'],
            'clinicName' => $row['clinic_name'],
            'serviceCode' => $row['service_code'],
            'serviceLabel' => $row['service_label'],
            'experienceCode' => $row['experience_code'],
            'experienceLabel' => $row['experience_label'],
            'dateIso' => $row['preferred_date'],
            'time24' => $row['preferred_time'],
            'preferred_date' => $row['preferred_date'], // Keep for backward compatibility
            'preferred_time' => $row['preferred_time'], // Keep for backward compatibility
            'firstName' => $row['first_name'],
            'lastName' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'notes' => $row['notes'],
            'status' => $row['status'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at']
        ];
    }
    
    $stmt->close();

    // Format response
    $response = [
        'ok' => true,
        'bookings' => $bookings,
        'total' => count($bookings),
        'filters' => [
            'doctorId' => $doctorId,
            'status' => $status,
            'date' => $date
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
        error_log('[by-doctor.php noise] ' . substr($noise, 0, 500));
    }
    
    // Return response
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
?>
