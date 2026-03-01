FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache bash git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["php-fpm"]
