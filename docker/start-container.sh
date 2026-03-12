#!/bin/sh
set -eu

cd /app

set_runtime_permissions() {
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
}

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

set_runtime_permissions

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction || true
fi

php artisan optimize:clear --no-interaction || true
php artisan statamic:stache:clear --no-interaction || true

set_runtime_permissions

unitd --control unix:/run/control.unit.sock --no-daemon &
unit_pid=$!

trap 'kill -TERM "$unit_pid" 2>/dev/null || true; wait "$unit_pid"' INT TERM

attempt=0
until [ -S /run/control.unit.sock ]; do
    attempt=$((attempt + 1))

    if [ "$attempt" -ge 50 ]; then
        echo "Unit control socket was not created in time." >&2
        exit 1
    fi

    sleep 0.1
done

curl --silent --show-error --fail \
    --unix-socket /run/control.unit.sock \
    -X PUT \
    -H "Content-Type: application/json" \
    --data-binary @/docker-entrypoint.d/unit.json \
    http://localhost/config

wait "$unit_pid"
