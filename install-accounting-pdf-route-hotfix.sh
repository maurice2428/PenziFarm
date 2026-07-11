#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Accounting PDF Route Hotfix v1.1..."

php artisan db:seed \
    --class=AccountingPdfPermissionSeeder

php artisan permission:cache-reset

composer dump-autoload -o

php artisan optimize:clear
php artisan route:clear
php artisan view:clear

php artisan accounting-pdfs:doctor

echo "Accounting PDF route hotfix installed."
