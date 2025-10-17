<?php
// Test database connection with different configurations
echo "<h2>Database Connection Test</h2>";

// Test different port configurations
$configs = [
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => '', 'db' => 'smilebright'],
    ['host' => '127.0.0.1', 'port' => 3307, 'user' => 'root', 'pass' => '', 'db' => 'smilebright'],
    ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => '', 'db' => 'smilebright'],
    ['host' => 'localhost', 'port' => 3307, 'user' => 'root', 'pass' => '', 'db' => 'smilebright'],
];

foreach ($configs as $i => $config) {
    echo "<h3>Test " . ($i + 1) . ": {$config['host']}:{$config['port']}</h3>";
    
    try {
        $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db'], $config['port']);
        
        if ($conn->connect_error) {
            echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
        } else {
            echo "<p style='color: green;'>✅ Connection successful!</p>";
            echo "<p>Server info: " . $conn->server_info . "</p>";
            $conn->close();
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    }
    echo "<hr>";
}

// Check if database exists
echo "<h3>Database Check</h3>";
try {
    $conn = new mysqli('127.0.0.1', 'root', '', '', 3306);
    if (!$conn->connect_error) {
        $result = $conn->query("SHOW DATABASES LIKE 'smilebright'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>✅ Database 'smilebright' exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Database 'smilebright' does not exist</p>";
            echo "<p>You may need to create it first.</p>";
        }
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database check failed: " . $e->getMessage() . "</p>";
}
?>

