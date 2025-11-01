<?php
/**
 * Native PHP SMTP Email Function
 * Pure PHP implementation - No external dependencies
 * Works on Windows/XAMPP by connecting directly to SMTP server
 */

require_once __DIR__ . '/../config/email.php';

/**
 * Send email via SMTP using pure PHP (no dependencies)
 */
function sendSMTPEmail($to, $subject, $message, $headers = '') {
    try {
        // Get SMTP configuration
        $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $smtpUser = defined('SMTP_USER') ? SMTP_USER : 'smilebrightsg.info@gmail.com';
        $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';
        $fromEmail = defined('EMAIL_FROM') ? EMAIL_FROM : 'smilebrightsg.info@gmail.com';
        $fromName = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Smile Bright Dental';
        
        // Parse headers to get From address and BCC
        $bccEmails = [];
        if (!empty($headers)) {
            // Extract From from headers if present
            if (preg_match('/From:\s*(.+?)\s*</i', $headers, $matches)) {
                $fromEmail = trim($matches[1]);
            } elseif (preg_match('/From:\s*<(.+?)>/i', $headers, $matches)) {
                $fromEmail = trim($matches[1]);
            }
            
            // Extract BCC addresses
            if (preg_match_all('/Bcc:\s*(.+?)(?:\r\n|$)/i', $headers, $matches)) {
                foreach ($matches[1] as $bcc) {
                    $bcc = trim($bcc);
                    if (!empty($bcc) && filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
                        $bccEmails[] = $bcc;
                    }
                }
            }
        } else {
            $headers = "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        // Build email data
        $data = $headers;
        $data .= "To: {$to}\r\n";
        $data .= "Subject: {$subject}\r\n";
        $data .= "\r\n";
        $data .= $message;
        
        // Use stream_context for SSL/TLS connection
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        // Use stream_socket_client for better TLS support
        if ($smtpPort == 465) {
            // SSL connection for port 465
            $smtp = stream_socket_client("ssl://{$smtpHost}:{$smtpPort}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        } else {
            // Plain connection first, then STARTTLS for port 587
            $smtp = stream_socket_client("tcp://{$smtpHost}:{$smtpPort}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        }
        
        if (!$smtp) {
            error_log("SMTP Connection failed to {$smtpHost}:{$smtpPort} - {$errstr} ({$errno})");
            return false;
        }
        
        // Read server greeting
        $response = fgets($smtp, 515);
        if (!$response) {
            error_log("SMTP: No greeting from server");
            fclose($smtp);
            return false;
        }
        
        // Send EHLO
        fputs($smtp, "EHLO " . $smtpHost . "\r\n");
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // STARTTLS for port 587
        if ($smtpPort == 587) {
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '220') {
                error_log("STARTTLS failed: {$response}");
                fclose($smtp);
                return false;
            }
            
            // Enable crypto with multiple TLS methods
            $cryptoMethods = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethods |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethods |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            
            $cryptoResult = stream_socket_enable_crypto($smtp, true, $cryptoMethods);
            if (!$cryptoResult) {
                error_log("STARTTLS encryption failed");
                fclose($smtp);
                return false;
            }
            
            // Send EHLO again after STARTTLS
            fputs($smtp, "EHLO " . $smtpHost . "\r\n");
            $response = '';
            while ($line = fgets($smtp, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) == ' ') break;
            }
        }
        
        // Authenticate
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        
        fputs($smtp, base64_encode($smtpUser) . "\r\n");
        $response = fgets($smtp, 515);
        
        fputs($smtp, base64_encode($smtpPass) . "\r\n");
        $response = fgets($smtp, 515);
        
        if (substr($response, 0, 3) != '235') {
            error_log("SMTP Authentication failed: {$response}");
            fclose($smtp);
            return false;
        }
        
        // Send email
        fputs($smtp, "MAIL FROM: <{$fromEmail}>\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '250') {
            error_log("MAIL FROM failed: {$response}");
            fclose($smtp);
            return false;
        }
        
        // Add recipient
        fputs($smtp, "RCPT TO: <{$to}>\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '250') {
            error_log("RCPT TO failed: {$response}");
            fclose($smtp);
            return false;
        }
        
        // Add BCC recipients
        foreach ($bccEmails as $bccEmail) {
            fputs($smtp, "RCPT TO: <{$bccEmail}>\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '250') {
                error_log("BCC RCPT TO failed: {$response}");
                // Continue anyway
            }
        }
        
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '354') {
            error_log("DATA command failed: {$response}");
            fclose($smtp);
            return false;
        }
        
        fputs($smtp, $data . "\r\n.\r\n");
        $response = fgets($smtp, 515);
        
        if (substr($response, 0, 3) != '250') {
            error_log("Email sending failed: {$response}");
            fclose($smtp);
            return false;
        }
        
        // Quit
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        return true;
        
    } catch (Exception $e) {
        error_log("SMTP Email Error: " . $e->getMessage());
        return false;
    }
}

