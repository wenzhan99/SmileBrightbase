<?php
/**
 * Simple Gmail SMTP Test
 * Tests basic Gmail authentication
 */

require_once 'vendor/autoload.php';
require_once 'email_config_secure.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "🔐 Simple Gmail SMTP Test\n";
echo str_repeat('=', 50) . "\n";

echo "📋 Configuration:\n";
echo "Host: " . SMTP_HOST . "\n";
echo "Port: " . SMTP_PORT . "\n";
echo "Secure: " . (SMTP_SECURE ? 'Yes (SSL)' : 'No (STARTTLS)') . "\n";
echo "User: " . SMTP_USER . "\n";
echo "Pass: ***" . substr(SMTP_PASS, -4) . " (length: " . strlen(SMTP_PASS) . ")\n";
echo "\n";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    
    // Development SSL override
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG: $str\n";
    };
    
    echo "🔗 Testing SMTP connection...\n";
    $mail->smtpConnect();
    echo "✅ SMTP connection successful!\n";
    
    $mail->smtpClose();
    
    echo "\n🎉 Gmail SMTP authentication successful!\n";
    echo "Your Gmail App Password is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Gmail SMTP test failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    echo "\n🔧 Troubleshooting:\n";
    if (strpos($e->getMessage(), 'Username and Password not accepted') !== false) {
        echo "• The Gmail App Password is incorrect\n";
        echo "• Make sure you're using the NEW App Password (not the old one)\n";
        echo "• Ensure 2-Step Verification is enabled on the Gmail account\n";
        echo "• Remove any spaces from the password\n";
        echo "• Try generating a new App Password\n";
    } else if (strpos($e->getMessage(), 'TLS') !== false || strpos($e->getMessage(), 'handshake') !== false) {
        echo "• SSL/TLS connection issue\n";
        echo "• Try port 587 with STARTTLS instead of 465 with SSL\n";
        echo "• Check firewall/antivirus settings\n";
    } else if (strpos($e->getMessage(), 'ENOTFOUND') !== false) {
        echo "• DNS resolution issue\n";
        echo "• Check internet connection\n";
        echo "• Try a different DNS server\n";
    }
    
    echo "\n📋 Next steps:\n";
    echo "1. Go to https://myaccount.google.com/security\n";
    echo "2. Click '2-Step Verification' → 'App passwords'\n";
    echo "3. Delete the old password and create a new one\n";
    echo "4. Update the password in email_config_secure.php\n";
    echo "5. Test again\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
?>
