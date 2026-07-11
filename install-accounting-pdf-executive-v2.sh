#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
cd "$ROOT"

echo "Installing Penzi Accounting PDF Executive Layout v2..."

php <<'PHP'
<?php

$file = getcwd() . '/routes/web.php';

if (! is_file($file)) {
    fwrite(STDERR, "routes/web.php was not found.\n");
    exit(1);
}

$requiredLine =
    "require __DIR__ . '/accounting-reports.php';";

$lines = preg_split(
    '/\R/',
    file_get_contents($file)
);

$output = [];
$found = false;

foreach ($lines as $line) {
    if (trim($line) === $requiredLine) {
        if ($found) {
            continue;
        }

        $found = true;
        $output[] = $requiredLine;
        continue;
    }

    $output[] = $line;
}

if (! $found) {
    $output[] = '';
    $output[] = $requiredLine;
}

file_put_contents(
    $file,
    rtrim(implode(PHP_EOL, $output))
    . PHP_EOL
);
PHP

php artisan db:seed \
    --class=AccountingPdfPermissionSeeder

php artisan permission:cache-reset

composer dump-autoload -o

php artisan optimize:clear
php artisan route:clear
php artisan view:clear

php artisan accounting-pdfs:doctor

echo "Accounting PDF Executive Layout v2 installed."
