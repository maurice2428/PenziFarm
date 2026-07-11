#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Penzi Accounting Reports Full Invoice Style v6..."

ROUTES_WEB="routes/web.php"
REQUIRE_LINE="require __DIR__ . '/accounting-reports.php';"

if [[ ! -f "$ROUTES_WEB" ]]; then
    echo "ERROR: $ROUTES_WEB was not found."
    exit 1
fi

# Remove duplicate exact includes, then add one clean include.
sed -i "\#${REQUIRE_LINE}#d" "$ROUTES_WEB"

printf "\n%s\n" "$REQUIRE_LINE" >> "$ROUTES_WEB"

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

php artisan accounting-reports:doctor

echo "Accounting report suite installed successfully."
