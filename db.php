<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'smilebright', 3306);
if ($mysqli->connect_errno) {
  http_response_code(500);
  die('DB connect failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>
