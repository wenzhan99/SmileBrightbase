<?php
/**
 * Booking Success Page
 * Displays booking confirmation with reference ID
 */

require_once __DIR__ . '/../../api/config.php';

// Get reference ID from query string
$referenceId = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if (empty($referenceId)) {
  header('Location: /SmileBrightbase/public/booking/book_appointmentbase.html');
  exit();
}

// Check database connection
if ($mysqli->connect_errno) {
  die('Database connection failed');
}

// Fetch booking details
$stmt = $mysqli->prepare("SELECT * FROM bookings WHERE reference_id = ?");
$stmt->bind_param('s', $referenceId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

// Helper function to escape output
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Smile Bright Dental — Booking Confirmed</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../css/footer.css" />
  <style>
    :root{
      --primary:#1e4b86; --primary-600:#173b6a;
      --text:#243042; --muted:#6b7a90; --bg:#f5f7fb; --card:#ffffff;
      --ring:#e5e9f3; --shadow:0 8px 24px rgba(20,40,80,.08); --radius:14px;
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
    .btn{
      padding:10px 18px;border-radius:999px;border:none;
      background:#1e4b86;color:#fff;font-weight:700;
      cursor:pointer;transition:background 0.2s ease;
      white-space:nowrap;text-decoration:none;display:inline-block;
    }
    .btn:hover{background:#173b6a}

    .wrap{max-width:1100px;margin:28px auto;padding:0 16px}
    .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
    .card-header{background:var(--primary);color:#fff;padding:22px 26px}
    .card-header h1{margin:0 0 8px;font-weight:900;font-size:1.7rem;color:#fff}
    .card-header p{margin:0;color:rgba(255,255,255,.9)}
    .card-body{padding:24px}

    /* Success Page Styles */
    .success-container {
      text-align: center;
      padding: 40px 20px;
    }
    .success-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 30px;
      font-size: 2.5rem;
      color: white;
    }
    .success-title {
      font-size: 2rem;
      font-weight: 700;
      color: #28a745;
      margin-bottom: 15px;
    }
    .success-message {
      font-size: 1.1rem;
      color: var(--muted);
      margin-bottom: 30px;
      line-height: 1.6;
    }
    .booking-details {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 25px;
      margin: 30px 0;
      text-align: left;
    }
    .booking-details h3 {
      color: var(--primary);
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 20px;
      text-align: center;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #e9ecef;
    }
    .detail-row:last-child {
      border-bottom: none;
    }
    .detail-label {
      font-weight: 600;
      color: var(--text);
    }
    .detail-value {
      color: var(--muted);
      font-weight: 500;
    }
    .reference-id {
      background: var(--primary);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 1.1rem;
    }
    .action-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    .action-button {
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s;
      display: inline-block;
    }
    .btn-primary {
      background: var(--primary);
      color: white;
    }
    .btn-primary:hover {
      background: var(--primary-600);
    }
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    .btn-secondary:hover {
      background: #5a6268;
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
          <a href="../about_us.html" class="nav-link">About Us</a>
        </div>
        <div class="nav-item">
          <a href="../services.html" class="nav-link">Services</a>
        </div>
        <div class="nav-item">
          <a href="../clinics.html" class="nav-link">Clinics</a>
        </div>
        <div class="nav-item">
          <a href="../faq.html" class="nav-link">FAQ</a>
        </div>
        <div class="nav-item">
          <a href="../book_appointmentbase.html"><button class="btn">Book Appointment</button></a>
        </div>
      </div>
    </div>
  </nav>

  <!-- ===== CONTENT ===== -->
  <div class="wrap">
    <div class="card">
      <div class="card-header">
        <h1>Booking Confirmed</h1>
        <p>Your appointment has been successfully scheduled</p>
      </div>

      <div class="card-body">
        <?php if ($booking): ?>
          <div class="success-container">
            <div class="success-icon">✓</div>
            <h2 class="success-title">Thank you, <?php echo h($booking['first_name']); ?>!</h2>
            <p class="success-message">
              Your appointment has been confirmed. Please save your reference number for your records.
            </p>

            <div class="booking-details">
              <h3>Appointment Details</h3>
              <div class="detail-row">
                <span class="detail-label">Reference Number</span>
                <span class="detail-value reference-id"><?php echo h($booking['reference_id']); ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Dentist</span>
                <span class="detail-value"><?php echo h($booking['doctor_name']); ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Clinic</span>
                <span class="detail-value"><?php echo h($booking['location_name']); ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['date'])); ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Time</span>
                <span class="detail-value"><?php echo date('g:i A', strtotime($booking['time'])); ?></span>
              </div>
              <?php if (!empty($booking['notes'])): ?>
              <div class="detail-row">
                <span class="detail-label">Notes</span>
                <span class="detail-value"><?php echo h($booking['notes']); ?></span>
              </div>
              <?php endif; ?>
            </div>

            <div class="action-buttons">
              <a href="../index.html" class="action-button btn-primary">Return Home</a>
              <a href="../book_appointmentbase.html" class="action-button btn-secondary">Book Another Appointment</a>
            </div>
          </div>
        <?php else: ?>
          <div class="success-container">
            <p>Booking not found. Please contact support with your reference number.</p>
            <a href="../index.html" class="action-button btn-primary">Return Home</a>
          </div>
        <?php endif; ?>
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
</body>
</html>

