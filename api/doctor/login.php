<?php
/**
 * Doctor Login Handler - Base Version Compliant
 * Processes doctor login form submission
 * Uses PHP sessions (not AJAX/JSON)
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /SmileBrightbase/public/booking/doctor_login.html?error=invalid_method');
  exit();
}

// Doctor credentials (Base Version - simple password system)
$doctorCredentials = [
  'Dr. Chua Wen Zhan' => ['password' => 'chua123', 'slug' => 'dr-chua-wen-zhan'],
  'Dr. Lau Gwen' => ['password' => 'lau123', 'slug' => 'dr-lau-gwen'],
  'Dr. Sarah Tan' => ['password' => 'sarah123', 'slug' => 'dr-sarah-tan'],
  'Dr. James Lim' => ['password' => 'james123', 'slug' => 'dr-james-lim'],
  'Dr. Aisha Rahman' => ['password' => 'aisha123', 'slug' => 'dr-aisha-rahman'],
  'Dr. Alex Lee' => ['password' => 'alex123', 'slug' => 'dr-alex-lee']
];

// Get form data
$doctorName = trim($_POST['doctor'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate
if (empty($doctorName) || empty($password)) {
  header('Location: /SmileBrightbase/public/booking/doctor_login.php?error=missing_fields');
  exit();
}

// Check credentials
if (!isset($doctorCredentials[$doctorName])) {
  header('Location: /SmileBrightbase/public/booking/doctor_login.php?error=invalid_doctor');
  exit();
}

if ($password !== $doctorCredentials[$doctorName]['password']) {
  header('Location: /SmileBrightbase/public/booking/doctor_login.php?error=incorrect_password');
  exit();
}

// Login successful - set session
$_SESSION['doctor_name'] = $doctorName;
$_SESSION['doctor_slug'] = $doctorCredentials[$doctorName]['slug'];
$_SESSION['login_time'] = date('Y-m-d H:i:s');

// Redirect to dashboard
header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php');
exit();

