FROM php:8.2

WORKDIR /app
COPY . ./

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apt update \
    && apt install net-tools zip unzip \
    && docker-php-ext-install sockets \
    && composer install