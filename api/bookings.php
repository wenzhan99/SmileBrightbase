<?php
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

// Include database connection
include_once '../src/config/database.php';

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

// Send confirmation email via Node.js email service
$emailSent = false;
try {
    // Prepare email data for Node.js service
    $emailData = [
        'referenceId' => $bookingRef,
        'patient' => [
            'firstName' => $patient['first_name'],
            'lastName' => $patient['last_name'],
            'email' => $patient['email'],
            'phone' => $patient['phone']
        ],
        'appointment' => [
            'dentistId' => $dentistId,
            'dentistName' => $dentistName,
            'clinicId' => $clinicId,
            'clinicName' => $clinicName,
            'serviceCode' => $service,
            'serviceLabel' => ucfirst(str_replace('_', ' ', $service)),
            'experienceCode' => $previousExperience,
            'experienceLabel' => ucfirst(str_replace('_', ' ', $previousExperience)),
            'dateIso' => $dateIso,
            'time24' => $timeHuman,
            'dateDisplay' => $dateHuman,
            'timeDisplay' => $timeHuman
        ],
        'notes' => $notes,
        'consent' => [
            'agreePolicy' => true,
            'agreeTerms' => true
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
        error_log('Email service: Successfully sent confirmation emails for booking ' . $bookingRef);
    } else {
        error_log('Email service failed: HTTP ' . $httpCode . ', Error: ' . $curlError . ', Response: ' . $response);
    }
    
} catch (Exception $e) {
    // Log error but don't fail the booking
    error_log('Email sending failed: ' . $e->getMessage());
}

// Return success response
echo json_encode([
    'ok' => true,
    'message' => 'Booking created successfully',
    'booking_id' => $bookingId,
    'booking_ref' => $bookingRef,
    'manage_token' => $manageToken,
    'dentist_name' => $dentistName,
    'clinic_name' => $clinicName,
    'date_human' => $dateHuman,
    'time_human' => $timeHuman,
    'service' => ucfirst(str_replace('_', ' ', $service)),
    'manage_url' => '/booking/manage?token=' . $manageToken,
    'email_status' => $emailSent ? 'sent' : 'queued',
    'redirectUrl' => '/SmileBright/public/booking/booking_success.html?bookingId=' . $bookingRef
]);
?>
