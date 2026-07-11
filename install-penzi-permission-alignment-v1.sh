#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Penzi permission alignment v1..."

composer dump-autoload -o

php artisan db:seed \
    --class=PenziModulePermissionSeeder \
    --force

php artisan permission:cache-reset || true
php artisan optimize:clear
php artisan filament:clear-cached-components || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan penzi-permissions:doctor

echo "Penzi permission alignment installed."
