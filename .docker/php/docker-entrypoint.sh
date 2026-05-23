#!/bin/sh
set -e

if [ -z "${APP_SECRET:-}" ]; then
    APP_SECRET="$(php -r 'echo bin2hex(random_bytes(16));')"
    export APP_SECRET
fi

if [ "$1" = "supervisord" ] || [ "$1" = "frankenphp" ]; then
    su -s /bin/sh www-data -c '
        php bin/console doctrine:migrations:migrate \
            --no-interaction \
            --all-or-nothing \
            --allow-no-migration

        php bin/console cache:clear --no-warmup
        php bin/console cache:warmup
    '
fi

exec "$@"
