#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

python3 - <<'PY'
from pathlib import Path

path = Path('routes/web.php')
line = "require __DIR__ . '/accounting-reports.php';"

if not path.exists():
    raise SystemExit('routes/web.php was not found.')

lines = path.read_text().splitlines()
cleaned = []
seen = False

for current in lines:
    if current.strip() == line:
        if seen:
            continue
        seen = True
    cleaned.append(current)

if not seen:
    cleaned.append('')
    cleaned.append(line)

path.write_text('\n'.join(cleaned).rstrip() + '\n')
PY

php artisan db:seed --class=AccountingPdfPermissionSeeder
php artisan permission:cache-reset
composer dump-autoload -o
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan accounting-pdfs:doctor
