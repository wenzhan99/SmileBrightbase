<?php
// SmileBright Email Service
// Handles sending confirmation and update emails

class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $fromEmail;
    private $fromName;
    private $replyToEmail;
    private $replyToName;
    
    public function __construct() {
        // Load configuration from environment or config file
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? 465;
        $this->smtpUser = $_ENV['SMTP_USER'] ?? '';
        $this->smtpPass = $_ENV['SMTP_PASS'] ?? '';
        $this->fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'smilebrightsg.info@gmail.com';
        $this->fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Smile Bright Dental';
        $this->replyToEmail = $_ENV['SMTP_REPLYTO_EMAIL'] ?? 'smilebrightsg.info@gmail.com';
        $this->replyToName = $_ENV['SMTP_REPLYTO_NAME'] ?? 'Smile Bright Reception';
    }
    
    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($bookingData) {
        $patientEmail = $bookingData['patient']['email'];
        $patientName = $bookingData['patient']['firstName'] . ' ' . $bookingData['patient']['lastName'];
        
        // Prepare template data
        $templateData = [
            'referenceId' => $bookingData['referenceId'],
            'patient' => [
                'firstName' => $bookingData['patient']['firstName'],
                'lastName' => $bookingData['patient']['lastName'],
                'email' => $patientEmail
            ],
            'appointment' => [
                'dentistName' => $bookingData['appointment']['dentistName'],
                'clinicName' => $bookingData['appointment']['clinicName'],
                'serviceLabel' => $bookingData['appointment']['serviceLabel'],
                'dateDisplay' => $this->formatDate($bookingData['appointment']['dateIso']),
                'timeDisplay' => $this->formatTime($bookingData['appointment']['time24'])
            ],
            'manageUrl' => 'http://localhost/SmileBright/public/booking/manage_booking.html?ref=' . $bookingData['referenceId']
        ];
        
        $subject = "Your Smile Bright appointment is confirmed — Ref {$bookingData['referenceId']}";
        
        // Send to patient
        $patientResult = $this->sendEmail(
            $patientEmail,
            $subject,
            'booking_confirmation',
            $templateData,
            'patient'
        );
        
        // Send to clinic
        $clinicSubject = "New booking — {$patientName} — {$templateData['appointment']['dateDisplay']} {$templateData['appointment']['timeDisplay']} — Ref {$bookingData['referenceId']}";
        $clinicResult = $this->sendEmail(
            $this->fromEmail,
            $clinicSubject,
            'booking_confirmation',
            $templateData,
            'clinic'
        );
        
        return [
            'patient' => $patientResult,
            'clinic' => $clinicResult
        ];
    }
    
    /**
     * Send booking update email
     */
    public function sendBookingUpdate($bookingData) {
        $patientEmail = $bookingData['patient']['email'];
        $patientName = $bookingData['patient']['firstName'] . ' ' . $bookingData['patient']['lastName'];
        
        // Prepare template data
        $templateData = [
            'referenceId' => $bookingData['referenceId'],
            'patient' => [
                'firstName' => $bookingData['patient']['firstName'],
                'lastName' => $bookingData['patient']['lastName'],
                'email' => $patientEmail
            ],
            'appointment' => [
                'dentistName' => $bookingData['appointment']['dentistName'],
                'clinicName' => $bookingData['appointment']['clinicName'],
                'serviceLabel' => $bookingData['appointment']['serviceLabel'],
                'dateDisplay' => $this->formatDate($bookingData['appointment']['dateIso']),
                'timeDisplay' => $this->formatTime($bookingData['appointment']['time24'])
            ],
            'manageUrl' => 'http://localhost/SmileBright/public/booking/manage_booking.html?ref=' . $bookingData['referenceId']
        ];
        
        $subject = "Your booking was updated — Ref {$bookingData['referenceId']}";
        
        // Send to patient
        $patientResult = $this->sendEmail(
            $patientEmail,
            $subject,
            'booking_update',
            $templateData,
            'patient'
        );
        
        // Send to clinic
        $clinicSubject = "Booking updated — {$patientName} — {$templateData['appointment']['dateDisplay']} {$templateData['appointment']['timeDisplay']} — Ref {$bookingData['referenceId']}";
        $clinicResult = $this->sendEmail(
            $this->fromEmail,
            $clinicSubject,
            'booking_update',
            $templateData,
            'clinic'
        );
        
        return [
            'patient' => $patientResult,
            'clinic' => $clinicResult
        ];
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendEmail($toEmail, $subject, $templateId, $templateData, $recipientType) {
        try {
            // Include PHPMailer
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->smtpPort;
            
            // Sender
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addReplyTo($this->replyToEmail, $this->replyToName);
            
            // Recipient
            $mail->addAddress($toEmail);
            
            // Subject
            $mail->Subject = $subject;
            
            // Load and process template
            $htmlContent = $this->loadTemplate($templateId . '.html', $templateData);
            $textContent = $this->loadTemplate($templateId . '.txt', $templateData);
            
            $mail->isHTML(true);
            $mail->Body = $htmlContent;
            $mail->AltBody = $textContent;
            
            // Send email
            $result = $mail->send();
            
            // Log success
            $this->logEmail($templateData['referenceId'], $recipientType, $toEmail, $subject, 'sent');
            
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            // Log failure
            $this->logEmail($templateData['referenceId'], $recipientType, $toEmail, $subject, 'failed', $e->getMessage());
            
            return ['success' => false, 'message' => 'Email failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Load and process email template
     */
    private function loadTemplate($templateFile, $data) {
        $templatePath = __DIR__ . '/../../templates/email/' . $templateFile;
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: $templateFile");
        }
        
        $content = file_get_contents($templatePath);
        
        // Simple template replacement
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $content = str_replace("{{$key.$subKey}}", $subValue, $content);
                }
            } else {
                $content = str_replace("{{$key}}", $value, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Log email attempt
     */
    private function logEmail($referenceId, $recipientType, $toEmail, $subject, $status, $error = null) {
        try {
            $mysqli = new mysqli('127.0.0.1', 'root', '', 'smilebrightbase', 3306);
            
            $stmt = $mysqli->prepare("
                INSERT INTO email_log (reference_id, recipient_type, to_addr, subject, status, last_error)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $stmt->bind_param('ssssss', $referenceId, $recipientType, $toEmail, $subject, $status, $error);
                $stmt->execute();
                $stmt->close();
            }
            
            $mysqli->close();
        } catch (Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }
    
    /**
     * Format date for display
     */
    private function formatDate($dateIso) {
        $date = DateTime::createFromFormat('Y-m-d', $dateIso);
        return $date ? $date->format('l, F j, Y') : $dateIso;
    }
    
    /**
     * Format time for display
     */
    private function formatTime($time24) {
        $time = DateTime::createFromFormat('H:i', $time24);
        return $time ? $time->format('g:i A') : $time24;
    }
}

// Global function for backward compatibility
function sendBookingConfirmation($email, $bookingData) {
    $emailService = new EmailService();
    return $emailService->sendBookingConfirmation($bookingData);
}

function sendBookingUpdate($email, $bookingData) {
    $emailService = new EmailService();
    return $emailService->sendBookingUpdate($bookingData);
}
?>
