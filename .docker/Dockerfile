FROM php:8.0-apache

RUN a2enmod rewrite

RUN set -xe \
    && apt-get update \
    && apt-get install -y unzip libpng-dev libzip-dev libonig-dev libjpeg-dev libmcrypt-dev wget \
    && docker-php-ext-install mysqli zip

RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

WORKDIR /bitrix-module
