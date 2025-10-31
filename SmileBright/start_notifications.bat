@echo off
echo Starting SmileBright Notification System...
echo.

REM Check if Node.js is installed
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed or not in PATH
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)

REM Check if npm dependencies are installed
if not exist "node_modules" (
    echo Installing Node.js dependencies...
    npm install
    if %errorlevel% neq 0 (
        echo ERROR: Failed to install dependencies
        pause
        exit /b 1
    )
)

REM Check if .env file exists
if not exist ".env" (
    echo WARNING: .env file not found
    echo Please copy env.example to .env and configure your settings
    echo.
    copy env.example .env
    echo Created .env file from template
    echo Please edit .env with your configuration before starting the service
    pause
    exit /b 1
)

REM Start the notification service
echo Starting notification service on port 3001...
echo Press Ctrl+C to stop the service
echo.
npm start
