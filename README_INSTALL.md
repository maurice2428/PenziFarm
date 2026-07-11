# Penzi Permission Alignment v1

This package aligns the exact permission names used by the Filament navigation,
resource access methods, custom row actions, bulk actions, UserResource tabs,
and production seeders.

## Modules fixed

- Animal Health > Product(s)
- Breeding Management > Gestation Rule(s)
- Inventory > Stock Items
- Inventory > Stock Movements
- Inventory > Stock Adjustments
- Accounting Controls > Bank & Cash Reconciliations
- Accounting Controls > Source Posting Audit
- Accounting Controls > Posting Failures
- Kenya Tax & Compliance > Dashboard
- Kenya Tax & Compliance > Tax Rules
- Kenya Tax & Compliance > Tax Register

## Key corrections

- `HealthProductResource` now consistently uses `* health products`; the legacy
  `* products` assignments are copied to the canonical permissions by the seeder.
- Duplicate UserResource permissions were removed.
- Accounting tax and reconciliation permissions appear only in the dedicated
  **Accounting Controls & Kenya Tax** tab.
- Custom actions and bulk actions now check their own exact permissions.
- Stock movements remain immutable; only view/print/export/manage permissions are
  selectable.
- Stock adjustments expose only permissions implemented by the current resource:
  view, create and export.
- Permission tabs are hidden from users who cannot `assign permissions`.
- Saving a user no longer wipes direct permissions when the editor lacks
  `assign permissions`.
- Administrator and Admin roles receive all current `web` permissions on seeding.

## Install locally

```bash
cd ~/LocalDev/Penzi

BACKUP_DIR="$HOME/Backups/penzi-permissions-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp app/Filament/Resources/UserResource.php "$BACKUP_DIR/UserResource.php"
cp database/seeders/RolePermissionSeeder.php "$BACKUP_DIR/RolePermissionSeeder.php"

unzip -o \
~/Downloads/Penzi_Permission_Alignment_v1.zip \
-d ~/LocalDev/Penzi

bash install-penzi-permission-alignment-v1.sh ~/LocalDev/Penzi
```

No migration is required.

## Inspect a specific user

```bash
php artisan penzi-permissions:doctor --user="USER_EMAIL"
```

Example:

```bash
php artisan penzi-permissions:doctor --user="mauricenzioki2428@gmail.com"
```

## Assign selected permissions

Open the user in the Filament User resource and select permissions from:

- Veterinary
- Breeding & Weights
- Inventory & Reports
- Accounting Controls & Kenya Tax

Then save the user and sign out/sign back in.

## Push to GitHub

```bash
git add \
app/Console/Commands/PenziPermissionDoctor.php \
app/Filament/Pages/Accounting/KenyaTaxCompliance.php \
app/Filament/Resources/UserResource.php \
app/Filament/Resources/HealthProductResource.php \
app/Filament/Resources/BreedingGestationRuleResource.php \
app/Filament/Resources/InventoryItemResource.php \
app/Filament/Resources/StockMovementResource.php \
app/Filament/Resources/StockAdjustmentResource.php \
app/Filament/Resources/Accounting/AccountingReconciliationResource.php \
app/Filament/Resources/Accounting/AccountingSourcePostingResource.php \
app/Filament/Resources/Accounting/AccountingPostingFailureResource.php \
app/Filament/Resources/Accounting/AccountingTaxSettingResource.php \
app/Filament/Resources/Accounting/AccountingTaxTransactionResource.php \
database/seeders/PenziModulePermissionSeeder.php \
database/seeders/RolePermissionSeeder.php \
install-penzi-permission-alignment-v1.sh

git commit -m "fix: align Filament module permissions across resources users and roles"
git push origin main
```

## Deploy on live server

```bash
cd /home/u103788518/apps/PenziFarm

php artisan down

git fetch origin
git pull --ff-only origin main

composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction

php artisan db:seed \
    --class=PenziModulePermissionSeeder \
    --force

php artisan permission:cache-reset || true
php artisan optimize:clear
php artisan filament:clear-cached-components || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan penzi-permissions:doctor
php artisan up
```

After deployment, sign out and sign back in. For non-admin users, select the
required direct permissions in UserResource.
