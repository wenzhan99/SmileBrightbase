<?php
/**
 * Manage Booking Page - Base Version Compliant
 * No AJAX, no JSON, no jQuery - pure PHP/HTML form handling
 */

require_once __DIR__ . '/../../api/config.php';

// Helper function to escape output
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Get reference ID from query string
$referenceId = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

$booking = null;
$error = '';
$statusMessage = '';
$statusType = '';

// If reference ID provided, fetch booking
if (!empty($referenceId)) {
  try {
    // Check database connection
    if ($mysqli->connect_errno) {
      throw new Exception('Database connection failed');
    }
    
    // Fetch booking by reference ID (using pure names - NO SLUGS)
    $stmt = $mysqli->prepare("
      SELECT 
        reference_id, doctor_name, location_name,
        service_key, patient_type, date, time,
        first_name, last_name, email, phone, notes, status, created_at
      FROM bookings 
      WHERE reference_id = ?
    ");
    
    if (!$stmt) {
      throw new Exception('Database prepare failed: ' . $mysqli->error);
    }
    
    $stmt->bind_param('s', $referenceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    // Optional email verification
    if ($booking && !empty($email) && strtolower($booking['email']) !== strtolower($email)) {
      $booking = null;
      $error = 'Email does not match booking record';
    }
    
    if (!$booking) {
      $error = 'Booking not found. Please check your reference ID.';
    }
  } catch (Exception $e) {
    $error = 'Error loading booking: ' . $e->getMessage();
  }
}

// Handle form submission for updates (POST) - Base Version Compliant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
  $updateRefId = isset($_POST['reference_id']) ? trim($_POST['reference_id']) : '';
  $dentistName = isset($_POST['dentist']) ? trim($_POST['dentist']) : '';
  $newDate = isset($_POST['date']) ? trim($_POST['date']) : '';
  $newTime = isset($_POST['time']) ? trim($_POST['time']) : '';
  
  if (empty($updateRefId)) {
    $error = 'Reference ID required';
  } elseif (empty($dentistName) || empty($newDate) || empty($newTime)) {
    $error = 'Please fill in all required fields (Dentist, Date, Time)';
  } else {
    try {
      // Check database connection
      if ($mysqli->connect_errno) {
        throw new Exception('Database connection failed');
      }
      
      // Look up doctor by name to get clinic
      $doctorStmt = $mysqli->prepare("SELECT d.name as doctor_name, c.name as clinic_name 
                                     FROM doctors d 
                                     JOIN clinics c ON d.clinic_slug = c.slug 
                                     WHERE d.name = ?");
      $doctorStmt->bind_param('s', $dentistName);
      $doctorStmt->execute();
      $doctorResult = $doctorStmt->get_result();
      $doctor = $doctorResult->fetch_assoc();
      $doctorStmt->close();
      
      if (!$doctor) {
        throw new Exception('Invalid dentist selected');
      }
      
      $doctorName = $doctor['doctor_name'];
      $clinicName = $doctor['clinic_name'];
      
      // Check for duplicate time slot using doctor_name (NO SLUGS)
      $checkStmt = $mysqli->prepare("SELECT id FROM bookings WHERE doctor_name = ? AND date = ? AND time = ? AND reference_id != ?");
      $checkStmt->bind_param('ssss', $doctorName, $newDate, $newTime, $updateRefId);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        throw new Exception('This time slot is already booked. Please select another time.');
      }
      $checkStmt->close();
      
      // Update booking - use doctor_name and location_name (NO SLUGS)
      $updateStmt = $mysqli->prepare("UPDATE bookings SET doctor_name = ?, location_name = ?, date = ?, time = ?, updated_at = CURRENT_TIMESTAMP WHERE reference_id = ?");
      
      if (!$updateStmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
      }
      
      // Get old booking data for email comparison
      $oldBooking = $booking;
      
      $updateStmt->bind_param('sssss', $doctorName, $clinicName, $newDate, $newTime, $updateRefId);
      
      if (!$updateStmt->execute()) {
        $errorCode = $updateStmt->errno;
        $errorMsg = $updateStmt->error ?: $mysqli->error;
        $updateStmt->close();
        throw new Exception('Update failed [Error ' . $errorCode . ']: ' . $errorMsg);
      }
      $updateStmt->close();
      
      // Get updated booking data
      $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
      $stmt->bind_param('s', $updateRefId);
      $stmt->execute();
      $updatedBooking = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      
      // Send email notifications (to patient and clinic)
      require_once __DIR__ . '/../../src/config/email.php';
      
      try {
        require_once __DIR__ . '/../../src/services/native_email_service.php';
        
        // Service name mapping
        $serviceNames = [
          'general' => 'General Checkup',
          'cleaning' => 'Teeth Cleaning',
          'filling' => 'Dental Filling',
          'extraction' => 'Tooth Extraction',
          'braces' => 'Braces Consultation',
          'whitening' => 'Teeth Whitening',
          'implant' => 'Dental Implant',
          'others' => 'Others'
        ];
        
        // Prepare booking data for email
        $bookingData = [
          'email' => $updatedBooking['email'],
          'full_name' => $updatedBooking['first_name'] . ' ' . $updatedBooking['last_name'],
          'first_name' => $updatedBooking['first_name'],
          'last_name' => $updatedBooking['last_name'],
          'phone' => $updatedBooking['phone'],
          'preferred_date' => $updatedBooking['date'],
          'preferred_time' => $updatedBooking['time'],
          'preferred_clinic' => $updatedBooking['location_name'],
          'doctor_name' => $updatedBooking['doctor_name'],
          'clinic_name' => $updatedBooking['location_name'],
          'service' => $serviceNames[$updatedBooking['service_key']] ?? ucfirst($updatedBooking['service_key']),
          'reference_id' => $updateRefId,
          'notes' => $updatedBooking['notes'] ?: 'None',
          'id' => $updateRefId,
          'reschedule_token' => bin2hex(random_bytes(16))
        ];
        
        $emailService = new SmileBrightEmailService();
        
        // 1. Send confirmation to PATIENT
        $patientResult = $emailService->sendBookingConfirmation($bookingData);
        if ($patientResult['success']) {
          error_log('✅ Patient email sent to: ' . $updatedBooking['email']);
        } else {
          error_log('❌ Patient email failed: ' . $patientResult['message']);
        }
        
        // 2. Send notification to CLINIC
        $clinicEmail = defined('EMAIL_BCC_ADMIN') && !empty(EMAIL_BCC_ADMIN) ? EMAIL_BCC_ADMIN : 'smilebrightsg.info@gmail.com';
        
        // Check what changed
        $changes = [];
        if ($oldBooking['doctor_name'] !== $updatedBooking['doctor_name']) {
          $changes[] = 'Doctor: ' . $oldBooking['doctor_name'] . ' -> ' . $updatedBooking['doctor_name'];
        }
        if ($oldBooking['location_name'] !== $updatedBooking['location_name']) {
          $changes[] = 'Clinic: ' . $oldBooking['location_name'] . ' -> ' . $updatedBooking['location_name'];
        }
        if ($oldBooking['date'] !== $updatedBooking['date']) {
          $changes[] = 'Date: ' . date('M j, Y', strtotime($oldBooking['date'])) . ' -> ' . date('M j, Y', strtotime($updatedBooking['date']));
        }
        if (substr($oldBooking['time'], 0, 5) !== substr($updatedBooking['time'], 0, 5)) {
          $changes[] = 'Time: ' . date('g:i A', strtotime($oldBooking['time'])) . ' -> ' . date('g:i A', strtotime($updatedBooking['time']));
        }
        
        $changeSummary = !empty($changes) ? implode("\n", $changes) : 'No changes specified';
        $patientName = $updatedBooking['first_name'] . ' ' . $updatedBooking['last_name'];
        $serviceName = $serviceNames[$updatedBooking['service_key']] ?? ucfirst($updatedBooking['service_key']);
        $doctorName = $updatedBooking['doctor_name'];
        $clinicName = $updatedBooking['location_name'];
        $appointmentDate = date('M j, Y', strtotime($updatedBooking['date']));
        $appointmentTime = date('g:i A', strtotime($updatedBooking['time']));
        
        $clinicSubject = "Patient Updated Booking - Ref {$updateRefId} - {$patientName}";
        $clinicHtml = "<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f7fb; }
    .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .header { background: #1f4f86; color: white; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
    .detail { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
    .changes { background: #fff7ed; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='card'>
      <div class='header'>
        <h1 style='margin:0;'>Patient Updated Booking</h1>
        <p style='margin:5px 0 0 0;'>Reference: {$updateRefId}</p>
      </div>
      
      <h2>Updated Booking Details</h2>
      <div class='detail'><strong>Patient:</strong> {$patientName}</div>
      <div class='detail'><strong>Email:</strong> {$updatedBooking['email']}</div>
      <div class='detail'><strong>Phone:</strong> {$updatedBooking['phone']}</div>
      <div class='detail'><strong>Doctor:</strong> {$doctorName}</div>
      <div class='detail'><strong>Clinic:</strong> {$clinicName}</div>
      <div class='detail'><strong>Date:</strong> {$appointmentDate}</div>
      <div class='detail'><strong>Time:</strong> {$appointmentTime}</div>
      <div class='detail'><strong>Service:</strong> {$serviceName}</div>
      
      <div class='changes'>
        <h3>Changes Made by Patient:</h3>
        " . (!empty($changes) ? "<ul><li>" . implode("</li><li>", array_map('htmlspecialchars', $changes)) . "</li></ul>" : "<p>No specific changes logged</p>") . "
      </div>
      
      <p style='margin-top:20px; color:#6b7a90; font-size:14px;'>
        This booking was updated by the patient via manage_booking.php<br>
        Updated at: " . date('M j, Y g:i A') . "
      </p>
    </div>
  </div>
</body>
</html>";
        $clinicText = "Patient Updated Booking - Ref {$updateRefId}\n\nPatient: {$patientName}\nEmail: {$updatedBooking['email']}\nPhone: {$updatedBooking['phone']}\nDoctor: {$doctorName}\nClinic: {$clinicName}\nDate: {$appointmentDate}\nTime: {$appointmentTime}\n\nChanges:\n{$changeSummary}\n\nUpdated by patient at " . date('M j, Y g:i A');
        
        $clinicResult = $emailService->sendClinicNotification($clinicSubject, $clinicHtml, $clinicText, $clinicEmail);
        if ($clinicResult['success']) {
          error_log('✅ Clinic email sent to: ' . $clinicEmail);
        } else {
          error_log('❌ Clinic email failed: ' . $clinicResult['message']);
        }
      } catch (Exception $e) {
        // Log error but don't fail the update
        error_log('❌ Email notification failed for booking ' . $updateRefId . ': ' . $e->getMessage());
      }
      
      // Redirect to show updated booking
      header('Location: manage_booking.php?ref=' . urlencode($updateRefId) . '&status=success&message=' . urlencode('Booking updated successfully! Email notifications have been sent.'));
      exit();
    } catch (Exception $e) {
      $error = 'Update failed: ' . $e->getMessage();
      // Reload booking data on error
      if (!empty($updateRefId) && !$mysqli->connect_errno) {
        $referenceId = $updateRefId;
        try {
          $stmt = $mysqli->prepare("SELECT reference_id, doctor_name, location_name, service_key, patient_type, date, time, first_name, last_name, email, phone, notes, status, created_at FROM bookings WHERE reference_id = ?");
          $stmt->bind_param('s', $referenceId);
          $stmt->execute();
          $result = $stmt->get_result();
          $booking = $result->fetch_assoc();
          $stmt->close();
        } catch (Exception $e2) {
          // Ignore reload error
        }
      }
    }
  }
}

// Dentist mapping (for reference - using pure names)
$dentists = [
  'Dr. Chua Wen Zhan' => 'Orchard Clinic',
  'Dr. Lau Gwen' => 'Orchard Clinic',
  'Dr. Sarah Tan' => 'Marina Bay Clinic',
  'Dr. James Lim' => 'Bukit Timah Clinic',
  'Dr. Aisha Rahman' => 'Tampines Clinic',
  'Dr. Alex Lee' => 'Jurong Clinic'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Smile Bright Dental — Manage Your Booking</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../css/footer.css" />
  <style>
    :root{
      --primary:#1e4b86; --primary-600:#173b6a;
      --text:#243042; --muted:#6b7a90; --bg:#f5f7fb; --card:#ffffff;
      --ring:#e5e9f3; --shadow:0 8px 24px rgba(20,40,80,.08); --radius:14px;
      --success:#10b981; --warning:#f59e0b; --error:#ef4444;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--text);background:var(--bg)}
    
    /* Navigation */
    .nav{
      background:#fff;
      box-shadow:0 2px 10px rgba(0,0,0,.06);
      position:sticky;top:0;z-index:100;
    }
    .nav-inner{
      max-width:1200px;margin:0 auto;padding:14px 20px;
      display:flex;align-items:center;justify-content:space-between;
    }
    .brand{
      font-weight:800;font-size:1.3rem;color:#243042;
      text-decoration:none;
    }
    .navlinks{
      display:flex;align-items:center;gap:5px;flex:1;justify-content:center;
    }
    .nav-item{position:relative}
    .nav-link{
      display:block;padding:10px 15px;text-decoration:none;color:#243042;
      font-weight:600;border-radius:6px;transition:all 0.2s ease;cursor:pointer;
    }
    .nav-link:hover,
    .nav-link:focus{color:#1e4b86;background:rgba(30,75,134,0.05)}
    .nav-link[aria-current="page"]{color:#1e4b86}
    .nav-link.has-dropdown::after{
      content:'▾';margin-left:4px;font-size:0.75em;display:inline-block;
      transition:transform 0.2s ease;
    }
    .nav-item.active .nav-link.has-dropdown::after{transform:rotate(180deg)}
    .dropdown{
      position:absolute;top:100%;left:0;min-width:220px;background:#fff;
      border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:8px 0;
      margin-top:8px;opacity:0;visibility:hidden;transform:translateY(-10px);
      transition:all 0.25s ease;pointer-events:none;
    }
    .nav-item:hover .dropdown,
    .nav-item:focus-within .dropdown,
    .nav-item.active .dropdown{
      opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;
    }
    .dropdown a{
      display:block;padding:10px 20px;color:#243042;text-decoration:none;
      font-weight:500;transition:all 0.15s ease;
    }
    .dropdown a:hover,
    .dropdown a:focus{background:rgba(30,75,134,0.08);color:#1e4b86;padding-left:24px}
    
    .btn{
      padding:10px 18px;border-radius:999px;border:none;background:#1e4b86;
      color:#fff;font-weight:700;cursor:pointer;transition:background 0.2s ease;
      white-space:nowrap;text-decoration:none;display:inline-block;
    }
    .btn:hover{background:#173b6a}
    .btn:disabled{background:#6c757d;cursor:not-allowed}
    
    /* Main Content */
    .main-content{
      padding:40px 20px;max-width:900px;margin:0 auto;
    }
    .booking-container{
      background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);
      padding:40px;margin-bottom:30px;
    }
    
    /* Header Band */
    .header-band{
      background:var(--primary);color:#fff;padding:24px 28px;
      border-radius:16px;margin-bottom:30px;
      display:flex;align-items:center;justify-content:space-between;
      flex-wrap:wrap;gap:15px;
    }
    .header-band h1{
      color:#fff;font-size:2rem;margin:0;
    }
    .header-band .breadcrumb{
      color:rgba(255,255,255,0.9);text-decoration:none;
      font-weight:600;transition:color 0.2s ease;
    }
    .header-band .breadcrumb:hover{
      color:#fff;
    }
    
    /* Status Banner */
    .status-banner{
      padding:15px 20px;border-radius:8px;margin-bottom:20px;
      font-weight:600;text-align:center;
    }
    .status-banner.success{background:#d1fae5;color:#065f46;border-left:4px solid var(--success)}
    .status-banner.error{background:#fee2e2;color:#991b1b;border-left:4px solid var(--error)}
    .status-banner.warning{background:#fef3c7;color:#92400e;border-left:4px solid var(--warning)}
    
    /* Verification Section */
    .verification-section{
      background:#f8f9fa;border-radius:8px;padding:20px;margin-bottom:30px;
      border-left:4px solid var(--primary);
    }
    .verification-section h3{
      color:var(--primary);margin-bottom:15px;font-size:1.2rem;
    }
    .verification-form{
      display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;
      align-items:end;
    }
    
    /* Current Booking Display */
    .current-booking{
      background:#f8f9fa;border-radius:8px;padding:20px;margin-bottom:30px;
      border-left:4px solid var(--primary);
    }
    .current-booking h3{
      color:var(--primary);margin-bottom:15px;font-size:1.2rem;
    }
    .booking-grid{
      display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;
    }
    .booking-item{
      display:flex;flex-direction:column;
    }
    .booking-label{
      font-weight:600;color:var(--text);margin-bottom:5px;
    }
    .booking-value{
      color:var(--muted);font-size:0.95rem;
    }
    
    /* Form Styles - Matching book_appointmentbase.html */
    .form-section {
      margin-bottom: 30px;
    }
    .form-section h3 {
      color: var(--primary);
      margin-bottom: 20px;
      font-size: 1.3rem;
      font-weight: 700;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--ring);
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
    }
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    .form-group label {
      font-weight: 600;
      color: var(--text);
      margin-bottom: 8px;
      font-size: 14px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 12px 14px;
      border: 2px solid var(--ring);
      border-radius: 10px;
      font-size: 15px;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      background: #fff;
      font-family: inherit;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 75, 134, 0.1);
    }
    .form-group input:read-only {
      background: #f8f9fa;
      cursor: not-allowed;
    }
    .time-group {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
    }
    .time-option {
      position: relative;
    }
    .time-option input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      margin: 0;
      cursor: pointer;
    }
    .time-option label {
      display: block;
      padding: 12px 16px;
      border: 2px solid var(--ring);
      border-radius: 8px;
      text-align: center;
      font-weight: 600;
      color: var(--text);
      background: #fff;
      cursor: pointer;
      transition: all 0.2s ease;
      margin: 0;
    }
    .time-option input[type="radio"]:checked + label {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }
    .time-option input[type="radio"]:hover + label {
      border-color: var(--primary);
      background: rgba(30, 75, 134, 0.05);
    }
    .submit-btn {
      background: var(--primary);
      color: #fff;
      padding: 15px 40px;
      border: none;
      border-radius: 999px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s ease;
      width: 100%;
      margin-top: 20px;
    }
    .submit-btn:hover {
      background: var(--primary-600);
    }
    .submit-btn:disabled {
      background: #6c757d;
      cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
      .time-group {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    
    /* Action Buttons */
    .action-buttons{
      display:flex;gap:15px;justify-content:center;margin-top:30px;
    }
    .action-button{
      padding:12px 24px;border-radius:8px;text-decoration:none;
      font-weight:600;transition:all 0.2s ease;border:none;cursor:pointer;
    }
    .action-button.primary{
      background:var(--primary);color:#fff;
    }
    .action-button.primary:hover{background:var(--primary-600)}
    .action-button.secondary{
      background:#6c757d;color:#fff;
    }
    .action-button.secondary:hover{background:#5a6268}
    
    /* Error Messages */
    .error-message{
      background:#fee;color:#c33;padding:15px;border-radius:8px;
      margin-bottom:20px;border-left:4px solid #c33;
    }
    
    @media (max-width: 768px) {
      .form-grid{
        grid-template-columns:1fr;
      }
      .booking-grid{
        grid-template-columns:1fr;
      }
      .verification-form{
        grid-template-columns:1fr;
      }
      .action-buttons{
        flex-direction:column;
      }
    }
  </style>
</head>
<body>

  <!-- ===== HEADER ===== -->
  <nav class="nav">
    <div class="nav-inner">
      <a href="../index.html" class="brand">Smile Bright Dental</a>
      
      <div class="navlinks">
        <div class="nav-item">
          <a href="../index.html" class="nav-link">Home</a>
        </div>
        
        <div class="nav-item">
          <a href="../about_us.html" class="nav-link has-dropdown" aria-haspopup="true" aria-expanded="false" onclick="toggleDropdown(event, this)">
            About Us
          </a>
          <div class="dropdown" role="menu">
            <a href="../about_us.html#team" role="menuitem">Our Team</a>
            <a href="../about_us.html#mission" role="menuitem">Mission & Values</a>
            <a href="../about_us.html#careers" role="menuitem">Careers</a>
            <a href="../about_us.html#contact" role="menuitem">Contact Us</a>
          </div>
        </div>
        
        <div class="nav-item">
          <a href="../services.html" class="nav-link has-dropdown" aria-haspopup="true" aria-expanded="false" onclick="toggleDropdown(event, this)">
            Services
          </a>
          <div class="dropdown" role="menu">
            <a href="../services.html#general" role="menuitem">General Dentistry</a>
            <a href="../services.html#scaling" role="menuitem">Scaling & Polishing</a>
            <a href="../services.html#braces" role="menuitem">Braces & Invisalign</a>
            <a href="../services.html#whitening" role="menuitem">Teeth Whitening</a>
            <a href="../services.html#implants" role="menuitem">Dental Implants</a>
            <a href="../services.html#wisdom" role="menuitem">Wisdom Tooth Surgery</a>
          </div>
        </div>
        
        <div class="nav-item">
          <a href="../clinics.html" class="nav-link has-dropdown" aria-haspopup="true" aria-expanded="false" onclick="toggleDropdown(event, this)">
            Clinics
          </a>
          <div class="dropdown" role="menu">
            <a href="../clinics.html#locations" role="menuitem">Locations</a>
            <a href="../clinics.html#hours" role="menuitem">Opening Hours</a>
            <a href="../clinics.html#insurance" role="menuitem">Insurance & CHAS</a>
            <a href="../book_appointmentbase.html" role="menuitem">Book Appointment</a>
          </div>
        </div>
        
        <div class="nav-item">
          <a href="../faq.html" class="nav-link has-dropdown" aria-expanded="false" onclick="toggleDropdown(event, this)">FAQ</a>
          <div class="dropdown" role="menu">
            <a href="../faq.html#pricing-longevity" role="menuitem">Pricing & Longevity</a>
            <a href="../faq.html#processes-procedures" role="menuitem">Processes & Procedures</a>
            <a href="../faq.html#patient-transfers" role="menuitem">Patient Transfers</a>
            <a href="../faq.html#chas-dental-subsidies" role="menuitem">CHAS Dental Subsidies</a>
          </div>
        </div>
        
        <div class="nav-item">
          <a href="../book_appointmentbase.html"><button class="btn" aria-current="page">Book Appointment</button></a>
        </div>
      </div>
    </div>
  </nav>

  <!-- ===== MAIN CONTENT ===== -->
  <div class="main-content">
    <div class="booking-container">
      
      <!-- Header Band -->
      <div class="header-band">
        <h1>Manage Your Booking</h1>
        <a href="../book_appointmentbase.html" class="breadcrumb">← Book New Appointment</a>
      </div>

      <?php if (!empty($_GET['status']) && !empty($_GET['message'])): ?>
      <!-- Status Banner -->
      <div class="status-banner <?php echo h($_GET['status']); ?>">
        <?php echo h(urldecode($_GET['message'])); ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
      <!-- Error Message -->
      <div class="error-message">
        <strong>Error:</strong> <?php echo h($error); ?>
      </div>
      <?php endif; ?>

      <!-- Verification Section -->
      <div class="verification-section">
        <h3>Verify Your Booking</h3>
        <form method="get" action="manage_booking.php">
          <div class="verification-form">
            <div class="form-group">
              <label for="referenceId">Reference ID *</label>
              <input type="text" id="referenceId" name="ref" required placeholder="SB-20250101-XXXX" value="<?php echo h($referenceId); ?>">
            </div>
            <div class="form-group">
              <label for="email">Email (Optional)</label>
              <input type="email" id="email" name="email" placeholder="your.email@example.com" value="<?php echo h($email); ?>">
            </div>
            <div class="form-group">
              <button type="submit" class="btn">Load Booking</button>
            </div>
          </div>
        </form>
      </div>

      <?php if ($booking): ?>
      <!-- Current Booking Display -->
      <div class="current-booking">
        <h3>Current Booking Details</h3>
        <div class="booking-grid">
          <div class="booking-item">
            <div class="booking-label">Reference ID</div>
            <div class="booking-value"><?php echo h($booking['reference_id']); ?></div>
          </div>
          <div class="booking-item">
            <div class="booking-label">Status</div>
            <div class="booking-value"><?php echo h($booking['status'] ?: 'scheduled'); ?></div>
          </div>
          <div class="booking-item">
            <div class="booking-label">Dentist</div>
            <div class="booking-value">
              <?php echo h($booking['doctor_name'] ?? 'Unknown'); ?>
            </div>
          </div>
          <div class="booking-item">
            <div class="booking-label">Clinic</div>
            <div class="booking-value">
              <?php echo h($booking['location_name'] ?? 'Unknown'); ?>
            </div>
          </div>
          <div class="booking-item">
            <div class="booking-label">Date</div>
            <div class="booking-value">
              <?php 
                if (!empty($booking['date'])) {
                  $date = new DateTime($booking['date']);
                  echo h($date->format('l, F j, Y'));
                } else {
                  echo '-';
                }
              ?>
            </div>
          </div>
          <div class="booking-item">
            <div class="booking-label">Time</div>
            <div class="booking-value">
              <?php 
                if (!empty($booking['time'])) {
                  echo h(substr($booking['time'], 0, 5));
                } else {
                  echo '-';
                }
              ?>
            </div>
          </div>
          <div class="booking-item">
            <div class="booking-label">Service</div>
            <div class="booking-value"><?php echo h($booking['service_key'] ?: 'Not specified'); ?></div>
          </div>
          <div class="booking-item">
            <div class="booking-label">Patient Type</div>
            <div class="booking-value"><?php echo h($booking['patient_type'] ?: 'Not specified'); ?></div>
          </div>
        </div>
      </div>

      <!-- Update Form -->
      <div class="form-section">
        <h3>Update Booking Details</h3>
        <form method="post" action="manage_booking.php">
          <input type="hidden" name="reference_id" value="<?php echo h($booking['reference_id']); ?>">
          <input type="hidden" name="update_booking" value="1">
          
          <div class="form-grid">
            <!-- Dentist Selection -->
            <div class="form-group full-width">
              <label for="updateDentist">Dentist *</label>
              <select id="updateDentist" name="dentist" required>
                <option value="">Select a dentist</option>
                <?php
                // Get current doctor name from booking
                $currentDoctorName = $booking['doctor_name'] ?? '';
                
                // List all doctors with pure names
                $allDoctors = [
                  'Dr. Chua Wen Zhan' => ['clinic' => 'Orchard Clinic'],
                  'Dr. Lau Gwen' => ['clinic' => 'Orchard Clinic'],
                  'Dr. Sarah Tan' => ['clinic' => 'Marina Bay Clinic'],
                  'Dr. James Lim' => ['clinic' => 'Bukit Timah Clinic'],
                  'Dr. Aisha Rahman' => ['clinic' => 'Tampines Clinic'],
                  'Dr. Alex Lee' => ['clinic' => 'Jurong Clinic']
                ];
                
                foreach ($allDoctors as $name => $info):
                  $selected = ($currentDoctorName === $name) ? 'selected' : '';
                ?>
                <option value="<?php echo h($name); ?>" data-location-name="<?php echo h($info['clinic']); ?>" <?php echo $selected; ?>>
                  <?php echo h($name); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Location (Auto-filled) - Display only -->
            <div class="form-group full-width">
              <label for="updateLocation">Clinic *</label>
              <input type="text" id="updateLocation" name="clinicReadonly" readonly required value="<?php echo h($booking['location_name'] ?? ''); ?>">
            </div>

            <!-- Date Selection -->
            <div class="form-group full-width">
              <label for="updateDate">Date *</label>
              <input type="date" id="updateDate" name="date" required value="<?php echo h($booking['date'] ?: ''); ?>">
            </div>

            <!-- Time Selection -->
            <div class="form-group full-width">
              <label for="updateTime">Time *</label>
              <div class="time-group">
                <?php 
                $currentTime = !empty($booking['time']) ? substr($booking['time'], 0, 5) : '';
                $timeSlots = ['09:00', '11:00', '14:00', '16:00'];
                foreach ($timeSlots as $timeSlot):
                ?>
                <div class="time-option">
                  <input type="radio" id="update-time-<?php echo str_replace(':', '', $timeSlot); ?>" name="time" value="<?php echo $timeSlot; ?>" required <?php echo ($currentTime === $timeSlot) ? 'checked' : ''; ?>>
                  <label for="update-time-<?php echo str_replace(':', '', $timeSlot); ?>"><?php echo $timeSlot; ?></label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <button type="submit" class="submit-btn">Update Booking</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Action Buttons -->
      <div class="action-buttons" style="margin-top: 30px;">
        <a href="booking_success.html" class="action-button secondary">Back to Success Page</a>
        <a href="../book_appointmentbase.html" class="action-button secondary">Book New Appointment</a>
      </div>

    </div>
  </div>

  <!-- ===== FOOTER ===== -->
  <footer class="site-footer">
    <div class="footer-inner">
      <section class="footer-col">
        <h3>Smile Bright Dental</h3>
        <p>We're committed to providing exceptional dental services in a comfortable and caring environment.</p>
      </section>
      <section class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="../index.html">Home</a></li>
          <li><a href="../about_us.html">About Us</a></li>
          <li><a href="../services.html">Services</a></li>
          <li><a href="../clinics.html">Clinics</a></li>
          <li><a href="../faq.html">FAQ</a></li>
          <li><a href="../book_appointmentbase.html">Book Appointment</a></li>
        </ul>
      </section>
      <section class="footer-col">
        <h4>Services</h4>
        <ul>
          <li><a href="../services.html#general">General Dentistry</a></li>
          <li><a href="../services.html#cosmetic">Cosmetic Dentistry</a></li>
          <li><a href="../services.html#orthodontics">Orthodontics</a></li>
          <li><a href="../services.html#implants">Dental Implants</a></li>
          <li><a href="../services.html#pediatric">Pediatric Dentistry</a></li>
          <li><a href="../services.html#emergency">Emergency Dentistry</a></li>
        </ul>
      </section>
      <section class="footer-col">
        <h4>Contact Info</h4>
        <ul class="contact">
          <li>123 Orchard Road<br/>Singapore 238858</li>
          <li>Phone: <a href="tel:+6562345678">+65 6234 5678</a></li>
          <li>Email: info@smilebrightdental.sg</li>
        </ul>
      </section>
    </div>
    <div class="footer-bottom">
      <p>© 2025 Smile Bright Dental. All rights reserved. · <a href="../privacy.html">Privacy Policy</a> · <a href="../terms.html">Terms of Service</a></p>
    </div>
  </footer>

  <script>
    // Base Version - Simple JavaScript only (NO AJAX, NO JSON, NO jQuery)
    
    // Mobile menu toggle
    function toggleMobileMenu() {
      const navMenu = document.getElementById('navMenu');
      if (navMenu) {
        navMenu.classList.toggle('active');
      }
    }
    
    // Dropdown toggle for mobile/touch devices
    function toggleDropdown(event, element) {
      if (window.innerWidth <= 900 || 'ontouchstart' in window) {
        event.preventDefault();
        const navItem = element.parentElement;
        const wasActive = navItem.classList.contains('active');
        
        document.querySelectorAll('.nav-item.active').forEach(item => {
          if (item !== navItem) {
            item.classList.remove('active');
            item.querySelector('.nav-link')?.setAttribute('aria-expanded', 'false');
          }
        });
        
        navItem.classList.toggle('active');
        element.setAttribute('aria-expanded', !wasActive);
      }
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.closest('.nav-item')) {
        document.querySelectorAll('.nav-item.active').forEach(item => {
          item.classList.remove('active');
          item.querySelector('.nav-link')?.setAttribute('aria-expanded', 'false');
        });
      }
    });

    // Dentist selection handler - auto-fill location (matching book_appointmentbase.html)
    document.addEventListener('DOMContentLoaded', function() {
      const dentistSelect = document.getElementById('updateDentist');
      const locationInput = document.getElementById('updateLocation');
      
      if (dentistSelect && locationInput) {
        dentistSelect.addEventListener('change', function() {
          const selectedOption = this.options[this.selectedIndex];
          
          if (selectedOption.value) {
            const locationName = selectedOption.getAttribute('data-location-name');
            locationInput.value = locationName || '';
          } else {
            locationInput.value = '';
          }
        });
      }
    });
  </script>
</body>
</html>

