<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Booking - SmileBright Dental</title>
    <style>
        :root {
            --primary: #1e4b86;
            --primary-600: #173b6a;
            --text: #243042;
            --muted: #6b7a90;
            --bg: #f5f7fb;
            --card: #ffffff;
            --ring: #e5e9f3;
            --shadow: 0 8px 24px rgba(20,40,80,.08);
            --radius: 14px;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
        }
        
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .form-card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--muted);
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--ring);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 75, 134, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .btn:hover {
            background: var(--primary-600);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        
        .success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .policy-notice {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #92400e;
        }
        
        .policy-notice h4 {
            margin-bottom: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <div class="header">
                <h1>Find Your Booking</h1>
                <p>Enter your booking details to access your appointment</p>
            </div>
            
            <form id="findBookingForm">
                <div class="form-group">
                    <label for="referenceId">Reference ID</label>
                    <input 
                        type="text" 
                        id="referenceId" 
                        name="referenceId" 
                        placeholder="e.g., SB123456" 
                        required
                        pattern="SB[0-9]{6}"
                        title="Reference ID should be in format SB123456"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your.email@example.com" 
                        required
                    >
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    Find My Booking
                </button>
                
                <div class="error" id="errorMessage"></div>
                <div class="success" id="successMessage"></div>
            </form>
            
            <div class="policy-notice">
                <h4>Rescheduling Policy</h4>
                <p>Rescheduling is disabled within 12 hours of booking or within 12 hours of your appointment start. If rescheduling is not available, please submit a new booking instead.</p>
            </div>
            
            <div class="back-link">
                <a href="index.html">← Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('findBookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const errorDiv = document.getElementById('errorMessage');
            const successDiv = document.getElementById('successMessage');
            
            // Hide previous messages
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Searching...';
            
            const formData = new FormData(this);
            const referenceId = formData.get('referenceId');
            const email = formData.get('email');
            
            try {
                const response = await fetch('find_booking_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `referenceId=${encodeURIComponent(referenceId)}&email=${encodeURIComponent(email)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect to reschedule page
                    window.location.href = `reschedule.php?ref=${data.booking.reference_id}&token=${data.booking.reschedule_token}`;
                } else {
                    errorDiv.textContent = data.error || 'Booking not found. Please check your details.';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Find My Booking';
            }
        });
        
        // Format reference ID input
        document.getElementById('referenceId').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            // Remove any non-alphanumeric characters except SB prefix
            value = value.replace(/[^A-Z0-9]/g, '');
            // Ensure it starts with SB
            if (value && !value.startsWith('SB')) {
                value = 'SB' + value.replace(/^SB/, '');
            }
            e.target.value = value;
        });
    </script>
</body>
</html>
