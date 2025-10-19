<?php
/**
 * PHP Email Service for SmileBright Dental
 * Uses PHPMailer with Gmail SMTP for secure email delivery
 */

require_once 'vendor/autoload.php';
require_once 'email_config_secure.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SmileBrightEmailService {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->config = $this->loadEmailConfig();
        $this->initializeMailer();
    }
    
    /**
     * Load email configuration from secure config file
     */
    private function loadEmailConfig() {
        return [
            'smtp_host' => SMTP_HOST,
            'smtp_port' => SMTP_PORT,
            'smtp_secure' => SMTP_SECURE,
            'smtp_user' => SMTP_USER,
            'smtp_pass' => SMTP_PASS,
            'from_email' => EMAIL_FROM,
            'from_name' => EMAIL_FROM_NAME,
            'reply_to' => EMAIL_REPLY_TO,
            'bcc_admin' => EMAIL_BCC_ADMIN
        ];
    }
    
    /**
     * Initialize PHPMailer with Gmail SMTP configuration
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_user'];
            $this->mailer->Password = $this->config['smtp_pass'];
            $this->mailer->SMTPSecure = $this->config['smtp_secure'] ? 
                PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['smtp_port'];
            
            // Development SSL override (for localhost testing)
            if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
                $this->mailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }
            
            // Recipients
            $this->mailer->setFrom($this->config['smtp_user'], $this->config['from_name']);
            $this->mailer->addReplyTo($this->config['reply_to'], $this->config['from_name']);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("PHPMailer initialization failed: " . $e->getMessage());
            throw new Exception("Email service initialization failed");
        }
    }
    
    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($bookingData) {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipient
            $this->mailer->addAddress($bookingData['email'], $bookingData['full_name']);
            
            // Add BCC admin if configured
            if (!empty($this->config['bcc_admin'])) {
                $this->mailer->addBCC($this->config['bcc_admin']);
            }
            
            // Set subject
            $this->mailer->Subject = "✅ Your SmileBright booking — Ref {$bookingData['reference_id']}";
            
            // Generate email content
            $htmlContent = $this->generateBookingConfirmationHTML($bookingData);
            $textContent = $this->generateBookingConfirmationText($bookingData);
            
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = $textContent;
            
            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Booking confirmation email sent successfully to: {$bookingData['email']}");
                return [
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'to' => $bookingData['email'],
                    'subject' => $this->mailer->Subject
                ];
            } else {
                throw new Exception("Email sending failed");
            }
            
        } catch (Exception $e) {
            error_log("Booking confirmation email failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'to' => $bookingData['email'] ?? 'unknown'
            ];
        }
    }
    
    /**
     * Send clinic adjustment notification
     */
    public function sendClinicAdjustment($bookingData, $oldData = []) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($bookingData['email'], $bookingData['full_name']);
            
            if (!empty($this->config['bcc_admin'])) {
                $this->mailer->addBCC($this->config['bcc_admin']);
            }
            
            $this->mailer->Subject = "📅 Appointment adjusted — Ref {$bookingData['reference_id']}";
            
            $htmlContent = $this->generateClinicAdjustmentHTML($bookingData, $oldData);
            $textContent = $this->generateClinicAdjustmentText($bookingData, $oldData);
            
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = $textContent;
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Clinic adjustment email sent successfully to: {$bookingData['email']}");
                return [
                    'success' => true,
                    'message' => 'Adjustment notification sent successfully',
                    'to' => $bookingData['email'],
                    'subject' => $this->mailer->Subject
                ];
            } else {
                throw new Exception("Email sending failed");
            }
            
        } catch (Exception $e) {
            error_log("Clinic adjustment email failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'to' => $bookingData['email'] ?? 'unknown'
            ];
        }
    }
    
    /**
     * Send reschedule confirmation
     */
    public function sendRescheduleConfirmation($bookingData) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($bookingData['email'], $bookingData['full_name']);
            
            if (!empty($this->config['bcc_admin'])) {
                $this->mailer->addBCC($this->config['bcc_admin']);
            }
            
            $this->mailer->Subject = "✅ Rescheduled confirmed — Ref {$bookingData['reference_id']}";
            
            $htmlContent = $this->generateRescheduleConfirmationHTML($bookingData);
            $textContent = $this->generateRescheduleConfirmationText($bookingData);
            
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = $textContent;
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Reschedule confirmation email sent successfully to: {$bookingData['email']}");
                return [
                    'success' => true,
                    'message' => 'Reschedule confirmation sent successfully',
                    'to' => $bookingData['email'],
                    'subject' => $this->mailer->Subject
                ];
            } else {
                throw new Exception("Email sending failed");
            }
            
        } catch (Exception $e) {
            error_log("Reschedule confirmation email failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'to' => $bookingData['email'] ?? 'unknown'
            ];
        }
    }
    
    /**
     * Test email configuration
     */
    public function testConfiguration($testEmail = null) {
        try {
            $testEmail = $testEmail ?: $this->config['smtp_user'];
            
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($testEmail);
            $this->mailer->Subject = "🔐 Gmail Configuration Test - SmileBright";
            
            $htmlContent = $this->generateTestEmailHTML();
            $textContent = $this->generateTestEmailText();
            
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = $textContent;
            
            $result = $this->mailer->send();
            
            return [
                'success' => true,
                'message' => 'Test email sent successfully',
                'to' => $testEmail,
                'config' => [
                    'host' => $this->config['smtp_host'],
                    'port' => $this->config['smtp_port'],
                    'secure' => $this->config['smtp_secure'],
                    'user' => $this->config['smtp_user']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Generate booking confirmation HTML
     */
    private function generateBookingConfirmationHTML($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $viewUrl = "confirm.php?ref={$bookingData['reference_id']}&token={$bookingData['reschedule_token']}";
        $cancelUrl = "cancel.php?ref={$bookingData['reference_id']}&token={$bookingData['reschedule_token']}";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Appointment Confirmation - SmileBright Dental</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f7fb; }
                .container { background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); overflow: hidden; }
                .header { background: linear-gradient(135deg, #1f4f86 0%, #173e6c 100%); color: white; padding: 30px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
                .content { padding: 30px 20px; }
                .greeting { font-size: 18px; margin-bottom: 20px; color: #1f4f86; }
                .appointment-details { background-color: #f8f9fa; border: 1px solid #e7ebf3; border-radius: 6px; padding: 20px; margin: 20px 0; }
                .detail-row { display: flex; margin-bottom: 12px; align-items: center; }
                .detail-icon { width: 20px; margin-right: 12px; text-align: center; }
                .detail-content { flex: 1; }
                .detail-label { font-weight: 600; color: #1f4f86; font-size: 14px; }
                .detail-value { color: #333; font-size: 16px; }
                .action-section { text-align: center; margin: 30px 0; }
                .reschedule-button { display: inline-block; background: linear-gradient(135deg, #1f4f86 0%, #173e6c 100%); color: white; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: 600; font-size: 16px; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e7ebf3; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ YOUR APPOINTMENT IS BOOKED</h1>
                    <p>We've reserved your slot at SmileBright Dental</p>
                </div>
                
                <div class='content'>
                    <div class='greeting'>Hi {$bookingData['full_name']},</div>
                    
                    <p>We've confirmed your appointment. Here are the details:</p>
                    
                    <div class='appointment-details'>
                        <div class='detail-row'>
                            <div class='detail-icon'>📅</div>
                            <div class='detail-content'>
                                <div class='detail-label'>When</div>
                                <div class='detail-value'>{$formattedDate} at {$formattedTime}</div>
                            </div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-icon'>🏥</div>
                            <div class='detail-content'>
                                <div class='detail-label'>Clinic</div>
                                <div class='detail-value'>{$clinicInfo['name']}</div>
                                <div style='font-size: 14px; color: #6b7a90; margin-top: 4px;'>{$clinicInfo['address']}</div>
                            </div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-icon'>🦷</div>
                            <div class='detail-content'>
                                <div class='detail-label'>Service</div>
                                <div class='detail-value'>{$bookingData['service']}</div>
                            </div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-icon'>📞</div>
                            <div class='detail-content'>
                                <div class='detail-label'>Reference ID</div>
                                <div class='detail-value'>{$bookingData['reference_id']}</div>
                            </div>
                        </div>
                    </div>

                    <div class='action-section'>
                        <p><strong>Need to change your appointment?</strong></p>
                        <a href='{$viewUrl}' class='reschedule-button'>RESCHEDULE APPOINTMENT</a>
                        <p style='margin-top: 15px;'><a href='{$cancelUrl}' style='color: #dc3545; text-decoration: none;'>Cancel this appointment</a></p>
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
     * Generate booking confirmation text
     */
    private function generateBookingConfirmationText($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $viewUrl = "confirm.php?ref={$bookingData['reference_id']}&token={$bookingData['reschedule_token']}";
        
        return "YOUR APPOINTMENT IS BOOKED ✅\n\nHi {$bookingData['full_name']},\n\nWe've confirmed your appointment. Here are the details:\n\n📅 When: {$formattedDate} at {$formattedTime}\n🏥 Clinic: {$clinicInfo['name']}\n   {$clinicInfo['address']}\n🦷 Service: {$bookingData['service']}\n📞 Reference ID: {$bookingData['reference_id']}\n\nNeed to change your appointment?\nReschedule: {$viewUrl}\n\n---\nAppointment ID: #{$bookingData['id']} • Created " . date('M j, Y \a\t g:i A') . "\n— SmileBright Dental\nYour trusted dental care provider in Singapore";
    }
    
    /**
     * Generate clinic adjustment HTML
     */
    private function generateClinicAdjustmentHTML($bookingData, $oldData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $oldFormattedDate = !empty($oldData['preferred_date']) ? formatEmailDate($oldData['preferred_date']) : '';
        $oldFormattedTime = !empty($oldData['preferred_time']) ? formatEmailTime($oldData['preferred_time']) : '';
        $viewUrl = "confirm.php?ref={$bookingData['reference_id']}&token={$bookingData['reschedule_token']}";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Appointment Adjusted - SmileBright Dental</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .change-summary { background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 20px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>📅 APPOINTMENT ADJUSTED</h1>
                <p>Your appointment details have been updated</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>Hi {$bookingData['full_name']},</div>
                
                <p>We've made some adjustments to your appointment. Here's what changed:</p>
                
                <div class='change-summary'>
                    <h3>📋 Change Summary</h3>
                    " . (!empty($oldFormattedDate) ? "<p>📅 Date/Time: {$oldFormattedDate} {$oldFormattedTime} → {$formattedDate} {$formattedTime}</p>" : "") . "
                    <p>📞 Reference ID: {$bookingData['reference_id']}</p>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <p><strong>View your updated appointment details:</strong></p>
                    <a href='{$viewUrl}' style='display: inline-block; background: #1f4f86; color: white; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: 600;'>VIEW APPOINTMENT</a>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate clinic adjustment text
     */
    private function generateClinicAdjustmentText($bookingData, $oldData) {
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $oldFormattedDate = !empty($oldData['preferred_date']) ? formatEmailDate($oldData['preferred_date']) : '';
        $oldFormattedTime = !empty($oldData['preferred_time']) ? formatEmailTime($oldData['preferred_time']) : '';
        $viewUrl = "confirm.php?ref={$bookingData['reference_id']}&token={$bookingData['reschedule_token']}";
        
        return "APPOINTMENT ADJUSTED 📅\n\nHi {$bookingData['full_name']},\n\nWe've made some adjustments to your appointment. Here's what changed:\n\n" . 
               (!empty($oldFormattedDate) ? "📅 Date/Time: {$oldFormattedDate} {$oldFormattedTime} → {$formattedDate} {$formattedTime}\n" : "") . 
               "📞 Reference ID: {$bookingData['reference_id']}\n\nView your updated appointment: {$viewUrl}";
    }
    
    /**
     * Generate reschedule confirmation HTML
     */
    private function generateRescheduleConfirmationHTML($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $viewUrl = "confirm.php?ref={$bookingData['reference_id']}&token={$bookingData['reschedule_token']}";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reschedule Confirmed - SmileBright Dental</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .appointment-details { background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; padding: 20px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>✅ RESCHEDULE CONFIRMED</h1>
                <p>Your appointment has been successfully updated</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>Hi {$bookingData['full_name']},</div>
                
                <p>Great news! Your appointment reschedule has been confirmed. Here are your updated details:</p>
                
                <div class='appointment-details'>
                    <h3>📅 New Date & Time</h3>
                    <p>{$formattedDate} at {$formattedTime}</p>
                    <h3>🏥 Clinic</h3>
                    <p>{$clinicInfo['name']}</p>
                    <h3>🦷 Service</h3>
                    <p>{$bookingData['service']}</p>
                    <h3>📞 Reference ID</h3>
                    <p>{$bookingData['reference_id']}</p>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <p><strong>View your appointment details:</strong></p>
                    <a href='{$viewUrl}' style='display: inline-block; background: #1f4f86; color: white; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: 600;'>VIEW APPOINTMENT</a>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate reschedule confirmation text
     */
    private function generateRescheduleConfirmationText($bookingData) {
        $clinicInfo = getClinicInfo($bookingData['preferred_clinic']);
        $formattedDate = formatEmailDate($bookingData['preferred_date']);
        $formattedTime = formatEmailTime($bookingData['preferred_time']);
        $viewUrl = "confirm.php?ref={$bookingData['reference_id']}&token={$bookingData['reschedule_token']}";
        
        return "RESCHEDULE CONFIRMED ✅\n\nHi {$bookingData['full_name']},\n\nGreat news! Your appointment reschedule has been confirmed.\n\n📅 New Date & Time: {$formattedDate} at {$formattedTime}\n🏥 Clinic: {$clinicInfo['name']}\n🦷 Service: {$bookingData['service']}\n📞 Reference ID: {$bookingData['reference_id']}\n\nView your appointment: {$viewUrl}";
    }
    
    /**
     * Generate test email HTML
     */
    private function generateTestEmailHTML() {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Gmail Configuration Test</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1f4f86; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🔐 Gmail Configuration Test</h1>
            </div>
            <div class='content'>
                <h2>✅ Test Successful!</h2>
                <p>Your Gmail SMTP configuration is working correctly.</p>
                <p><strong>Configuration Details:</strong></p>
                <ul>
                    <li>SMTP Host: {$this->config['smtp_host']}</li>
                    <li>Port: {$this->config['smtp_port']}</li>
                    <li>Secure: " . ($this->config['smtp_secure'] ? 'Yes (SSL)' : 'No (STARTTLS)') . "</li>
                    <li>From: {$this->config['smtp_user']}</li>
                    <li>Test Time: " . date('Y-m-d H:i:s') . "</li>
                </ul>
                <p style='color: #28a745; font-weight: bold;'>🎉 Your SmileBright notification system is ready!</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate test email text
     */
    private function generateTestEmailText() {
        return "Gmail Configuration Test Successful!\n\nYour Gmail SMTP configuration is working correctly.\n\nConfiguration Details:\n- SMTP Host: {$this->config['smtp_host']}\n- Port: {$this->config['smtp_port']}\n- Secure: " . ($this->config['smtp_secure'] ? 'Yes (SSL)' : 'No (STARTTLS)') . "\n- From: {$this->config['smtp_user']}\n- Test Time: " . date('Y-m-d H:i:s') . "\n\n🎉 Your SmileBright notification system is ready!";
    }
}

// Helper function for easy integration
function sendBookingEmail($bookingData) {
    try {
        $emailService = new SmileBrightEmailService();
        return $emailService->sendBookingConfirmation($bookingData);
    } catch (Exception $e) {
        error_log("Email service error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Test function
function testEmailConfiguration($testEmail = null) {
    try {
        $emailService = new SmileBrightEmailService();
        return $emailService->testConfiguration($testEmail);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
