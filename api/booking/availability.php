<?php
// SmileBright Booking API - Get Availability
// /api/booking/availability.php

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

// Initialize response
$response = ['ok' => false, 'error' => 'Unknown error'];

try {
    // Get parameters from query string
    $clinicId = $_GET['clinicId'] ?? null;
    $dentistId = $_GET['dentistId'] ?? null;
    $date = $_GET['date'] ?? null;
    
    if (!$clinicId || !$dentistId || !$date) {
        echo json_encode(['ok' => false, 'error' => 'Missing required parameters: clinicId, dentistId, date']);
        exit();
    }

    // Validate date format
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
        echo json_encode(['ok' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD']);
        exit();
    }

    // Check if date is in the future
    if ($dateTime < new DateTime()) {
        echo json_encode(['ok' => false, 'error' => 'Date must be in the future']);
        exit();
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

    // Get existing bookings for this dentist and date
    $stmt = $mysqli->prepare("
        SELECT preferred_time 
        FROM bookings 
        WHERE dentist_id = ? AND preferred_date = ? AND status != 'cancelled'
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }
    
    $stmt->bind_param('ss', $dentistId, $date);
    
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $bookedTimes = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTimes[] = $row['preferred_time'];
    }
    $stmt->close();

    // Generate available time slots (9 AM to 5 PM, 30-minute intervals)
    $availableSlots = [];
    $startHour = 9;
    $endHour = 17;
    
    for ($hour = $startHour; $hour < $endHour; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            $timeSlot = sprintf('%02d:%02d', $hour, $minute);
            
            // Skip if this time slot is already booked
            if (!in_array($timeSlot, $bookedTimes)) {
                $availableSlots[] = $timeSlot;
            }
        }
    }

    $mysqli->close();

    // Format response
    $response = [
        'ok' => true,
        'slots' => $availableSlots,
        'date' => $date,
        'dentistId' => $dentistId,
        'clinicId' => $clinicId,
        'totalSlots' => count($availableSlots)
    ];

} catch (Exception $e) {
    $response = ['ok' => false, 'error' => $e->getMessage()];
} catch (Throwable $e) {
    $response = ['ok' => false, 'error' => 'Server error: ' . $e->getMessage()];
} finally {
    // Clean any accidental output
    $noise = ob_get_clean();
    if ($noise) {
        error_log('[availability.php noise] ' . substr($noise, 0, 500));
    }
    
    // Return response
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
?>