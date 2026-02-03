FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y libpq-dev pkg-config \
 && docker-php-ext-install pdo_pgsql

COPY . /var/www/html/

EXPOSE 80
