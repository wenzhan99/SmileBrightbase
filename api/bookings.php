<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate required fields for new structure
if (!isset($input['dentist_id']) || !isset($input['patient']) || !isset($input['date_iso']) || !isset($input['time_iso'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Missing required booking data']);
    exit();
}

$dentistId = $input['dentist_id'];
$patient = $input['patient'];
$dateIso = $input['date_iso'];
$timeIso = $input['time_iso'];
$service = $input['service'] ?? 'general';
$previousExperience = $input['previous_experience'] ?? 'first-time';
$notes = $input['notes'] ?? '';

// Validate patient data
if (!isset($patient['first_name']) || !isset($patient['last_name']) || !isset($patient['email']) || !isset($patient['phone'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Missing required patient information']);
    exit();
}

// Dentist to Clinic Mapping
$dentistClinicMapping = [
    'Dr. Chua Wen Zhan' => ['clinic' => 'Orchard', 'clinic_id' => 'orchard'],
    'Dr. Lau Gwen' => ['clinic' => 'Orchard', 'clinic_id' => 'orchard'],
    'Dr. Sarah Tan' => ['clinic' => 'Marina Bay', 'clinic_id' => 'marina_bay'],
    'Dr. James Lim' => ['clinic' => 'Bukit Timah', 'clinic_id' => 'bukit_t'],
    'Dr. Aisha Rahman' => ['clinic' => 'Tampines', 'clinic_id' => 'tampines'],
    'Dr. Alex Lee' => ['clinic' => 'Jurong', 'clinic_id' => 'jurong']
];

// Get dentist name and auto-assign clinic
$dentistName = $input['dentistName'] ?? 'Unknown Dentist';
$clinicInfo = $dentistClinicMapping[$dentistName] ?? ['clinic' => 'Unknown', 'clinic_id' => 'unknown'];
$clinicId = $clinicInfo['clinic_id'];
$clinicName = $clinicInfo['clinic'];

// Generate booking reference (SB-YYYYMMDD-XXXX format)
$bookingRef = 'SB-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Generate manage token
$manageToken = 'token-' . time() . '-' . bin2hex(random_bytes(8));

// Parse date and time
$appointmentDateTime = new DateTime($timeIso);
$dateHuman = $appointmentDateTime->format('D, d M Y');
$timeHuman = $appointmentDateTime->format('H:i');

// Prepare booking data for email
$emailData = [
    'reference_id' => $bookingRef,
    'reschedule_token' => $manageToken,
    'full_name' => $patient['first_name'] . ' ' . $patient['last_name'],
    'preferred_clinic' => $clinicName,
    'preferred_date' => $dateIso,
    'preferred_time' => $timeHuman,
    'service' => $service
];

// Save booking to database
try {
    $tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+7 days')); // 7 days from now
    
    // Prepare the SQL statement
    $stmt = $mysqli->prepare("
        INSERT INTO bookings (
            reference_id, full_name, email, phone, preferred_clinic, service,
            preferred_date, preferred_time, message, reschedule_token, 
            token_expires_at, terms_accepted
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }
    
    // Bind parameters
    $fullName = $patient['first_name'] . ' ' . $patient['last_name'];
    $termsAccepted = 1; // Assuming consent is given if we reach this point
    
    $stmt->bind_param('ssssssssssss', 
        $bookingRef,
        $fullName,
        $patient['email'],
        $patient['phone'],
        $clinicName,
        $service,
        $dateIso,
        $timeHuman,
        $notes,
        $manageToken,
        $tokenExpiresAt,
        $termsAccepted
    );
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }
    
    $bookingId = $mysqli->insert_id;
    $stmt->close();
    
} catch (Exception $e) {
    // Log error and return failure
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Failed to save booking. Please try again.']);
    exit();
}

// Send confirmation email using PHP mail() function (Base Version - No AJAX/JSON)
$emailSent = false;
try {
    // Include email service function
    require_once __DIR__ . '/../src/services/send_email.php';
    
    // Prepare appointment data for email
    $appointmentData = [
        'email' => $patient['email'],
        'first_name' => $patient['first_name'],
        'last_name' => $patient['last_name'],
        'phone' => $patient['phone'],
        'date' => $dateIso,
        'time' => $timeHuman,
        'clinic' => $clinicName,
        'service' => ucfirst(str_replace('_', ' ', $service)),
        'experience' => ucfirst(str_replace('_', ' ', $previousExperience)),
        'message' => $notes,
        'id' => $bookingId,
        'reschedule_token' => $manageToken,
        'created_at' => date('Y-m-d H:i:s'),
        'token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ];
    
    // Send email using PHP mail() function
    $emailSent = sendBookingConfirmation($appointmentData);
    
    if ($emailSent) {
        error_log('Email sent successfully for booking ' . $bookingRef);
    } else {
        error_log('Email sending failed for booking ' . $bookingRef);
    }
    
} catch (Exception $e) {
    // Log error but don't fail the booking
    error_log('Email sending failed: ' . $e->getMessage());
}

// Return success response (HTML redirect for base version - no JSON)
header('Location: /SmileBrightbase/public/booking/booking_success.php?ref=' . urlencode($bookingRef));
exit();
?>
