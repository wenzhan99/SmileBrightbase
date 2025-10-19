<?php
/**
 * Fresh configuration test
 */

// Read the file directly to see what's actually in it
$configContent = file_get_contents('email_config_secure.php');
echo "🔧 Raw Configuration File Content (lines 10-15):\n";
echo str_repeat('=', 50) . "\n";

$lines = explode("\n", $configContent);
for ($i = 9; $i <= 14; $i++) {
    if (isset($lines[$i])) {
        echo ($i + 1) . ": " . $lines[$i] . "\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";

// Now try to include and check
echo "📋 Including the file and checking constants:\n";
include 'email_config_secure.php';

echo "SMTP_PASS constant: " . (defined('SMTP_PASS') ? SMTP_PASS : 'NOT DEFINED') . "\n";
echo "Length: " . (defined('SMTP_PASS') ? strlen(SMTP_PASS) : 'N/A') . "\n";
echo "Expected: vogihahdpfalpbgm\n";
echo "Match: " . (defined('SMTP_PASS') && SMTP_PASS === 'vogihahdpfalpbgm' ? 'YES' : 'NO') . "\n";
?>
