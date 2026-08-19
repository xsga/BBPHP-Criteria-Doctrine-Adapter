FROM php:8.4-cli

ARG DEBIAN_FRONTEND=noninteractive

# Set timezone
ENV TZ=Europe/Madrid

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libxml2-dev \
    && docker-php-ext-install dom \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://phar.phpunit.de/phploc.phar -o /usr/local/bin/phploc \
    && chmod +x /usr/local/bin/phploc

WORKDIR /app