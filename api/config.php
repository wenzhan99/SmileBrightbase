<?php
/**
 * SmileBright Database Configuration
 * Default: root/no password (XAMPP) - modify for production
 */

$DB_HOST = '127.0.0.1';

$DB_USER = 'root';

$DB_PASS = '';

$DB_NAME = 'smilebrightbase';   // << single database

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

$mysqli->set_charset('utf8mb4');

?>
