#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Sales Payment Receipt enum hotfix v3.1..."

php artisan view:clear
php artisan optimize:clear
php artisan cache:clear || true

composer dump-autoload -o

php artisan view:clear
php artisan optimize:clear

echo "Receipt enum hotfix installed."
