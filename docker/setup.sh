#!/usr/bin/env bash
# One-time bootstrap: builds the image, scaffolds Laravel 13 + Filament 5 around the
# files already in this repo (existing files are never overwritten), then migrates and seeds.
set -euo pipefail
cd "$(dirname "$0")/.."

export GID="$(id -g)"
docker compose build app

if [ ! -f artisan ]; then
    echo "==> Scaffolding Laravel skeleton"
    docker run --rm --network host -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
        -v "$PWD":/var/www/html reviewer-app bash -c '
            composer create-project laravel/laravel /tmp/skeleton "^13.0" --no-interaction --prefer-dist \
            && cp -rn /tmp/skeleton/. /var/www/html/'
    cp .env.example .env
fi

docker compose up -d
app="docker compose exec -T app"

if ! grep -q '"filament/filament"' composer.json; then
    echo "==> Installing Filament 5"
    $app composer require "filament/filament:^5.0" -W --no-interaction
    $app composer require --dev "laravel/boost" --no-interaction
    # Keep Filament's published assets in sync after every composer update
    $app php -r '$f="composer.json";$j=json_decode(file_get_contents($f),true);$s=&$j["scripts"]["post-autoload-dump"];if(!in_array("@php artisan filament:upgrade",$s)){$s[]="@php artisan filament:upgrade";}file_put_contents($f,json_encode($j,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);'
fi

grep -q '^APP_KEY=base64' .env || $app php artisan key:generate --no-interaction
$app php artisan filament:upgrade --no-interaction
$app php artisan storage:link --no-interaction || true
$app php artisan migrate --seed --force --no-interaction
$app sh -c 'npm ci --no-audit --no-fund && npm run build'
docker compose restart queue

echo "==> Done: http://localhost:${APP_PORT:-8081}/admin  (admin@reviewer.test / password)"
