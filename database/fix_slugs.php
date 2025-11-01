<?php
/**
 * Database Migration: Remove slugs from bookings table
 * Run this file via browser: http://localhost/SmileBrightbase/database/fix_slugs.php
 */

require_once __DIR__ . '/../api/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Remove Slugs</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #1e4b86; margin-bottom: 10px; }
        h2 { color: #243042; margin-top: 30px; font-size: 1.2em; }
        .success { color: #10b981; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; background: #dbeafe; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f59e0b; background: #fef3c7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1e4b86;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #173b6a; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Migration: Remove Slugs from Bookings</h1>
        <p>This will remove <code>doctor_slug</code> and <code>location_slug</code> columns from the bookings table.</p>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            echo "<h2>Migration Results</h2>";
            
            try {
                // Check current schema
                echo "<div class='info'>📋 Checking current bookings table structure...</div>";
                $result = $mysqli->query("DESCRIBE bookings");
                $columns = [];
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row['Field'];
                }
                echo "<div class='success'>Found columns: " . implode(', ', $columns) . "</div>";
                
                $hasSlugs = in_array('doctor_slug', $columns) || in_array('location_slug', $columns);
                
                if (!$hasSlugs) {
                    echo "<div class='warning'>⚠️ No slug columns found. Database is already up to date!</div>";
                } else {
                    // Step 1: Drop unique constraint first (it might be blocking FK drops)
                    echo "<div class='info'>Step 1: Dropping old unique constraint...</div>";
                    $indexResult = $mysqli->query("SHOW INDEX FROM bookings WHERE Key_name = 'uq_doctor_slot'");
                    if ($indexResult->num_rows > 0) {
                        $mysqli->query("ALTER TABLE bookings DROP INDEX uq_doctor_slot");
                        echo "<div class='success'>✓ Dropped index: uq_doctor_slot</div>";
                    } else {
                        echo "<div class='warning'>⚠️ No uq_doctor_slot index found</div>";
                    }
                    
                    // Step 2: Get ALL foreign key names (not just the ones we expect)
                    echo "<div class='info'>Step 2: Finding foreign key constraints...</div>";
                    $fkResult = $mysqli->query("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = 'smilebrightbase' 
                        AND TABLE_NAME = 'bookings' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    
                    $fkNames = [];
                    while ($row = $fkResult->fetch_assoc()) {
                        $fkNames[] = $row['CONSTRAINT_NAME'];
                    }
                    
                    // Step 3: Drop foreign keys
                    if (!empty($fkNames)) {
                        echo "<div class='info'>Step 3: Dropping foreign key constraints...</div>";
                        foreach ($fkNames as $fkName) {
                            try {
                                $mysqli->query("ALTER TABLE bookings DROP FOREIGN KEY `$fkName`");
                                echo "<div class='success'>✓ Dropped foreign key: $fkName</div>";
                            } catch (Exception $e) {
                                echo "<div class='warning'>⚠️ Could not drop $fkName: " . $e->getMessage() . "</div>";
                            }
                        }
                    } else {
                        echo "<div class='warning'>⚠️ No foreign keys found</div>";
                    }
                    
                    // Step 4: Drop slug columns
                    echo "<div class='info'>Step 4: Dropping slug columns...</div>";
                    if (in_array('doctor_slug', $columns)) {
                        try {
                            $mysqli->query("ALTER TABLE bookings DROP COLUMN doctor_slug");
                            echo "<div class='success'>✓ Dropped column: doctor_slug</div>";
                        } catch (Exception $e) {
                            echo "<div class='error'>❌ Could not drop doctor_slug: " . $e->getMessage() . "</div>";
                        }
                    }
                    if (in_array('location_slug', $columns)) {
                        try {
                            $mysqli->query("ALTER TABLE bookings DROP COLUMN location_slug");
                            echo "<div class='success'>✓ Dropped column: location_slug</div>";
                        } catch (Exception $e) {
                            echo "<div class='error'>❌ Could not drop location_slug: " . $e->getMessage() . "</div>";
                        }
                    }
                    
                    // Step 5: Recreate unique constraint
                    echo "<div class='info'>Step 5: Adding new unique constraint...</div>";
                    $mysqli->query("ALTER TABLE bookings ADD UNIQUE KEY uq_doctor_slot (doctor_name, date, time)");
                    echo "<div class='success'>✓ Added new index: uq_doctor_slot (doctor_name, date, time)</div>";
                    
                    // Verify final structure
                    echo "<div class='info'>📋 Verifying final structure...</div>";
                    $result = $mysqli->query("DESCRIBE bookings");
                    $finalColumns = [];
                    while ($row = $result->fetch_assoc()) {
                        $finalColumns[] = $row['Field'];
                    }
                    echo "<div class='success'>✓ Final columns: " . implode(', ', $finalColumns) . "</div>";
                    
                    echo "<div class='success'><strong>✅ Migration completed successfully!</strong></div>";
                    echo "<div class='info'>You can now try your booking form again.</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            ?>
            <h2>⚠️ Warning</h2>
            <div class="warning">
                <p><strong>This migration will:</strong></p>
                <ul>
                    <li>Remove <code>doctor_slug</code> column from bookings table</li>
                    <li>Remove <code>location_slug</code> column from bookings table</li>
                    <li>Drop foreign key constraints for those columns</li>
                    <li>Update unique constraint to use <code>doctor_name</code> instead</li>
                </ul>
                <p><strong>Make sure you have a backup if you have important data!</strong></p>
            </div>
            
            <form method="POST">
                <button type="submit" name="confirm" class="btn">Run Migration</button>
            </form>
            <?php
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <a href="../public/booking/book_appointmentbase.html" class="btn">← Back to Booking Form</a>
    </div>
</body>
</html>

