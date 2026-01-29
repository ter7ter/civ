FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y mc libpq-dev libonig-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli pcntl gd zip

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN apt-get install -y apache2-bin
RUN a2enmod alias
RUN a2enmod rewrite
RUN service apache2 restart