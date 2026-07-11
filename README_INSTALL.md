# Penzi Sales Payment Receipt v3.3 - Flush-Left Equal Tables

This patch makes every grouped table begin at the same left edge and use the
full available width.

## Exact alignment

```text
Receipt title panel                 100%
Receipt Details / Customer         50% / 50%
Payment Confirmation               25% / 25% / 25% / 25%
Animals / Items table              100%
Payment Allocation / Position      50% / 50%
Receipt Confirmation               100%
Prepared / Signature / Stamp / QR  25% / 25% / 25% / 25%
```

The grouped tables now use:

```css
width: 100%;
margin-left: 0;
margin-right: 0;
border-spacing: 0;
border-collapse: collapse;
```

This removes the hidden outer spacing that previously pushed the two-card and
four-card sections inward.

## Install

```bash
cd ~/LocalDev/Penzi

BACKUP_DIR="$HOME/Backups/penzi-sales-receipt-v33-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp resources/views/pdf/sales-payment-receipt.blade.php \
   "$BACKUP_DIR/sales-payment-receipt.blade.php"

unzip -o \
~/Downloads/Penzi_Sales_Payment_Receipt_Classic_v3_3_Flush_Equal_Tables.zip \
-d ~/LocalDev/Penzi

bash install-sales-payment-receipt-flush-equal-v3-3.sh \
~/LocalDev/Penzi
```

No migration is required.

## Validate

```bash
php artisan sales-receipt:doctor
```

Expected additional check:

```text
Flush-left grouped tables    YES
Overall status               HEALTHY
```
