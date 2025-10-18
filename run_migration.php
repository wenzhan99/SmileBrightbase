<?php
require __DIR__ . '/db.php';

header('Content-Type: text/plain');

echo "SmileBright Database Migration\n";
echo "==============================\n\n";

try {
    // Check if columns already exist
    $result = $mysqli->query("DESCRIBE bookings");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo "Current columns: " . implode(', ', $columns) . "\n\n";
    
    // Add missing columns
    $migrations = [
        "ALTER TABLE bookings ADD COLUMN reschedule_token CHAR(64) NULL AFTER created_at",
        "ALTER TABLE bookings ADD COLUMN token_expires_at DATETIME NULL AFTER reschedule_token", 
        "ALTER TABLE bookings ADD COLUMN terms_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER token_expires_at"
    ];
    
    foreach ($migrations as $sql) {
        $column_name = '';
        if (strpos($sql, 'reschedule_token') !== false) $column_name = 'reschedule_token';
        elseif (strpos($sql, 'token_expires_at') !== false) $column_name = 'token_expires_at';
        elseif (strpos($sql, 'terms_accepted') !== false) $column_name = 'terms_accepted';
        
        if (!in_array($column_name, $columns)) {
            echo "Adding column: $column_name\n";
            $mysqli->query($sql);
            echo "✓ Success\n";
        } else {
            echo "Column $column_name already exists, skipping\n";
        }
    }
    
    // Create unique index if it doesn't exist
    $index_result = $mysqli->query("SHOW INDEX FROM bookings WHERE Key_name = 'ux_bookings_reschedule_token'");
    if ($index_result->num_rows == 0) {
        echo "\nCreating unique index on reschedule_token\n";
        $mysqli->query("CREATE UNIQUE INDEX ux_bookings_reschedule_token ON bookings(reschedule_token)");
        echo "✓ Success\n";
    } else {
        echo "\nUnique index on reschedule_token already exists\n";
    }
    
    // Backfill existing records
    echo "\nBackfilling existing records...\n";
    
    // Add tokens to existing records
    $mysqli->query("UPDATE bookings SET reschedule_token = SHA2(CONCAT(id,'-',RAND(),'-',NOW()), 256), token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE reschedule_token IS NULL");
    echo "✓ Added tokens to existing records\n";
    
    // Generate reference IDs for existing records
    $mysqli->query("UPDATE bookings SET reference_id = CONCAT('SB', DATE_FORMAT(created_at,'%y%m'), LPAD(id,4,'0')) WHERE (reference_id IS NULL OR reference_id = '')");
    echo "✓ Generated reference IDs for existing records\n";
    
    echo "\nMigration completed successfully!\n";
    echo "\nFinal table structure:\n";
    echo "======================\n";
    
    $result = $mysqli->query("DESCRIBE bookings");
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL'
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    http_response_code(500);
}
?>
