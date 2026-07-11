#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Accounting Reports Paper-Sized v7..."

php artisan db:seed \
    --class=AccountingPdfPermissionSeeder

if php artisan list --raw \
    | grep -q '^permission:cache-reset'; then
    php artisan permission:cache-reset
fi

composer dump-autoload -o

php artisan optimize:clear
php artisan route:clear
php artisan view:clear

php artisan accounting-pdfs:paper-check

echo "Paper-sized accounting PDF layout installed."
