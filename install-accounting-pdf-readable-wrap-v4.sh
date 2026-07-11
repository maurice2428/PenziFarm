#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Penzi Accounting PDF Readable Wrap v4..."

php artisan db:seed \
    --class=AccountingPdfPermissionSeeder

php artisan permission:cache-reset

composer dump-autoload -o

php artisan optimize:clear
php artisan route:clear
php artisan view:clear

php artisan accounting-pdfs:doctor

echo "Accounting PDF Readable Wrap v4 installed."
