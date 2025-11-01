# PowerShell script to fix Apache DocumentRoot
# Run this script as Administrator

Write-Host "=== Apache DocumentRoot Fix Script ===" -ForegroundColor Cyan
Write-Host ""

$apacheConfig = "C:\xampp\apache\conf\httpd.conf"
$backupConfig = "C:\xampp\apache\conf\httpd.conf.backup"

# Check if Apache config exists
if (-not (Test-Path $apacheConfig)) {
    Write-Host "ERROR: Apache config file not found at: $apacheConfig" -ForegroundColor Red
    Write-Host "Please update the path in this script." -ForegroundColor Yellow
    exit 1
}

Write-Host "Current Apache DocumentRoot:" -ForegroundColor Yellow
$currentDocRoot = Select-String -Path $apacheConfig -Pattern "^DocumentRoot" | Select-Object -First 1
Write-Host $currentDocRoot.Line -ForegroundColor Gray
Write-Host ""

$newDocRoot = "C:/htdocs"
$confirm = Read-Host "Change DocumentRoot to '$newDocRoot'? (Y/N)"

if ($confirm -ne "Y" -and $confirm -ne "y") {
    Write-Host "Cancelled." -ForegroundColor Yellow
    exit 0
}

# Create backup
Write-Host "Creating backup..." -ForegroundColor Cyan
Copy-Item $apacheConfig $backupConfig -Force
Write-Host "Backup created: $backupConfig" -ForegroundColor Green

# Read config file
$content = Get-Content $apacheConfig

# Replace DocumentRoot
$content = $content | ForEach-Object {
    if ($_ -match "^DocumentRoot\s+") {
        "DocumentRoot `"$newDocRoot`""
    } else {
        $_
    }
}

# Replace Directory block
$content = $content | ForEach-Object {
    if ($_ -match '^<Directory\s+"C:/xampp/htdocs">') {
        "<Directory `"$newDocRoot`">"
    } elseif ($_ -match '^<Directory\s+"C:\\xampp\\htdocs">') {
        "<Directory `"$newDocRoot`">"
    } else {
        $_
    }
}

# Write updated config
Write-Host "Updating Apache configuration..." -ForegroundColor Cyan
$content | Set-Content $apacheConfig -Encoding UTF8

Write-Host ""
Write-Host "=== Configuration Updated ===" -ForegroundColor Green
Write-Host "New DocumentRoot: $newDocRoot" -ForegroundColor Green
Write-Host ""
Write-Host "NEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Restart Apache from XAMPP Control Panel" -ForegroundColor White
Write-Host "2. Test: http://localhost/SmileBrightbase/public/index.html" -ForegroundColor White
Write-Host ""
Write-Host "If something goes wrong, restore from: $backupConfig" -ForegroundColor Gray

