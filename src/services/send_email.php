<?php
/**
 * Email Sending Function for Smile Bright Dental
 * Sends appointment confirmation emails with reschedule/cancel links
 */

require_once __DIR__ . '/email_config.php';

function sendBookingConfirmation($appointmentData) {
    // Extract data
    $to = $appointmentData['email'];
    $firstName = $appointmentData['first_name'];
    $lastName = $appointmentData['last_name'];
    $phone = $appointmentData['phone'];
    $date = formatEmailDate($appointmentData['date']);
    $time = formatEmailTime($appointmentData['time']);
    $clinicName = $appointmentData['clinic'];
    $clinicInfo = getClinicInfo($clinicName);
    $clinicAddress = $clinicInfo['address'];
    $service = $appointmentData['service'];
    $experience = $appointmentData['experience'] ?? 'Not provided';
    $message = $appointmentData['message'] ?? 'None';
    $appointmentId = $appointmentData['id'];
    $rescheduleToken = $appointmentData['reschedule_token'];
    $createdAt = date('M j, Y \a\t g:i A', strtotime($appointmentData['created_at']));
    $tokenExpiresAt = date('M j, Y', strtotime($appointmentData['token_expires_at']));
    
    // Build URLs using config
    $rescheduleUrl = getRescheduleUrl($appointmentId, $rescheduleToken);
    $cancelUrl = getCancelUrl($appointmentId, $rescheduleToken);
    
    // Email subject
    $subject = "✔ Appointment booked — {$date} {$time} at {$clinicName}";
    
    // Plain text version
    $textBody = "Hi {$firstName},\n\n";
    $textBody .= "Your appointment is confirmed.\n\n";
    $textBody .= "• When: {$date} at {$time} (" . TIMEZONE . ")\n";
    $textBody .= "• Clinic: {$clinicName}\n";
    $textBody .= "  {$clinicAddress}\n";
    $textBody .= "• Service: {$service}\n\n";
    $textBody .= "Notes you shared:\n";
    $textBody .= "- Experience: {$experience}\n";
    $textBody .= "- Message: {$message}\n\n";
    $textBody .= "Need to reschedule? Use this link:\n";
    $textBody .= "{$rescheduleUrl}\n";
    $textBody .= "(Valid until {$tokenExpiresAt}.)\n\n";
    $textBody .= "If you didn't make this booking, please contact us at " . SUPPORT_PHONE . " or " . SUPPORT_EMAIL . ".\n\n";
    $textBody .= "Appointment ID: #{$appointmentId} • Created {$createdAt}\n\n";
    $textBody .= "— " . EMAIL_FROM_NAME;
    
    // HTML version
    $htmlBody = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Appointment Confirmation</title>
</head>
<body style=\"font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#223344;background:#f5f7fb;margin:0;padding:20px;\">
    <div style=\"max-width:600px;margin:0 auto;background:#ffffff;border-radius:14px;box-shadow:0 8px 24px rgba(20,40,80,.08);overflow:hidden;\">
        <!-- Header -->
        <div style=\"background:#1f4f86;color:#fff;padding:30px 26px;text-align:left;\">
            <h1 style=\"margin:0 0 10px;font-weight:900;font-size:24px;color:#fff;\">Your appointment is booked 👍</h1>
            <p style=\"margin:0;color:rgba(255,255,255,.9);font-size:15px;\">We've reserved your slot at Smile Bright Dental</p>
        </div>
        
        <!-- Body -->
        <div style=\"padding:30px 26px;\">
            <p style=\"margin:0 0 20px;font-size:16px;\">Hi <strong>{$firstName}</strong>,</p>
            
            <p style=\"margin:0 0 20px;font-size:15px;\">We've confirmed your appointment. Here are the details:</p>
            
            <div style=\"background:#f5f7fb;border-left:4px solid #1f4f86;padding:16px 20px;margin:0 0 24px;border-radius:8px;\">
                <p style=\"margin:0 0 10px;font-size:15px;\"><strong>📅 When:</strong> {$date} at {$time}</p>
                <p style=\"margin:0 0 10px;font-size:14px;color:" . BRAND_MUTED_TEXT . ";padding-left:20px;\">" . TIMEZONE . " timezone</p>
                
                <p style=\"margin:16px 0 10px;font-size:15px;\"><strong>🏥 Clinic:</strong> {$clinicName}</p>
                <p style=\"margin:0 0 10px;font-size:14px;color:" . BRAND_MUTED_TEXT . ";padding-left:20px;\">{$clinicAddress}</p>
                
                <p style=\"margin:16px 0 0;font-size:15px;\"><strong>🦷 Service:</strong> {$service}</p>
            </div>
            
            <div style=\"background:#fff7ed;border:1px solid #fed7aa;padding:16px 20px;margin:0 0 24px;border-radius:8px;\">
                <p style=\"margin:0 0 12px;font-weight:700;font-size:14px;color:#9a3412;\">📝 Notes you shared</p>
                <p style=\"margin:0 0 8px;font-size:14px;\"><strong>Experience:</strong></p>
                <p style=\"margin:0 0 12px;font-size:14px;color:#6b7a90;font-style:italic;padding-left:12px;\">{$experience}</p>
                <p style=\"margin:0 0 8px;font-size:14px;\"><strong>Message:</strong></p>
                <p style=\"margin:0;font-size:14px;color:#6b7a90;font-style:italic;padding-left:12px;\">{$message}</p>
            </div>
            
            <p style=\"margin:0 0 16px;font-size:15px;\">Need to change your appointment?</p>
            
            <div style=\"text-align:center;margin:0 0 20px;\">
                <a href=\"{$rescheduleUrl}\" style=\"display:inline-block;padding:12px 28px;background:" . BRAND_PRIMARY_COLOR . ";color:#fff;text-decoration:none;border-radius:999px;font-weight:700;font-size:15px;box-shadow:0 4px 6px rgba(31,79,134,.2);\">Reschedule Appointment</a>
            </div>
            
            <p style=\"margin:0 0 6px;font-size:13px;color:#6b7a90;text-align:center;\">This link is valid until {$tokenExpiresAt}</p>
            <p style=\"margin:0 0 24px;font-size:13px;color:#6b7a90;text-align:center;\">
                To cancel, <a href=\"{$cancelUrl}\" style=\"color:#c92a2a;\">click here</a>
            </p>
            
            <div style=\"border-top:2px solid " . BRAND_BORDER . ";padding-top:20px;margin-top:24px;\">
                <p style=\"margin:0 0 12px;font-size:14px;color:" . BRAND_MUTED_TEXT . ";\">
                    <strong>⚠️ Didn't make this booking?</strong><br>
                    Please contact us immediately at <a href=\"tel:" . str_replace(' ', '', SUPPORT_PHONE) . "\" style=\"color:" . BRAND_PRIMARY_COLOR . ";\">" . SUPPORT_PHONE . "</a> 
                    or <a href=\"mailto:" . SUPPORT_EMAIL . "\" style=\"color:" . BRAND_PRIMARY_COLOR . ";\">" . SUPPORT_EMAIL . "</a>
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style=\"background:" . BRAND_LIGHT_BG . ";padding:20px 26px;border-top:2px solid " . BRAND_BORDER . ";\">
            <p style=\"margin:0 0 8px;font-size:12px;color:" . BRAND_MUTED_TEXT . ";\">
                Appointment ID: <strong>#{$appointmentId}</strong> • Created {$createdAt}
            </p>
            <p style=\"margin:0;font-size:14px;color:#223344;font-weight:600;\">
                — " . EMAIL_FROM_NAME . "
            </p>
            <p style=\"margin:8px 0 0;font-size:12px;color:" . BRAND_MUTED_TEXT . ";\">
                Your trusted dental care provider in Singapore
            </p>
        </div>
    </div>
    
    <!-- Footer note -->
    <div style=\"max-width:600px;margin:16px auto 0;text-align:center;\">
        <p style=\"font-size:11px;color:#9ca3af;margin:0;\">
            This is an automated confirmation email. Please do not reply directly to this email.<br>
            For inquiries, contact us at " . SUPPORT_EMAIL . "
        </p>
    </div>
</body>
</html>";
    
    // Email headers
    $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . EMAIL_REPLY_TO . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"boundary-mixed\"\r\n";
    
    // Build multipart message
    $message = "--boundary-mixed\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $textBody . "\r\n\r\n";
    $message .= "--boundary-mixed\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";
    $message .= "--boundary-mixed--";
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

/**
 * Generate a secure reschedule token
 */
function generateRescheduleToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Get token expiry date (configured days from now)
 */
function getTokenExpiryDate() {
    return date('Y-m-d H:i:s', getTokenExpiryTimestamp());
}

