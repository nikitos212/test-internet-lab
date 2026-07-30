FROM composer:2 AS composer

FROM dunglas/frankenphp:1-php8.3-alpine

RUN install-php-extensions pdo_pgsql mbstring intl zip opcache

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.* symfony.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .
RUN composer dump-autoload --classmap-authoritative \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear \
    && chmod +x docker/entrypoint.sh

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV PORT=8080

EXPOSE 8080

ENTRYPOINT ["/app/docker/entrypoint.sh"]
