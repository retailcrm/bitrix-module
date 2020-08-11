FROM php:7.1-apache

RUN a2enmod rewrite

RUN set -xe \
    && apt-get update \
    && apt-get install -y --no-install-recommends git wget unzip imagemagick libpng-dev libjpeg-dev \
    libfreetype6-dev default-mysql-client libmcrypt-dev libicu-dev libxml2 libxml2-dev libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr --with-freetype-dir=/usr \
    && docker-php-ext-install opcache soap gd mbstring mysqli zip intl \
    && pecl install mcrypt-1.0.1 imagick-3.4.4 \
    && docker-php-ext-enable mcrypt imagick \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer
RUN wget -O /usr/bin/phpunit https://phar.phpunit.de/phpunit-7.phar && chmod +x /usr/bin/phpunit

WORKDIR /bitrix-module
