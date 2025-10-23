@echo off
echo MariaDB Repair Script
echo ====================

echo Stopping any running MySQL processes...
taskkill /f /im mysqld.exe 2>nul
taskkill /f /im mysqld-nt.exe 2>nul

echo Waiting for processes to stop...
timeout /t 5 /nobreak >nul

echo Attempting to repair MySQL data directory...
cd /d C:\xampp\mysql\bin

echo Running mysql_upgrade...
mysql_upgrade.exe --force --user=root --password=

echo Repair complete. Please restart XAMPP Control Panel.
pause

