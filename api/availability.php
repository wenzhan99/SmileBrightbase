<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get query parameters
$dentist = $_GET['dentist'] ?? '';
$clinic = $_GET['clinic'] ?? '';
$date = $_GET['date'] ?? '';

// Validate required parameters
if (!$dentist || !$clinic || !$date) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: dentist, clinic, date'
    ]);
    exit();
}

// Mock availability data - in real implementation, query your database
$mockSlots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

echo json_encode([
    'success' => true,
    'slots' => $mockSlots,
    'dentist' => $dentist,
    'clinic' => $clinic,
    'date' => $date
]);
?>
