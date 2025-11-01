<?php
/**
 * Native PHP Email Service for SmileBright Dental
 * Uses PHP's native mail() function (Base Version Compliant)
 * No external dependencies - Pure PHP/HTML/JavaScript/MySQL
 */

require_once __DIR__ . '/../config/email.php';

// Load SMTP function before class definition
if (!function_exists('sendSMTPEmail')) {
    require_once __DIR__ . '/smtp_mail.php';
}

class SmileBrightEmailService {
    private $config;
    
    public function __construct() {
        $this->config = $this->loadEmailConfig();
    }
    
    /**
     * Load email configuration from secure config file
     */
    private function loadEmailConfig() {
        return [
            'from_email' => defined('EMAIL_FROM') ? EMAIL_FROM : 'smilebright.info@gmail.com',
            'from_name' => defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Smile Bright Dental',
            'reply_to' => defined('EMAIL_REPLY_TO') ? EMAIL_REPLY_TO : (defined('EMAIL_FROM') ? EMAIL_FROM : ''),
            'bcc_admin' => defined('EMAIL_BCC_ADMIN') ? EMAIL_BCC_ADMIN : ''
        ];
    }
    
    /**
     * Send booking confirmation email to patient
     */
    public function sendBookingConfirmation($bookingData) {
        try {
            $to = $bookingData['email'];
            $subject = "Your SmileBright Booking Confirmation - Ref {$bookingData['reference_id']}";
            
            $htmlContent = $this->generateBookingConfirmationHTML($bookingData);
            $textContent = $this->generateBookingConfirmationText($bookingData);
            
            // Add BCC if configured
            $headers = $this->buildHeaders();
            if (!empty($this->config['bcc_admin'])) {
                $headers .= "Bcc: {$this->config['bcc_admin']}\r\n";
            }
            
            // Use SMTP function instead of mail() for Windows/XAMPP compatibility
            $result = sendSMTPEmail($to, $subject, $htmlContent, $headers);
            
            return [
                'success' => $result,
                'message' => $result ? 'Email sent successfully' : 'Email sending failed'
            ];
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send reschedule confirmation email
     */
    public function sendRescheduleConfirmation($bookingData) {
        try {
            $to = $bookingData['email'];
            $subject = "Reschedule Confirmed - Ref {$bookingData['reference_id']}";
            
            $htmlContent = $this->generateRescheduleConfirmationHTML($bookingData);
            $textContent = $this->generateRescheduleConfirmationText($bookingData);
            
            $headers = $this->buildHeaders();
            if (!empty($this->config['bcc_admin'])) {
                $headers .= "Bcc: {$this->config['bcc_admin']}\r\n";
            }
            
            // Use SMTP function instead of mail() for Windows/XAMPP compatibility
            $result = sendSMTPEmail($to, $subject, $htmlContent, $headers);
            
            return [
                'success' => $result,
                'message' => $result ? 'Email sent successfully' : 'Email sending failed'
            ];
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send clinic adjustment notification
     */
    public function sendClinicAdjustment($bookingData, $oldData = []) {
        try {
            $to = $bookingData['email'];
            $subject = "Appointment Adjusted - Ref {$bookingData['reference_id']}";
            
            $htmlContent = $this->generateClinicAdjustmentHTML($bookingData, $oldData);
            $textContent = $this->generateClinicAdjustmentText($bookingData, $oldData);
            
            $headers = $this->buildHeaders();
            
            // Use SMTP function instead of mail() for Windows/XAMPP compatibility
            $result = sendSMTPEmail($to, $subject, $htmlContent, $headers);
            
            return [
                'success' => $result,
                'message' => $result ? 'Email sent successfully' : 'Email sending failed'
            ];
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build email headers for mail() function
     */
    private function buildHeaders() {
        $fromEmail = $this->config['from_email'];
        $fromName = $this->config['from_name'];
        $replyTo = !empty($this->config['reply_to']) ? $this->config['reply_to'] : $fromEmail;
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        return $headers;
    }
    
    /**
     * Generate HTML content for booking confirmation
     */
    private function generateBookingConfirmationHTML($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $viewUrl = "http://" . $_SERVER['HTTP_HOST'] . "/SmileBrightbase/public/booking/manage_booking.php?ref={$bookingData['reference_id']}";
        $doctorName = isset($bookingData['doctor_name']) && !empty($bookingData['doctor_name']) ? htmlspecialchars($bookingData['doctor_name']) : 'Not specified';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Appointment Confirmation - SmileBright Dental</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f7fb; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #1f4f86; color: white; padding: 30px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px 20px; }
                .appointment-details { margin: 20px 0; }
                .detail-row { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 6px; }
                .detail-label { font-weight: 600; color: #6b7a90; font-size: 14px; margin-bottom: 5px; }
                .detail-value { font-size: 16px; color: #243042; }
                .action-section { margin-top: 30px; text-align: center; }
                .reschedule-button { display: inline-block; padding: 12px 24px; background: #1f4f86; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7a90; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>YOUR APPOINTMENT IS BOOKED</h1>
                    <p>We've reserved your slot at SmileBright Dental</p>
                </div>
                
                <div class='content'>
                    <div style='font-size: 18px; margin-bottom: 15px;'>Hi {$bookingData['full_name']},</div>
                    
                    <p>We've confirmed your appointment. Here are the details:</p>
                    
                    <div class='appointment-details'>
                        <div class='detail-row'>
                            <div class='detail-label'>When</div>
                            <div class='detail-value'>{$formattedDate} at {$formattedTime}</div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-label'>Dentist</div>
                            <div class='detail-value'>{$doctorName}</div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-label'>Clinic</div>
                            <div class='detail-value'>{$clinicInfo['name']}</div>
                            <div style='font-size: 14px; color: #6b7a90; margin-top: 4px;'>{$clinicInfo['address']}</div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-label'>Service</div>
                            <div class='detail-value'>{$bookingData['service']}</div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-label'>Reference ID</div>
                            <div class='detail-value'>{$bookingData['reference_id']}</div>
                        </div>
                    </div>

                    <div class='action-section'>
                        <p><strong>Need to change your appointment?</strong></p>
                        <a href='{$viewUrl}' class='reschedule-button'>MANAGE BOOKING</a>
                    </div>
                </div>
                
                <div class='footer'>
                    <div style='font-size: 12px; color: #6b7a90; margin-bottom: 10px;'>
                        Appointment ID: #{$bookingData['id']} • Created " . date('M j, Y \a\t g:i A') . "
                    </div>
                    <div style='font-weight: 600; color: #1f4f86; font-size: 14px;'>— SmileBright Dental</div>
                    <div style='color: #6b7a90; font-size: 12px; margin-top: 5px;'>Your trusted dental care provider in Singapore</div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate plain text content for booking confirmation
     */
    private function generateBookingConfirmationText($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $doctorName = isset($bookingData['doctor_name']) && !empty($bookingData['doctor_name']) ? $bookingData['doctor_name'] : 'Not specified';
        
        return "YOUR APPOINTMENT IS BOOKED\n\nHi {$bookingData['full_name']},\n\nWe've confirmed your appointment. Here are the details:\n\nWhen: {$formattedDate} at {$formattedTime}\nDentist: {$doctorName}\nClinic: {$clinicInfo['name']}\n{$clinicInfo['address']}\nService: {$bookingData['service']}\nReference ID: {$bookingData['reference_id']}\n\n---\nAppointment ID: #{$bookingData['id']} • Created " . date('M j, Y \a\t g:i A') . "\n— SmileBright Dental\nYour trusted dental care provider in Singapore";
    }
    
    /**
     * Generate HTML content for reschedule confirmation
     */
    private function generateRescheduleConfirmationHTML($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f7fb; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #1f4f86; color: white; padding: 20px; border-radius: 8px; margin: -30px -30px 20px -30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0;'>RESCHEDULE CONFIRMED</h1>
                </div>
                <p>Hi {$bookingData['full_name']},</p>
                <p>Great news! Your appointment reschedule has been confirmed.</p>
                <p><strong>New Date & Time:</strong> {$formattedDate} at {$formattedTime}</p>
                <p><strong>Clinic:</strong> {$clinicInfo['name']}</p>
                <p><strong>Service:</strong> {$bookingData['service']}</p>
                <p><strong>Reference ID:</strong> {$bookingData['reference_id']}</p>
                <p>— SmileBright Dental</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate plain text content for reschedule confirmation
     */
    private function generateRescheduleConfirmationText($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        
        return "RESCHEDULE CONFIRMED\n\nHi {$bookingData['full_name']},\n\nGreat news! Your appointment reschedule has been confirmed.\n\nNew Date & Time: {$formattedDate} at {$formattedTime}\nClinic: {$clinicInfo['name']}\nService: {$bookingData['service']}\nReference ID: {$bookingData['reference_id']}\n\n— SmileBright Dental";
    }
    
    /**
     * Generate HTML content for clinic adjustment
     */
    private function generateClinicAdjustmentHTML($bookingData, $oldData) {
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $oldFormattedDate = !empty($oldData['date']) ? formatEmailDate($oldData['date']) : '';
        $oldFormattedTime = !empty($oldData['time']) ? formatEmailTime($oldData['time']) : '';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f7fb; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #1f4f86; color: white; padding: 20px; border-radius: 8px; margin: -30px -30px 20px -30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0;'>APPOINTMENT ADJUSTED</h1>
                </div>
                <p>Hi {$bookingData['full_name']},</p>
                <p>We've made some adjustments to your appointment.</p>
                " . (!empty($oldFormattedDate) ? "<p><strong>Date/Time:</strong> {$oldFormattedDate} {$oldFormattedTime} → {$formattedDate} {$formattedTime}</p>" : "<p><strong>Date/Time:</strong> {$formattedDate} {$formattedTime}</p>") . "
                <p>— SmileBright Dental</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate plain text content for clinic adjustment
     */
    private function generateClinicAdjustmentText($bookingData, $oldData) {
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $oldFormattedDate = !empty($oldData['date']) ? formatEmailDate($oldData['date']) : '';
        $oldFormattedTime = !empty($oldData['time']) ? formatEmailTime($oldData['time']) : '';
        
        return "APPOINTMENT ADJUSTED\n\nHi {$bookingData['full_name']},\n\nWe've made some adjustments to your appointment.\n\n" . (!empty($oldFormattedDate) ? "Date/Time: {$oldFormattedDate} {$oldFormattedTime} → {$formattedDate} {$formattedTime}\n" : "Date/Time: {$formattedDate} {$formattedTime}\n") . "\n— SmileBright Dental";
    }
    
    /**
     * Send clinic notification email
     */
    public function sendClinicNotification($subject, $htmlContent, $textContent, $clinicEmail = null) {
        try {
            $to = $clinicEmail ?: (defined('EMAIL_BCC_ADMIN') && !empty(EMAIL_BCC_ADMIN) ? EMAIL_BCC_ADMIN : 'smilebrightsg.info@gmail.com');
            $headers = $this->buildHeaders();
            
            // Use SMTP function instead of mail() for Windows/XAMPP compatibility
            $result = sendSMTPEmail($to, $subject, $htmlContent, $headers);
            
            return [
                'success' => $result,
                'message' => $result ? 'Email sent successfully' : 'Email sending failed'
            ];
        } catch (Exception $e) {
            error_log('Clinic email sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send status update notification to patient
     */
    public function sendStatusUpdateNotification($bookingData, $oldStatus, $newStatus) {
        try {
            $to = $bookingData['email'];
            $statusMessages = [
                'cancelled' => ['subject' => 'Appointment Cancelled', 'msg' => 'Your appointment has been cancelled.'],
                'no-show' => ['subject' => 'Appointment Marked as No-Show', 'msg' => 'Your appointment has been marked as a no-show.'],
                'completed' => ['subject' => 'Appointment Completed', 'msg' => 'Your appointment has been completed. Thank you!'],
                'confirmed' => ['subject' => 'Appointment Confirmed', 'msg' => 'Your appointment status has been updated to confirmed.'],
                'rescheduled' => ['subject' => 'Appointment Rescheduled', 'msg' => 'Your appointment has been rescheduled.']
            ];
            
            $statusInfo = $statusMessages[$newStatus] ?? ['subject' => 'Appointment Status Updated', 'msg' => 'Your appointment status has been updated.'];
            $subject = $statusInfo['subject'] . ' - Ref ' . $bookingData['reference_id'];
            
            $htmlContent = $this->generateStatusUpdateHTML($bookingData, $statusInfo, $newStatus);
            $textContent = $this->generateStatusUpdateText($bookingData, $statusInfo, $newStatus);
            
            $headers = $this->buildHeaders();
            if (!empty($this->config['bcc_admin'])) {
                $headers .= "Bcc: {$this->config['bcc_admin']}\r\n";
            }
            
            // Use SMTP function instead of mail() for Windows/XAMPP compatibility
            $result = sendSMTPEmail($to, $subject, $htmlContent, $headers);
            
            return [
                'success' => $result,
                'message' => $result ? 'Email sent successfully' : 'Email sending failed'
            ];
        } catch (Exception $e) {
            error_log('Status update email failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate HTML for status update notification
     */
    private function generateStatusUpdateHTML($bookingData, $statusInfo, $newStatus) {
        $appointmentDate = formatEmailDate($bookingData['preferred_date']);
        $appointmentTime = formatEmailTime($bookingData['preferred_time']);
        $serviceNames = [
            'general' => 'General Checkup', 'cleaning' => 'Teeth Cleaning', 'filling' => 'Dental Filling',
            'extraction' => 'Tooth Extraction', 'braces' => 'Braces Consultation', 'whitening' => 'Teeth Whitening',
            'implant' => 'Dental Implant', 'others' => 'Others'
        ];
        $serviceName = isset($bookingData['service_key']) ? ($serviceNames[$bookingData['service_key']] ?? ucfirst($bookingData['service_key'])) : ($bookingData['service'] ?? 'N/A');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f7fb; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #1f4f86; color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .detail-row { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 6px; }
                .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: 600; margin: 10px 0; }
                .status-cancelled { background: #fee2e2; color: #991b1b; }
                .status-no-show { background: #fef3c7; color: #92400e; }
                .status-completed { background: #d1fae5; color: #065f46; }
                .status-confirmed { background: #dbeafe; color: #1e40af; }
                .status-rescheduled { background: #e0e7ff; color: #5b21b6; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$statusInfo['subject']}</h1>
                </div>
                <div class='content'>
                    <p>Hi {$bookingData['full_name']},</p>
                    <p>{$statusInfo['msg']}</p>
                    <div class='status-badge status-{$newStatus}'>Status: " . ucfirst(str_replace('-', ' ', $newStatus)) . "</div>
                    <div class='detail-row'><strong>Reference ID:</strong> {$bookingData['reference_id']}</div>
                    <div class='detail-row'><strong>Date & Time:</strong> {$appointmentDate} at {$appointmentTime}</div>
                    <div class='detail-row'><strong>Doctor:</strong> {$bookingData['doctor_name']}</div>
                    <div class='detail-row'><strong>Service:</strong> {$serviceName}</div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate text for status update notification
     */
    private function generateStatusUpdateText($bookingData, $statusInfo, $newStatus) {
        $appointmentDate = formatEmailDate($bookingData['preferred_date']);
        $appointmentTime = formatEmailTime($bookingData['preferred_time']);
        
        return "{$statusInfo['subject']}\n\nHi {$bookingData['full_name']},\n\n{$statusInfo['msg']}\n\nStatus: " . ucfirst(str_replace('-', ' ', $newStatus)) . "\nReference ID: {$bookingData['reference_id']}\nDate: {$appointmentDate}\nTime: {$appointmentTime}\nDoctor: {$bookingData['doctor_name']}\n\n— SmileBright Dental";
    }
}

