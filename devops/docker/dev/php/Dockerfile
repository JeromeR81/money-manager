FROM php:8.5-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip \
    libpq-dev libicu-dev libzip-dev libpng-dev \
    librabbitmq-dev libssl-dev \
    && docker-php-ext-install pdo_pgsql pgsql intl opcache zip gd bcmath sockets \
    && pecl install amqp xdebug \
    && docker-php-ext-enable amqp xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/html
