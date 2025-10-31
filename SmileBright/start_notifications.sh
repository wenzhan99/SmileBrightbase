#!/bin/bash

echo "Starting SmileBright Notification System..."
echo

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "ERROR: Node.js is not installed or not in PATH"
    echo "Please install Node.js from https://nodejs.org/"
    exit 1
fi

# Check if npm dependencies are installed
if [ ! -d "node_modules" ]; then
    echo "Installing Node.js dependencies..."
    npm install
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to install dependencies"
        exit 1
    fi
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "WARNING: .env file not found"
    echo "Please copy env.example to .env and configure your settings"
    echo
    cp env.example .env
    echo "Created .env file from template"
    echo "Please edit .env with your configuration before starting the service"
    exit 1
fi

# Start the notification service
echo "Starting notification service on port 3001..."
echo "Press Ctrl+C to stop the service"
echo
npm start
