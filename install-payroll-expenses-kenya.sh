#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${1:-$(pwd)}"
cd "$PROJECT_DIR"

echo "Installing Penzi Payroll Payments & Operating Expenses (Kenya)..."

if [[ ! -f artisan ]]; then
    echo "ERROR: artisan was not found in $PROJECT_DIR"
    exit 1
fi

if [[ ! -f bootstrap/providers.php ]]; then
    echo "ERROR: bootstrap/providers.php was not found."
    exit 1
fi

if ! grep -q 'AccountingEventServiceProvider::class' bootstrap/providers.php; then
    php -r '
        $path = "bootstrap/providers.php";
        $content = file_get_contents($path);
        $provider = "    App\\Providers\\AccountingEventServiceProvider::class,";
        $updated = preg_replace("/\\n\\];\\s*$/", "\\n{$provider}\\n];\\n", $content, 1);
        if ($updated === null || $updated === $content) {
            fwrite(STDERR, "Could not register AccountingEventServiceProvider automatically.\\n");
            exit(1);
        }
        file_put_contents($path, $updated);
    '
fi

php artisan migrate --force
php artisan db:seed --class=KenyaPayrollExpenseSeeder --force
php artisan permission:cache-reset || true
composer dump-autoload -o
php artisan optimize:clear
php artisan view:clear
php artisan payroll-expenses:doctor

echo "Installation completed. Review the doctor output before posting live transactions."
