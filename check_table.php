<?php
require __DIR__ . '/db.php';

echo "Current bookings table structure:\n";
echo "=================================\n";

$result = $mysqli->query('DESCRIBE bookings');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
