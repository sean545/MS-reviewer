#!/usr/bin/env bash
# Production deploy / update. Run on the server from the project folder after the first
# setup (see README). Pulls the latest main, installs production dependencies, builds the theme, migrates and caches.
set -euo pipefail
cd "$(dirname "$0")/.."

export GID="$(id -g)"
compose="docker compose -f docker-compose.yml -f docker-compose.prod.yml"
app="$compose exec -T app"

git pull --ff-only origin main
$compose build app
$compose up -d --remove-orphans

$app composer install --no-dev --optimize-autoloader --no-interaction
$app sh -c 'npm ci --no-audit --no-fund && npm run build'
$app php artisan migrate --force --no-interaction
$app php artisan storage:link --no-interaction 2>/dev/null || true
$app php artisan optimize --no-interaction
$app php artisan filament:optimize --no-interaction
$app php artisan queue:restart --no-interaction
$compose restart app

echo "==> Deployed $(git rev-parse --short HEAD)"
