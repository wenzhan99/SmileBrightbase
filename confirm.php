<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - SmileBright Dental</title>
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
            --success: #10b981;
            --warning: #f59e0b;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .confirmation-card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .success-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 10px;
        }
        
        .success-subtitle {
            color: var(--muted);
            font-size: 1.1rem;
        }
        
        .booking-details {
            background: #f8fafc;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
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
            text-align: right;
        }
        
        .reference-id {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-confirmed {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-rescheduled {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-clinic-adjusted {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-600);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text);
            border: 1px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .reschedule-section {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .policy-notice {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #991b1b;
        }
        
        .policy-notice h4 {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .confirmation-card {
                padding: 25px;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .detail-value {
                text-align: left;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-card">
            <div class="success-header">
                <div class="success-icon">✅</div>
                <h1 class="success-title">Booking Confirmed!</h1>
                <p class="success-subtitle">Your appointment has been successfully booked</p>
            </div>
            
            <div class="booking-details">
                <div class="detail-row">
                    <span class="detail-label">Reference ID</span>
                    <span class="detail-value">
                        <span class="reference-id" id="referenceId">Loading...</span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="status-badge status-confirmed" id="statusBadge">Confirmed</span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Name</span>
                    <span class="detail-value" id="fullName">Loading...</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value" id="email">Loading...</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value" id="phone">Loading...</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Clinic</span>
                    <span class="detail-value" id="clinic">Loading...</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Service</span>
                    <span class="detail-value" id="service">Loading...</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value" id="dateTime">Loading...</span>
                </div>
                
                <div class="detail-row" id="messageRow" style="display: none;">
                    <span class="detail-label">Message</span>
                    <span class="detail-value" id="message">Loading...</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Booked On</span>
                    <span class="detail-value" id="createdAt">Loading...</span>
                </div>
            </div>
            
            <div class="actions">
                <button class="btn btn-primary" onclick="window.print()">
                    🖨️ Print Confirmation
                </button>
                
                <a href="index.html" class="btn btn-secondary">
                    🏠 Back to Home
                </a>
                
                <a href="Book-Appointment.html" class="btn btn-success">
                    📅 Make Another Booking
                </a>
            </div>
            
            <div class="reschedule-section" id="rescheduleSection">
                <h3>Need to Reschedule?</h3>
                <p>You can reschedule your appointment using the link below, subject to our 12-hour policy.</p>
                <a href="#" class="btn btn-primary" id="rescheduleBtn">
                    📅 Reschedule Appointment
                </a>
            </div>
            
            <div class="policy-notice" id="policyNotice" style="display: none;">
                <h4>Rescheduling Policy</h4>
                <p>Rescheduling is unavailable within 12 hours of booking or within 12 hours of your appointment start. Please submit a new booking instead. For urgent issues, contact the clinic.</p>
            </div>
        </div>
    </div>

    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const ref = urlParams.get('ref');
        const token = urlParams.get('token');
        
        if (!ref || !token) {
            document.body.innerHTML = '<div class="container"><div class="confirmation-card"><h1>Invalid Confirmation Link</h1><p>This confirmation link is invalid or has expired.</p><a href="index.html" class="btn btn-primary">Back to Home</a></div></div>';
        } else {
            // Load booking details
            loadBookingDetails(ref, token);
        }
        
        async function loadBookingDetails(referenceId, rescheduleToken) {
            try {
                const response = await fetch(`get_booking.php?ref=${referenceId}&token=${rescheduleToken}`);
                const data = await response.json();
                
                if (data.success) {
                    const booking = data.booking;
                    
                    // Populate the form
                    document.getElementById('referenceId').textContent = booking.reference_id;
                    document.getElementById('fullName').textContent = booking.full_name;
                    document.getElementById('email').textContent = booking.email;
                    document.getElementById('phone').textContent = booking.phone;
                    document.getElementById('clinic').textContent = booking.preferred_clinic;
                    document.getElementById('service').textContent = booking.service;
                    document.getElementById('dateTime').textContent = formatDateTime(booking.preferred_date, booking.preferred_time);
                    document.getElementById('createdAt').textContent = formatDateTime(booking.created_at);
                    
                    if (booking.message) {
                        document.getElementById('message').textContent = booking.message;
                        document.getElementById('messageRow').style.display = 'flex';
                    }
                    
                    // Update status badge
                    updateStatusBadge(booking.status);
                    
                    // Check reschedule policy
                    checkReschedulePolicy(booking);
                    
                    // Set reschedule link
                    document.getElementById('rescheduleBtn').href = `reschedule.php?ref=${referenceId}&token=${rescheduleToken}`;
                    
                } else {
                    document.body.innerHTML = '<div class="container"><div class="confirmation-card"><h1>Booking Not Found</h1><p>This booking could not be found or the link has expired.</p><a href="index.html" class="btn btn-primary">Back to Home</a></div></div>';
                }
            } catch (error) {
                console.error('Error loading booking:', error);
                document.body.innerHTML = '<div class="container"><div class="confirmation-card"><h1>Error</h1><p>There was an error loading your booking details.</p><a href="index.html" class="btn btn-primary">Back to Home</a></div></div>';
            }
        }
        
        function updateStatusBadge(status) {
            const badge = document.getElementById('statusBadge');
            badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            badge.className = 'status-badge';
            
            switch (status) {
                case 'confirmed':
                    badge.classList.add('status-confirmed');
                    break;
                case 'rescheduled':
                    badge.classList.add('status-rescheduled');
                    break;
                case 'clinic-adjusted':
                    badge.classList.add('status-clinic-adjusted');
                    break;
            }
        }
        
        function checkReschedulePolicy(booking) {
            const now = new Date();
            const bookingTime = new Date(booking.created_at);
            const appointmentTime = new Date(`${booking.preferred_date} ${booking.preferred_time}`);
            
            const hoursSinceBooking = (now - bookingTime) / (1000 * 60 * 60);
            const hoursUntilAppointment = (appointmentTime - now) / (1000 * 60 * 60);
            
            if (hoursSinceBooking < 12 || hoursUntilAppointment < 12) {
                // Reschedule blocked
                document.getElementById('rescheduleSection').style.display = 'none';
                document.getElementById('policyNotice').style.display = 'block';
            }
        }
        
        function formatDateTime(dateStr, timeStr = null) {
            const date = new Date(dateStr);
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            let formatted = date.toLocaleDateString('en-US', options);
            
            if (timeStr) {
                const time = new Date(`2000-01-01T${timeStr}`);
                formatted += ` at ${time.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit',
                    hour12: true 
                })}`;
            }
            
            return formatted;
        }
    </script>
</body>
</html>
