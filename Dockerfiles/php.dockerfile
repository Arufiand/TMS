FROM php:8.4-rc-fpm-alpine

WORKDIR /var/www/html

COPY TMS-Laravel .

RUN docker-php-ext-install pdo pdo_mysql

RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

RUN chown -R laravel:laravel /var/www/html

USER laravel

# RUN chown -R laravel:laravel .
