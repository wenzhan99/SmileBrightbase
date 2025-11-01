<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'smilebrightbase', 3306);
if ($mysqli->connect_errno) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error]);
  exit();
}
$mysqli->set_charset('utf8mb4');
?>
