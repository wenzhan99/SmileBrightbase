<?php
/**
 * Doctor Dashboard - Base Version Compliant
 * Server-side rendered appointment management
 * No AJAX, JSON, or JavaScript required
 */

session_start();
require_once __DIR__ . '/../../api/config.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
  header('Location: /SmileBrightbase/public/booking/doctor_login.php?error=not_logged_in');
  exit();
}

$doctorName = $_SESSION['doctor_name'];
$doctorSlug = $_SESSION['doctor_slug'] ?? '';

// Get appointments for this doctor from database
$appointments = [];
$stats = [
  'total' => 0,
  'today' => 0,
  'upcoming' => 0,
  'completed' => 0,
  'cancelled' => 0,
  'no-show' => 0
];

try {
  // Query appointments for this doctor (include notes and patient_type)
  $query = "SELECT reference_id, first_name, last_name, email, phone, 
                   date, time, service_key, status, notes, patient_type, 
                   location_name, created_at
            FROM bookings 
            WHERE doctor_name = ? 
            ORDER BY date DESC, time DESC 
            LIMIT 100";
  
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('s', $doctorName);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $today = date('Y-m-d');
  
  while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
    $stats['total']++;
    
    if ($row['date'] == $today) {
      $stats['today']++;
    }
    
    if ($row['date'] >= $today && $row['status'] == 'confirmed') {
      $stats['upcoming']++;
    }
    
    if ($row['status'] == 'completed') {
      $stats['completed']++;
    }
    
    if ($row['status'] == 'cancelled') {
      $stats['cancelled']++;
    }
    
    if ($row['status'] == 'no-show') {
      $stats['no-show']++;
    }
  }
  
  $stmt->close();
} catch (Exception $e) {
  $error = 'Failed to load appointments: ' . $e->getMessage();
}

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

function h($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
  return date('M j, Y', strtotime($date));
}

function formatTime($time) {
  return date('g:i A', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Doctor Dashboard - Smile Bright Dental</title>
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
    
    .header{
      background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.06);
      padding:20px;margin-bottom:30px;
    }
    .header-inner{
      max-width:1200px;margin:0 auto;
      display:flex;justify-content:space-between;align-items:center;
    }
    .header h1{color:var(--primary);font-size:1.5rem}
    .header-actions{
      display:flex;gap:10px;align-items:center;
    }
    .btn{
      padding:10px 18px;border-radius:8px;border:none;
      background:var(--primary);color:#fff;font-weight:600;
      cursor:pointer;text-decoration:none;display:inline-block;
    }
    .btn:hover{background:var(--primary-600)}
    .btn-secondary{
      background:#6c757d;
    }
    .btn-secondary:hover{background:#5a6268}
    
    .container{
      max-width:1200px;margin:0 auto;padding:0 20px 40px;
    }
    
    .stats-grid{
      display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
      gap:20px;margin-bottom:30px;
    }
    .stat-card{
      background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);
      padding:20px;
    }
    .stat-card h3{
      font-size:0.9rem;color:var(--muted);margin-bottom:10px;
    }
    .stat-card .number{
      font-size:2rem;font-weight:700;color:var(--primary);
    }
    
    .appointments-table{
      background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);
      overflow:hidden;
    }
    table{
      width:100%;border-collapse:collapse;
    }
    th{
      background:#f8f9fa;padding:15px;text-align:left;
      font-weight:600;color:var(--text);border-bottom:2px solid var(--ring);
    }
    td{
      padding:15px;border-bottom:1px solid var(--ring);
    }
    tr:hover{background:#f8f9fa}
    
    .status{
      display:inline-block;padding:4px 12px;border-radius:999px;
      font-size:0.85rem;font-weight:600;
    }
    .status-confirmed{background:#dbeafe;color:#1e40af}
    .status-completed{background:#d1fae5;color:#065f46}
    .status-cancelled{background:#fee2e2;color:#991b1b}
    .status-no-show{background:#fef3c7;color:#92400e}
    .status-rescheduled{background:#e0e7ff;color:#5b21b6}
    
    .status-form{
      display:inline-flex;gap:8px;align-items:center;
    }
    .status-select{
      padding:4px 8px;border-radius:6px;border:1px solid var(--ring);
      font-size:0.85rem;
    }
    .status-update-btn{
      padding:4px 12px;border-radius:6px;border:none;
      background:var(--primary);color:#fff;font-size:0.85rem;
      cursor:pointer;
    }
    .status-update-btn:hover{background:var(--primary-600)}
    
    .error-message{
      background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;
      margin-bottom:20px;border-left:4px solid var(--error);
    }
    
    .empty-state{
      text-align:center;padding:60px 20px;color:var(--muted);
    }
    
    @media (max-width: 768px) {
      table{font-size:0.85rem}
      th,td{padding:10px}
      .stats-grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="header-inner">
      <h1>🦷 Doctor Dashboard - <?php echo h($doctorName); ?></h1>
      <div class="header-actions">
        <a href="doctor_dashboard.php" class="btn">Refresh</a>
        <a href="doctor_logout.php" class="btn btn-secondary">Logout</a>
      </div>
    </div>
  </div>

  <div class="container">
    <?php if (isset($error)): ?>
      <div class="error-message">
        <strong>Error:</strong> <?php echo h($error); ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
      <div style="background:#d1fae5;color:#065f46;padding:15px;border-radius:8px;margin-bottom:20px;border-left:4px solid #10b981;">
        <strong>✅ Success:</strong> Booking <?php echo isset($_GET['ref']) ? h($_GET['ref']) : ''; ?> has been updated successfully! Email notifications have been sent to the patient and clinic (smilebrightsg.info@gmail.com).
      </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == '1'): ?>
      <div style="background:#d1fae5;color:#065f46;padding:15px;border-radius:8px;margin-bottom:20px;border-left:4px solid #10b981;">
        <strong>✅ Success:</strong> Status for booking <?php echo isset($_GET['ref']) ? h($_GET['ref']) : ''; ?> has been updated from "<?php echo isset($_GET['old_status']) ? h(ucfirst(str_replace('-', ' ', $_GET['old_status']))) : ''; ?>" to "<?php echo isset($_GET['new_status']) ? h(ucfirst(str_replace('-', ' ', $_GET['new_status']))) : ''; ?>". Email notifications have been sent to the patient and clinic.
      </div>
    <?php endif; ?>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total Appointments</h3>
        <div class="number"><?php echo $stats['total']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Today's Appointments</h3>
        <div class="number"><?php echo $stats['today']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Upcoming</h3>
        <div class="number"><?php echo $stats['upcoming']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Completed</h3>
        <div class="number"><?php echo $stats['completed']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Cancelled</h3>
        <div class="number"><?php echo $stats['cancelled']; ?></div>
      </div>
      <div class="stat-card">
        <h3>No-Show</h3>
        <div class="number"><?php echo $stats['no-show']; ?></div>
      </div>
    </div>

    <div class="appointments-table">
      <h2 style="padding:20px;margin:0;border-bottom:2px solid var(--ring);">My Appointments</h2>
      
      <?php if (empty($appointments)): ?>
        <div class="empty-state">
          <p>No appointments found.</p>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Reference ID</th>
              <th>Patient Name</th>
              <th>Date</th>
              <th>Time</th>
              <th>Service</th>
              <th>Status</th>
              <th>Previous Experience</th>
              <th>Notes</th>
              <th>Contact</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($appointments as $apt): 
              $patientTypeLabels = [
                'first-time' => 'First Time',
                'regular' => 'Regular',
                'returning' => 'Returning'
              ];
              $patientType = $patientTypeLabels[$apt['patient_type']] ?? ucfirst($apt['patient_type'] ?? 'N/A');
              $notes = !empty($apt['notes']) ? $apt['notes'] : 'None';
            ?>
              <tr>
                <td><strong><?php echo h($apt['reference_id']); ?></strong></td>
                <td><?php echo h($apt['first_name'] . ' ' . $apt['last_name']); ?></td>
                <td><?php echo formatDate($apt['date']); ?></td>
                <td><?php echo formatTime($apt['time']); ?></td>
                <td><?php echo h($serviceNames[$apt['service_key']] ?? ucfirst($apt['service_key'])); ?></td>
                <td>
                  <form method="post" action="/SmileBrightbase/api/doctor/update_status.php" class="status-form" style="display:inline-flex;gap:8px;align-items:center;">
                    <input type="hidden" name="reference_id" value="<?php echo h($apt['reference_id']); ?>">
                    <select name="status" class="status-select">
                      <option value="confirmed" <?php echo ($apt['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                      <option value="rescheduled" <?php echo ($apt['status'] === 'rescheduled') ? 'selected' : ''; ?>>Rescheduled</option>
                      <option value="cancelled" <?php echo ($apt['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                      <option value="no-show" <?php echo ($apt['status'] === 'no-show') ? 'selected' : ''; ?>>No-Show</option>
                      <option value="completed" <?php echo ($apt['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                    <button type="submit" class="status-update-btn" title="Update Status">Update</button>
                  </form>
                </td>
                <td><?php echo h($patientType); ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo h($notes); ?>">
                  <?php echo h(mb_substr($notes, 0, 50)) . (mb_strlen($notes) > 50 ? '...' : ''); ?>
                </td>
                <td>
                  <?php echo h($apt['email']); ?><br>
                  <small><?php echo h($apt['phone']); ?></small>
                </td>
                <td>
                  <a href="doctor_edit_booking.php?ref=<?php echo urlencode($apt['reference_id']); ?>" class="btn" style="padding:6px 12px;font-size:0.85rem;">Edit</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div style="margin-top:30px;text-align:center;">
      <a href="../index.html" class="btn btn-secondary">← Back to Home</a>
    </div>
  </div>
</body>
</html>

