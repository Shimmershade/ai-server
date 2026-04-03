#!/bin/bash

echo "Starting Laravel Server..."

if ! command -v composer &> /dev/null
then
    echo "Composer not found. Please install composer first."
    exit 1
fi

if ! command -v php &> /dev/null
then
    echo "PHP not found. Please install PHP 8.1 or higher."
    exit 1
fi

if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install
fi

if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

php artisan config:clear
php artisan cache:clear

echo "Server starting on http://localhost:8000"
echo "Test ping: http://localhost:8000/api/ping"
echo ""

php artisan serve --host=0.0.0.0 --port=8000