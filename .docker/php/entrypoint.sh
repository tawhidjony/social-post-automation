#!/usr/bin/env sh
set -e

cd /src

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ]; then
    npm install
fi

exec composer run dev -- --inline "$@"
