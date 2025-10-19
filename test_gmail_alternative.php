<?php
/**
 * Alternative Gmail SMTP Test
 * Tests both port 465 (SSL) and port 587 (STARTTLS)
 */

require_once 'vendor/autoload.php';
require_once 'email_config_secure.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "🔐 Alternative Gmail SMTP Test\n";
echo str_repeat('=', 60) . "\n";

echo "📋 Current Configuration:\n";
echo "Host: " . SMTP_HOST . "\n";
echo "Port: " . SMTP_PORT . "\n";
echo "Secure: " . (SMTP_SECURE ? 'Yes (SSL)' : 'No (STARTTLS)') . "\n";
echo "User: " . SMTP_USER . "\n";
echo "Pass: ***" . substr(SMTP_PASS, -4) . " (length: " . strlen(SMTP_PASS) . ")\n";
echo "\n";

// Test 1: Port 465 with SSL (current configuration)
echo "🧪 Test 1: Port 465 with SSL (Current Config)\n";
echo str_repeat('-', 40) . "\n";

$mail1 = new PHPMailer(true);
try {
    $mail1->isSMTP();
    $mail1->Host = SMTP_HOST;
    $mail1->SMTPAuth = true;
    $mail1->Username = SMTP_USER;
    $mail1->Password = SMTP_PASS;
    $mail1->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail1->Port = 465;
    $mail1->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    
    $mail1->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail1->Debugoutput = function($str, $level) {
        echo "DEBUG: $str\n";
    };
    
    $mail1->smtpConnect();
    echo "✅ Port 465 (SSL) connection successful!\n";
    $mail1->smtpClose();
    
} catch (Exception $e) {
    echo "❌ Port 465 (SSL) failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Port 587 with STARTTLS
echo "🧪 Test 2: Port 587 with STARTTLS\n";
echo str_repeat('-', 40) . "\n";

$mail2 = new PHPMailer(true);
try {
    $mail2->isSMTP();
    $mail2->Host = SMTP_HOST;
    $mail2->SMTPAuth = true;
    $mail2->Username = SMTP_USER;
    $mail2->Password = SMTP_PASS;
    $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail2->Port = 587;
    $mail2->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    
    $mail2->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail2->Debugoutput = function($str, $level) {
        echo "DEBUG: $str\n";
    };
    
    $mail2->smtpConnect();
    echo "✅ Port 587 (STARTTLS) connection successful!\n";
    $mail2->smtpClose();
    
} catch (Exception $e) {
    echo "❌ Port 587 (STARTTLS) failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "🔧 Gmail App Password Troubleshooting Guide\n";
echo str_repeat('=', 60) . "\n";

echo "If both tests failed with 'Username and Password not accepted':\n\n";

echo "1. 🔐 VERIFY 2-STEP VERIFICATION IS ENABLED:\n";
echo "   • Go to: https://myaccount.google.com/security\n";
echo "   • Click '2-Step Verification'\n";
echo "   • Make sure it's ON (not just 'Less secure app access')\n\n";

echo "2. 🔑 GENERATE A NEW APP PASSWORD:\n";
echo "   • In the same security page, scroll down to 'App passwords'\n";
echo "   • Click 'App passwords'\n";
echo "   • Delete any existing 'SmileBright' passwords\n";
echo "   • Click 'Create' → Enter name: 'SmileBright PHP'\n";
echo "   • Copy the 16-character password (no spaces)\n\n";

echo "3. 📧 VERIFY GMAIL ACCOUNT:\n";
echo "   • Make sure smilebrightclinic@gmail.com is a real Gmail account\n";
echo "   • Check if the account has any security restrictions\n";
echo "   • Try logging into Gmail web interface first\n\n";

echo "4. 🔄 UPDATE CONFIGURATION:\n";
echo "   • Update email_config_secure.php with the new password\n";
echo "   • Test again with: php test_gmail_alternative.php\n\n";

echo "5. 🌐 ALTERNATIVE: Use a different email service:\n";
echo "   • SendGrid (free tier: 100 emails/day)\n";
echo "   • Mailgun (free tier: 5,000 emails/month)\n";
echo "   • Amazon SES (very cheap)\n\n";

echo "Current password being used: " . SMTP_PASS . "\n";
echo "Expected format: 16 characters, no spaces, lowercase letters\n";
?>
