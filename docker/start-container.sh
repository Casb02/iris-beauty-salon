#!/bin/sh
set -eu

cd /app

mkdir -p \
    bootstrap/cache \
    content \
    public/assets \
    resources/blueprints \
    storage/app/public \
    storage/forms \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    users

chown -R unit:unit \
    bootstrap/cache \
    content \
    public/assets \
    resources/blueprints \
    storage \
    users || true

chmod -R ug+rwX \
    bootstrap/cache \
    content \
    public/assets \
    resources/blueprints \
    storage \
    users || true

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction || true
fi

php artisan optimize:clear --no-interaction || true
php artisan statamic:stache:clear --no-interaction || true

exec unitd --no-daemon
