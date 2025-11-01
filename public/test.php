<?php
// Quick diagnostic test for Apache/PHP
echo "Apache is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "<br>File exists check:<br>";
echo "index.html exists: " . (file_exists(__DIR__ . '/index.html') ? 'YES' : 'NO') . "<br>";
?>

