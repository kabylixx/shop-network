# syntax=docker/dockerfile:1

# Single FrankenPHP image (Caddy HTTP server + PHP 8.5 bundled).
FROM dunglas/frankenphp:php8.5

# Required PHP extensions: pdo_mysql (Doctrine/MySQL), intl (Serializer,
# case/accent-insensitive normalization), opcache (performance). The
# install-php-extensions helper ships with the FrankenPHP image.
RUN install-php-extensions \
        pdo_mysql \
        intl \
        opcache

# Composer (copied from the official image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# FrankenPHP serves public/index.php; SERVER_NAME=:80 forces plain HTTP
# (no auto TLS) for frictionless local use without certificates.
ENV SERVER_NAME=:80
