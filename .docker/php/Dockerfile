FROM dunglas/frankenphp:php8.4 AS builder

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV APP_SECRET=build
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN install-php-extensions \
    intl \
    pdo_sqlite \
    pdo_mysql \
    zip \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN --mount=type=cache,target=/root/.composer \
    composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress

COPY . ./

RUN --mount=type=cache,target=/root/.composer \
    composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd

RUN php bin/console importmap:install \
    && php bin/console tailwind:build --minify \
    && php bin/console asset-map:compile

FROM dunglas/frankenphp:php8.4 AS prod

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV APP_SECRET=

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app

COPY --from=builder /app ./

RUN mkdir -p var \
    && chown -R www-data:www-data var
VOLUME /app/var

COPY <<'SH' /usr/local/bin/docker-entrypoint
#!/bin/sh
set -e

if [ -z "${APP_SECRET:-}" ]; then
    APP_SECRET="$(php -r 'echo bin2hex(random_bytes(16));')"
    export APP_SECRET
fi

if [ "$1" = "frankenphp" ]; then
    php bin/console doctrine:migrations:migrate \
        --no-interaction \
        --all-or-nothing \
        --allow-no-migration

    php bin/console cache:clear --no-warmup
    php bin/console cache:warmup
fi

exec docker-php-entrypoint "$@"
SH
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]