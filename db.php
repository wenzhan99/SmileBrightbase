<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'smilebright';
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
  http_response_code(500);
  exit('Database connection failed.');
}
$conn->set_charset('utf8mb4');
