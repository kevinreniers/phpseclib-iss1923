FROM php:8.2

WORKDIR /app
COPY bin bin/
COPY config config/
COPY src src/
COPY public public/
COPY .env composer.json composer.lock symfony.lock ./

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apt update \
    && apt install net-tools zip unzip \
    && docker-php-ext-install sockets \
    && composer install