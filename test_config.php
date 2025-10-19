<?php
/**
 * Test configuration loading
 */

echo "🔧 Testing Configuration Loading\n";
echo str_repeat('=', 50) . "\n";

// Check if .env file exists
if (file_exists('.env')) {
    echo "✅ .env file found\n";
    
    $env = parse_ini_file('.env');
    
    echo "\n📋 Environment Variables:\n";
    echo "SMTP_HOST: " . ($env['SMTP_HOST'] ?? 'NOT SET') . "\n";
    echo "SMTP_PORT: " . ($env['SMTP_PORT'] ?? 'NOT SET') . "\n";
    echo "SMTP_SECURE: " . ($env['SMTP_SECURE'] ?? 'NOT SET') . "\n";
    echo "SMTP_USER: " . ($env['SMTP_USER'] ?? 'NOT SET') . "\n";
    echo "SMTP_PASS: " . (isset($env['SMTP_PASS']) ? '***' . substr($env['SMTP_PASS'], -4) : 'NOT SET') . "\n";
    echo "EMAIL_FROM: " . ($env['EMAIL_FROM'] ?? 'NOT SET') . "\n";
    echo "EMAIL_REPLY_TO: " . ($env['EMAIL_REPLY_TO'] ?? 'NOT SET') . "\n";
    
    // Check if password is the expected length
    if (isset($env['SMTP_PASS'])) {
        $passLength = strlen($env['SMTP_PASS']);
        echo "\n🔐 Password Analysis:\n";
        echo "Length: $passLength characters\n";
        echo "Expected: 16 characters (Gmail App Password)\n";
        
        if ($passLength === 16) {
            echo "✅ Password length is correct\n";
        } else {
            echo "⚠️  Password length is not 16 characters\n";
            echo "   Gmail App Passwords should be exactly 16 characters\n";
        }
        
        // Check for spaces
        if (strpos($env['SMTP_PASS'], ' ') !== false) {
            echo "⚠️  Password contains spaces - remove them!\n";
        } else {
            echo "✅ Password has no spaces\n";
        }
    }
    
} else {
    echo "❌ .env file not found\n";
    echo "Please copy env.example to .env and configure it\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
?>
