FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

EXPOSE 80
