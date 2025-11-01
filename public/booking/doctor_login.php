<?php
// Base Version - Server-side error handling (no AJAX)
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Doctor Login - Smile Bright Dental</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root{
      --primary:#1e4b86; --primary-600:#173b6a;
      --text:#243042; --muted:#6b7a90; --bg:#f5f7fb; --card:#ffffff;
      --ring:#e5e9f3; --shadow:0 8px 24px rgba(20,40,80,.08); --radius:14px;
      --error:#ef4444; --success:#10b981;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
      color:var(--text);background:var(--bg);
      display:flex;align-items:center;justify-content:center;
      min-height:100vh;padding:20px;
    }
    
    .login-container{
      background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);
      padding:40px;max-width:400px;width:100%;
    }
    
    .logo{
      text-align:center;margin-bottom:30px;
    }
    .logo h1{
      color:var(--primary);font-size:1.8rem;margin-bottom:5px;
    }
    .logo p{
      color:var(--muted);font-size:0.95rem;
    }
    
    .form-group{
      margin-bottom:20px;
    }
    .form-group label{
      display:block;font-weight:600;color:var(--text);margin-bottom:8px;
    }
    .form-group select,
    .form-group input{
      width:100%;padding:12px;border:2px solid var(--ring);border-radius:8px;
      font-size:1rem;transition:border-color 0.2s ease;
    }
    .form-group select:focus,
    .form-group input:focus{
      outline:none;border-color:var(--primary);
    }
    
    .btn{
      width:100%;padding:12px;border-radius:8px;border:none;
      background:var(--primary);color:#fff;font-weight:700;font-size:1rem;
      cursor:pointer;transition:background 0.2s ease;
    }
    .btn:hover{background:var(--primary-600)}
    
    .error-message{
      background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;
      margin-bottom:20px;border-left:4px solid var(--error);
    }
    
    .success-message{
      background:#d1fae5;color:#065f46;padding:12px;border-radius:8px;
      margin-bottom:20px;border-left:4px solid var(--success);
    }
    
    .back-link{
      text-align:center;margin-top:20px;
    }
    .back-link a{
      color:var(--primary);text-decoration:none;font-weight:600;
    }
    .back-link a:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">
      <h1>🦷 Doctor Login</h1>
      <p>Smile Bright Dental</p>
    </div>
    
    <?php
    // Get error/message from URL parameters (Base Version - no AJAX)
    $error = isset($_GET['error']) ? $_GET['error'] : '';
    $message = isset($_GET['message']) ? $_GET['message'] : '';
    
    function h($str) {
      return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
    ?>
    
    <?php if ($error): ?>
      <div class="error-message">
        <strong>Error:</strong> 
        <?php
        switch($error) {
          case 'missing_fields':
            echo 'Please select a doctor and enter password';
            break;
          case 'invalid_doctor':
            echo 'Invalid doctor selection';
            break;
          case 'incorrect_password':
            echo 'Incorrect password. Please try again.';
            break;
          case 'not_logged_in':
            echo 'Please log in to access the dashboard';
            break;
          default:
            echo h($error);
        }
        ?>
      </div>
    <?php endif; ?>
    
    <?php if ($message == 'logged_out'): ?>
      <div class="success-message">
        <strong>Success:</strong> You have been logged out successfully.
      </div>
    <?php endif; ?>
    
    <form method="post" action="/SmileBrightbase/api/doctor/login.php">
      <div class="form-group">
        <label for="doctor">Select Doctor</label>
        <select id="doctor" name="doctor" required>
          <option value="">-- Select Your Name --</option>
          <option value="Dr. Chua Wen Zhan">Dr. Chua Wen Zhan</option>
          <option value="Dr. Lau Gwen">Dr. Lau Gwen</option>
          <option value="Dr. Sarah Tan">Dr. Sarah Tan</option>
          <option value="Dr. James Lim">Dr. James Lim</option>
          <option value="Dr. Aisha Rahman">Dr. Aisha Rahman</option>
          <option value="Dr. Alex Lee">Dr. Alex Lee</option>
        </select>
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>
      
      <button type="submit" class="btn">Login to Dashboard</button>
    </form>
    
    <div class="back-link">
      <a href="../index.html">← Back to Home</a>
    </div>
  </div>

</body>
</html>

