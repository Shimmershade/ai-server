#!/bin/bash

echo "Starting Laravel Ping Server..."

# Проверяем установлен ли composer
if ! command -v composer &> /dev/null
then
    echo "Composer not found. Please install composer first."
    exit 1
fi

# Проверяем установлен ли PHP
if ! command -v php &> /dev/null
then
    echo "PHP not found. Please install PHP 8.1 or higher."
    exit 1
fi

# Устанавливаем зависимости если нет vendor папки
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install
fi

# Создаем .env файл если его нет
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Очищаем кэш
php artisan config:clear
php artisan cache:clear

echo "Server starting on http://localhost:8000"
echo "Test ping: http://localhost:8000/api/ping"
echo ""

# Запускаем сервер
php artisan serve --host=0.0.0.0 --port=8000