FROM php:8.3-cli-alpine

RUN apk add --no-cache curl
RUN docker-php-ext-install pdo

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]