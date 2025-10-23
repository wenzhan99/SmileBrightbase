<?php
// SmileBright Booking API - Update Booking Endpoint
// /api/booking/update.php

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
    if (!isset($input['referenceId']) || !isset($input['changes'])) {
        echo json_encode(['ok' => false, 'error' => 'Missing required fields: referenceId and changes']);
        exit();
    }

    $referenceId = $input['referenceId'];
    $changes = $input['changes'];

    if (empty($changes)) {
        echo json_encode(['ok' => false, 'error' => 'No changes provided']);
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

    // Check if booking exists
    $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }
    
    $stmt->bind_param('s', $referenceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingBooking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$existingBooking) {
        echo json_encode(['ok' => false, 'error' => 'Booking not found']);
        exit();
    }

    // Validate changes
    $allowedFields = [
        'dentist_id', 'dentist_name', 'clinic_id', 'clinic_name',
        'service_code', 'service_label', 'preferred_date', 'preferred_time',
        'dateIso', 'time24', 'email', 'phone', 'notes'
    ];

    $validChanges = [];
    foreach ($changes as $field => $value) {
        if (in_array($field, $allowedFields)) {
            // Map new field names to database field names
            $dbField = $field;
            if ($field === 'dateIso') {
                $dbField = 'preferred_date';
            } elseif ($field === 'time24') {
                $dbField = 'preferred_time';
            }
            $validChanges[$dbField] = $value;
        }
    }

    if (empty($validChanges)) {
        echo json_encode(['ok' => false, 'error' => 'No valid changes provided']);
        exit();
    }

    // Validate date/time if provided
    $checkDate = $validChanges['preferred_date'] ?? null;
    $checkTime = $validChanges['preferred_time'] ?? null;
    
    if ($checkDate) {
        $date = DateTime::createFromFormat('Y-m-d', $checkDate);
        if (!$date || $date->format('Y-m-d') !== $checkDate) {
            echo json_encode(['ok' => false, 'error' => 'Invalid date format']);
            exit();
        }
        
        // Check if date is in the future
        if ($date < new DateTime()) {
            echo json_encode(['ok' => false, 'error' => 'Appointment date must be in the future']);
            exit();
        }
    }

    if ($checkTime) {
        $time = DateTime::createFromFormat('H:i', $checkTime);
        if (!$time || $time->format('H:i') !== $checkTime) {
            echo json_encode(['ok' => false, 'error' => 'Invalid time format']);
            exit();
        }
    }

    // Check for conflicts if date/time/dentist is being changed
    if ($checkDate || $checkTime || isset($validChanges['dentist_id'])) {
        $finalDate = $checkDate ?? $existingBooking['preferred_date'];
        $finalTime = $checkTime ?? $existingBooking['preferred_time'];
        $finalDentist = $validChanges['dentist_id'] ?? $existingBooking['dentist_id'];
        
        $stmt = $mysqli->prepare("
            SELECT reference_id FROM bookings 
            WHERE dentist_id = ? AND preferred_date = ? AND preferred_time = ? 
            AND reference_id != ?
        ");
        
        if ($stmt) {
            $stmt->bind_param('ssss', $finalDentist, $finalDate, $finalTime, $referenceId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                echo json_encode(['ok' => false, 'error' => 'Time slot is already booked for this dentist']);
                exit();
            }
            $stmt->close();
        }
    }

    // Build update query
    $updateFields = [];
    $updateValues = [];
    $types = '';
    
    foreach ($validChanges as $field => $value) {
        $updateFields[] = "`$field` = ?";
        $updateValues[] = $value;
        $types .= 's';
    }
    
    $updateFields[] = "`updated_at` = CURRENT_TIMESTAMP";
    $updateQuery = "UPDATE bookings SET " . implode(', ', $updateFields) . " WHERE reference_id = ?";
    $updateValues[] = $referenceId;
    $types .= 's';

    // Execute update
    $stmt = $mysqli->prepare($updateQuery);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }
    
    $stmt->bind_param($types, ...$updateValues);
    
    if (!$stmt->execute()) {
        throw new Exception('Database update failed: ' . $stmt->error);
    }
    
    $stmt->close();

    // Log changes to booking_history table
    foreach ($validChanges as $field => $newValue) {
        $oldValue = $existingBooking[$field] ?? null;
        
        if ($oldValue !== $newValue) {
            $stmt = $mysqli->prepare("
                INSERT INTO booking_history (reference_id, field, old_value, new_value, changed_by)
                VALUES (?, ?, ?, ?, 'patient')
            ");
            
            if ($stmt) {
                $stmt->bind_param('ssss', $referenceId, $field, $oldValue, $newValue);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $mysqli->close();

    // Send update emails (temporarily disabled for testing)
    $emailSent = false;

    // Set success response
    $response = [
        'ok' => true,
        'referenceId' => $referenceId,
        'message' => 'Booking updated successfully',
        'updated' => array_keys($validChanges),
        'redirectUrl' => '/SmileBright/public/booking/manage_booking.html?ref=' . $referenceId,
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
        error_log('[update.php noise] ' . substr($noise, 0, 500));
    }
    
    // Return response
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
?>
