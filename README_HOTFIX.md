# Sales Payment Receipt Enum Hotfix v3.1

## Error fixed

PHP 8.4 reports this warning as an exception inside the Blade view:

```text
The use statement with non-compound name 'BackedEnum' has no effect
```

The receipt Blade no longer imports the global enum interfaces. It uses fully
qualified names instead:

```php
$value instanceof \BackedEnum
$value instanceof \UnitEnum
```

## Install

```bash
cd ~/LocalDev/Penzi

unzip -o \
~/Downloads/Penzi_Sales_Payment_Receipt_Classic_v3_1_Enum_Hotfix.zip \
-d ~/LocalDev/Penzi

bash install-sales-payment-receipt-enum-hotfix-v3-1.sh \
~/LocalDev/Penzi
```

No migration is required.

## Important

The current Filament action generates the PDF directly from:

```php
Pdf::loadView('pdf.sales-payment-receipt', [...])
```

Therefore the required fix is in:

```text
resources/views/pdf/sales-payment-receipt.blade.php
```

The controller route is not involved in this specific error.

## Manual check

```bash
grep -nE 'use (BackedEnum|UnitEnum)|instanceof \\(BackedEnum|UnitEnum)' \
resources/views/pdf/sales-payment-receipt.blade.php
```

Expected output should contain only:

```text
instanceof \BackedEnum
instanceof \UnitEnum
```

Then clear compiled views:

```bash
php artisan view:clear
php artisan optimize:clear
```
