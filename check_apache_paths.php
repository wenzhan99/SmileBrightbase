<?php
/**
 * Apache Path Diagnostic Script
 * Place this file in your DocumentRoot and access it via browser
 * Example: http://localhost/check_apache_paths.php
 */

echo "<h1>Apache Configuration Diagnostic</h1>";
echo "<h2>Server Information</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not available') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not available') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not available') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not available') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Not available') . "\n";
echo "</pre>";

echo "<h2>File System Checks</h2>";
echo "<pre>";

$paths_to_check = [
    'C:/htdocs',
    'C:/htdocs/SmileBrightbase',
    'C:/htdocs/SmileBrightbase/public',
    'C:/htdocs/SmileBrightbase/public/index.html',
    'C:/xampp/htdocs',
    'C:/xampp/htdocs/SmileBrightbase',
];

foreach ($paths_to_check as $path) {
    $exists = file_exists($path);
    $is_dir = is_dir($path);
    $is_file = is_file($path);
    $readable = is_readable($path);
    
    echo "Path: $path\n";
    echo "  Exists: " . ($exists ? 'YES' : 'NO') . "\n";
    echo "  Is Directory: " . ($is_dir ? 'YES' : 'NO') . "\n";
    echo "  Is File: " . ($is_file ? 'YES' : 'NO') . "\n";
    echo "  Readable: " . ($readable ? 'YES' : 'NO') . "\n";
    echo "\n";
}

echo "</pre>";

echo "<h2>Recommended Configuration</h2>";
$doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
echo "<pre>";
echo "Current DocumentRoot: $doc_root\n\n";

if (file_exists('C:/htdocs/SmileBrightbase/public/index.html')) {
    echo "✓ SmileBrightbase found at: C:/htdocs/SmileBrightbase\n";
    echo "\n";
    echo "RECOMMENDATION:\n";
    echo "Update Apache httpd.conf:\n";
    echo "  DocumentRoot \"C:/htdocs\"\n";
    echo "  <Directory \"C:/htdocs\">\n";
    echo "      AllowOverride All\n";
    echo "      Require all granted\n";
    echo "  </Directory>\n";
} elseif (file_exists('C:/xampp/htdocs/SmileBrightbase/public/index.html')) {
    echo "✓ SmileBrightbase found at: C:/xampp/htdocs/SmileBrightbase\n";
    echo "\n";
    echo "RECOMMENDATION:\n";
    echo "Move project from C:/htdocs to C:/xampp/htdocs\n";
    echo "OR update DocumentRoot to C:/htdocs\n";
} else {
    echo "✗ Cannot find SmileBrightbase in common locations\n";
    echo "\n";
    echo "Please verify:\n";
    echo "1. Project is at: C:/htdocs/SmileBrightbase\n";
    echo "2. File exists: C:/htdocs/SmileBrightbase/public/index.html\n";
}
echo "</pre>";

?>

