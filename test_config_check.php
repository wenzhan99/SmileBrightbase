<?php
/**
 * Quick configuration check
 */

require_once 'email_config_secure.php';

echo "🔧 Configuration Check\n";
echo str_repeat('=', 40) . "\n";

echo "SMTP_HOST: " . SMTP_HOST . "\n";
echo "SMTP_PORT: " . SMTP_PORT . "\n";
echo "SMTP_SECURE: " . (SMTP_SECURE ? 'true' : 'false') . "\n";
echo "SMTP_USER: " . SMTP_USER . "\n";
echo "SMTP_PASS: " . substr(SMTP_PASS, 0, 4) . "..." . substr(SMTP_PASS, -4) . " (length: " . strlen(SMTP_PASS) . ")\n";
echo "EMAIL_FROM: " . EMAIL_FROM . "\n";

echo "\n" . str_repeat('=', 40) . "\n";

// Check if the password looks correct
if (strlen(SMTP_PASS) === 16) {
    echo "✅ Password length is correct (16 characters)\n";
} else {
    echo "❌ Password length is incorrect: " . strlen(SMTP_PASS) . " characters\n";
}

if (strpos(SMTP_PASS, ' ') === false) {
    echo "✅ Password has no spaces\n";
} else {
    echo "❌ Password contains spaces\n";
}

echo "\nExpected password: vogihahdpfalpbgm\n";
echo "Actual password: " . SMTP_PASS . "\n";
echo "Match: " . (SMTP_PASS === 'vogihahdpfalpbgm' ? 'YES' : 'NO') . "\n";
?>
