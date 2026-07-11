#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${1:-$(pwd)}"
cd "$PROJECT_DIR"

if [[ ! -f artisan || ! -f bootstrap/providers.php ]]; then
    echo "Run this script from the Laravel project root." >&2
    exit 1
fi

STAMP="$(date +%Y%m%d_%H%M%S)"
cp bootstrap/providers.php "bootstrap/providers.php.accounting-v2-${STAMP}.bak"

python3 - <<'PYTHON'
from pathlib import Path
path = Path('bootstrap/providers.php')
text = path.read_text()
provider = 'App\\Providers\\AccountingEventServiceProvider::class,'
if provider not in text:
    marker = '];'
    pos = text.rfind(marker)
    if pos == -1:
        raise SystemExit('Could not locate the providers array closing marker.')
    text = text[:pos] + f'    {provider}\n' + text[pos:]
    path.write_text(text)
    print('Accounting provider registered.')
else:
    print('Accounting provider is already registered.')
PYTHON

php artisan migrate
php artisan db:seed --class=KenyaAccountingV2Seeder
php artisan permission:cache-reset
composer dump-autoload -o
php artisan optimize:clear
php artisan view:clear

echo
echo "Accounting Core V2 installed. Run:"
echo "  php artisan accounting:doctor"
echo "  php artisan accounting:backfill-v2"
echo "Review the dry run before using --commit."
