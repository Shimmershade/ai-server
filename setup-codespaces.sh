#!/bin/bash

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -z "$YANDEX_API_KEY" ]; then
    sed -i "s/YANDEX_API_KEY=.*/YANDEX_API_KEY=$YANDEX_API_KEY/" .env
fi

if [ ! -z "$YANDEX_FOLDER_ID" ]; then
    sed -i "s/YANDEX_FOLDER_ID=.*/YANDEX_FOLDER_ID=$YANDEX_FOLDER_ID/" .env
fi

composer install
php artisan key:generate
php artisan migrate --force