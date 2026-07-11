#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Penzi Sales Payment Receipt Classic v2..."

php artisan route:clear || true

ROUTE_EXISTS="no"

if php artisan route:list \
    --name=sales-payments.receipt \
    --json 2>/dev/null \
    | grep -q '"name":"sales-payments.receipt"'; then
    ROUTE_EXISTS="yes"
fi

if [[ "$ROUTE_EXISTS" != "yes" ]]; then
    REQUIRE_LINE="require __DIR__ . '/sales-payment-receipts.php';"

    if ! grep -qxF "$REQUIRE_LINE" routes/web.php; then
        printf "\n%s\n" "$REQUIRE_LINE" \
            >> routes/web.php
    fi
fi

composer dump-autoload -o

php artisan optimize:clear
php artisan route:clear
php artisan view:clear

php artisan sales-receipt:doctor

echo "Classic sales payment receipt installed."
