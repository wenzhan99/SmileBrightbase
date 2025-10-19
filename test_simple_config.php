<?php
/**
 * Simple configuration test
 */

echo "🔧 Testing Simple Configuration\n";
echo str_repeat('=', 50) . "\n";

// Check if .env file exists
if (file_exists('.env')) {
    echo "✅ .env file found\n";
    
    // Read the file manually
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $config = [];
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $config[trim($key)] = trim($value);
        }
    }
    
    echo "\n📋 Configuration Values:\n";
    echo "SMTP_HOST: " . ($config['SMTP_HOST'] ?? 'NOT SET') . "\n";
    echo "SMTP_PORT: " . ($config['SMTP_PORT'] ?? 'NOT SET') . "\n";
    echo "SMTP_SECURE: " . ($config['SMTP_SECURE'] ?? 'NOT SET') . "\n";
    echo "SMTP_USER: " . ($config['SMTP_USER'] ?? 'NOT SET') . "\n";
    echo "SMTP_PASS: " . (isset($config['SMTP_PASS']) ? '***' . substr($config['SMTP_PASS'], -4) : 'NOT SET') . "\n";
    echo "EMAIL_FROM: " . ($config['EMAIL_FROM'] ?? 'NOT SET') . "\n";
    echo "EMAIL_REPLY_TO: " . ($config['EMAIL_REPLY_TO'] ?? 'NOT SET') . "\n";
    
    // Check if password is the expected length
    if (isset($config['SMTP_PASS'])) {
        $passLength = strlen($config['SMTP_PASS']);
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
        if (strpos($config['SMTP_PASS'], ' ') !== false) {
            echo "⚠️  Password contains spaces - remove them!\n";
        } else {
            echo "✅ Password has no spaces\n";
        }
        
        // Show first and last few characters for verification
        echo "First 4 chars: " . substr($config['SMTP_PASS'], 0, 4) . "\n";
        echo "Last 4 chars: " . substr($config['SMTP_PASS'], -4) . "\n";
    }
    
} else {
    echo "❌ .env file not found\n";
    echo "Please copy env.example to .env and configure it\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
?>
