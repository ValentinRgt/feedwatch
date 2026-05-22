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

RUN apt-get update && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=builder /app ./

RUN mkdir -p var \
    && chown -R www-data:www-data var
VOLUME /app/var

COPY --from=dockerconf supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY --from=dockerconf docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]