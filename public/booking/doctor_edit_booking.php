<?php
/**
 * Doctor Edit Booking Form - Base Version Compliant
 * Server-side rendered form for editing appointments
 */

session_start();
require_once __DIR__ . '/../../api/config.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
  header('Location: /SmileBrightbase/public/booking/doctor_login.php?error=not_logged_in');
  exit();
}

$doctorName = $_SESSION['doctor_name'];
$referenceId = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if (empty($referenceId)) {
  header('Location: /SmileBrightbase/public/booking/doctor_dashboard.php?error=missing_reference');
  exit();
}

// Get booking details
$booking = null;
$error = '';

try {
  $stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
  $stmt->bind_param('s', $referenceId);
  $stmt->execute();
  $result = $stmt->get_result();
  $booking = $result->fetch_assoc();
  $stmt->close();
  
  if (!$booking) {
    $error = 'Booking not found';
  } else if ($booking['doctor_name'] !== $doctorName) {
    $error = 'You do not have permission to edit this booking';
    $booking = null;
  }
} catch (Exception $e) {
  $error = 'Failed to load booking: ' . $e->getMessage();
}

// Get all doctors for dropdown
$allDoctors = [];
try {
  $result = $mysqli->query("SELECT name FROM doctors ORDER BY name");
  while ($row = $result->fetch_assoc()) {
    $allDoctors[] = $row['name'];
  }
} catch (Exception $e) {
  // Ignore error, use empty list
}

function h($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$timeSlots = ['09:00', '11:00', '14:00', '16:00'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Edit Booking - Smile Bright Dental</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../css/footer.css" />
  <style>
    :root{
      --primary:#1e4b86; --primary-600:#173b6a;
      --text:#243042; --muted:#6b7a90; --bg:#f5f7fb; --card:#ffffff;
      --ring:#e5e9f3; --shadow:0 8px 24px rgba(20,40,80,.08); --radius:14px;
      --error:#ef4444; --success:#10b981;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--text);background:var(--bg)}
    
    .header{
      background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.06);
      padding:20px;margin-bottom:30px;
    }
    .header-inner{
      max-width:1200px;margin:0 auto;
      display:flex;justify-content:space-between;align-items:center;
    }
    .header h1{color:var(--primary);font-size:1.5rem}
    
    .container{
      max-width:800px;margin:0 auto;padding:0 20px 40px;
    }
    
    .form-card{
      background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);
      padding:30px;
    }
    
    .form-group{
      margin-bottom:20px;
    }
    .form-group label{
      display:block;font-weight:600;color:var(--text);margin-bottom:8px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea{
      width:100%;padding:12px;border:2px solid var(--ring);border-radius:8px;
      font-size:1rem;font-family:inherit;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus{
      outline:none;border-color:var(--primary);
    }
    .form-group textarea{
      min-height:100px;resize:vertical;
    }
    
    .form-row{
      display:grid;grid-template-columns:1fr 1fr;gap:20px;
    }
    
    .btn{
      padding:12px 24px;border-radius:8px;border:none;
      background:var(--primary);color:#fff;font-weight:600;font-size:1rem;
      cursor:pointer;text-decoration:none;display:inline-block;
    }
    .btn:hover{background:var(--primary-600)}
    .btn-secondary{
      background:#6c757d;
    }
    .btn-secondary:hover{background:#5a6268}
    
    .btn-group{
      display:flex;gap:10px;margin-top:30px;
    }
    
    .error-message{
      background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;
      margin-bottom:20px;border-left:4px solid var(--error);
    }
    
    .info-box{
      background:#f8f9fa;border-left:4px solid var(--primary);padding:15px;
      margin-bottom:20px;border-radius:4px;
    }
    
    @media (max-width: 768px) {
      .form-row{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="header-inner">
      <h1>✏️ Edit Booking - <?php echo h($referenceId); ?></h1>
      <a href="doctor_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
  </div>

  <div class="container">
    <?php if ($error): ?>
      <div class="error-message">
        <strong>Error:</strong> <?php echo h($error); ?>
      </div>
      <div style="text-align:center;margin-top:20px;">
        <a href="doctor_dashboard.php" class="btn">Go Back to Dashboard</a>
      </div>
    <?php elseif ($booking): ?>
      
      <div class="info-box">
        <strong>Patient:</strong> <?php echo h($booking['first_name'] . ' ' . $booking['last_name']); ?><br>
        <strong>Email:</strong> <?php echo h($booking['email']); ?><br>
        <strong>Phone:</strong> <?php echo h($booking['phone']); ?>
      </div>
      
      <?php if (isset($_GET['success'])): ?>
        <div style="background:#d1fae5;color:#065f46;padding:15px;border-radius:8px;margin-bottom:20px;border-left:4px solid var(--success);">
          <strong>Success:</strong> Booking updated successfully! Email notifications have been sent.
        </div>
      <?php endif; ?>
      
      <form method="post" action="/SmileBrightbase/api/doctor/update_booking.php" class="form-card">
        <input type="hidden" name="reference_id" value="<?php echo h($booking['reference_id']); ?>">
        
        <div class="form-group">
          <label for="doctor_name">Doctor *</label>
          <select id="doctor_name" name="doctor_name" required>
            <?php foreach ($allDoctors as $docName): ?>
              <option value="<?php echo h($docName); ?>" <?php echo ($booking['doctor_name'] === $docName) ? 'selected' : ''; ?>>
                <?php echo h($docName); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="location_name">Clinic Location</label>
          <input type="text" id="location_name" name="location_name" value="<?php echo h($booking['location_name'] ?? ''); ?>" readonly style="background:#f5f7fb;">
          <small style="color:var(--muted);">Clinic location is automatically set based on the selected doctor</small>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="date">Date *</label>
            <input type="date" id="date" name="date" value="<?php echo h($booking['date']); ?>" required min="<?php echo date('Y-m-d'); ?>">
          </div>
          
          <div class="form-group">
            <label for="time">Time *</label>
            <select id="time" name="time" required>
              <?php 
              $currentTime = substr($booking['time'], 0, 5);
              foreach ($timeSlots as $slot): 
              ?>
                <option value="<?php echo h($slot); ?>" <?php echo ($currentTime === $slot) ? 'selected' : ''; ?>>
                  <?php echo date('g:i A', strtotime($slot)); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        
        <div class="form-group">
          <label for="notes">Additional Notes</label>
          <textarea id="notes" name="notes" placeholder="Enter any additional notes or special instructions..."><?php echo h($booking['notes'] ?? ''); ?></textarea>
        </div>
        
        <div class="btn-group">
          <button type="submit" class="btn">Update Booking</button>
          <a href="doctor_dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
      
    <?php endif; ?>
  </div>
</body>
</html>

