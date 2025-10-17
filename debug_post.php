<?php
// Debug version to see what's being sent
header('Content-Type: application/json');

echo json_encode([
    'debug' => true,
    'received_data' => $_POST,
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
]);
?>
