# Penzi Farm ERP - Progeny, Heredity and Maternal Performance Module v2

This package updates the complete breeding intelligence module and corrects the Breeding Outcomes `Pending` date crash.

## Corrections included in v2

1. **Breeding Outcomes Carbon error corrected**
   - The `evaluation_completed_at` column no longer passes the word `Pending` into Carbon.
   - Null or malformed legacy values display as `Pending` safely.
   - The mating date column also uses guarded date formatting.

2. **Progeny Explorer redesigned**
   - Classic command-centre layout.
   - One controlled chevron per select field.
   - Native browser arrows and duplicated select background icons are disabled.
   - Responsive desktop, tablet and phone layouts.
   - Dynamic farm name, farm tagline and global theme colours.
   - Improved animal identity, parent summary, performance cards, decision cards, tree legend and rankings.

3. **Progeny PDF redesigned**
   - Fixed dynamic header and footer on every page.
   - Dynamic logo, farm name, tagline, phone, email and address.
   - Dynamic global primary, secondary, accent, success and danger colours.
   - Watermark, QR verification, authorised signature and official stamp support.
   - Generated-by name and role.
   - Page `X of Y` numbering.
   - Printable ancestry or descendant tree for one to five generations.

4. **All module files included**
   - Updated Animal Resource.
   - Progeny Analytics Service.
   - Progeny Explorer Filament page and Blade view.
   - Breeding Outcome Resource and page classes.
   - Progeny PDF controller, report and recursive tree partial.
   - Breeding performance dashboard component.
   - Models, migrations, permissions and routes.

---

## Important package paths

The following requested files are included:

```text
app/Filament/Resources/AnimalResource.php
app/Services/ProgenyAnalyticsService.php
app/Filament/Pages/ProgenyExplorer.php
app/Filament/Resources/BreedingOutcomeResource.php
resources/views/pdf/progeny-report.blade.php
resources/views/components/breeding-performance-dashboard.blade.php
```

Additional required supporting files are also included.

---

## A. Upgrade an existing v1 installation locally

Go to the project:

```bash
cd ~/LocalDev/Penzi
```

Create a dated backup:

```bash
BACKUP_DIR="$HOME/Backups/penzi-progeny-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp app/Filament/Resources/AnimalResource.php "$BACKUP_DIR/AnimalResource.php"
cp app/Filament/Pages/ProgenyExplorer.php "$BACKUP_DIR/ProgenyExplorer.php" 2>/dev/null || true
cp app/Filament/Resources/BreedingOutcomeResource.php "$BACKUP_DIR/BreedingOutcomeResource.php" 2>/dev/null || true
cp app/Models/BreedingRecord.php "$BACKUP_DIR/BreedingRecord.php" 2>/dev/null || true
cp resources/views/filament/pages/progeny-explorer.blade.php "$BACKUP_DIR/progeny-explorer.blade.php" 2>/dev/null || true
cp resources/views/pdf/progeny-report.blade.php "$BACKUP_DIR/progeny-report.blade.php" 2>/dev/null || true
```

Extract the v2 package over the project root:

```bash
unzip -o \
~/Downloads/Penzi_Progeny_Heredity_Maternal_Performance_Module_v2.zip \
-d ~/LocalDev/Penzi
```

Ensure the route file is loaded once:

```bash
grep -qxF "require __DIR__ . '/progeny.php';" routes/web.php \
|| printf "\nrequire __DIR__ . '/progeny.php';\n" >> routes/web.php
```

Run database updates and permissions:

```bash
php artisan migrate
php artisan db:seed --class=ProgenyPermissionSeeder
php artisan permission:cache-reset
```

Rebuild application caches:

```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## B. Fresh local installation

The same commands apply:

```bash
cd ~/LocalDev/Penzi

unzip -o \
~/Downloads/Penzi_Progeny_Heredity_Maternal_Performance_Module_v2.zip \
-d ~/LocalDev/Penzi

grep -qxF "require __DIR__ . '/progeny.php';" routes/web.php \
|| printf "\nrequire __DIR__ . '/progeny.php';\n" >> routes/web.php

php artisan migrate
php artisan db:seed --class=ProgenyPermissionSeeder
php artisan permission:cache-reset
composer dump-autoload -o
php artisan optimize:clear
```

---

## C. Validate locally

Run syntax checks:

```bash
php -l app/Filament/Resources/BreedingOutcomeResource.php
php -l app/Filament/Pages/ProgenyExplorer.php
php -l app/Services/ProgenyAnalyticsService.php
php -l app/Http/Controllers/ProgenyReportController.php
php -l app/Models/BreedingRecord.php
```

Confirm routes:

```bash
php artisan route:list | grep -E "progeny|breeding-outcomes"
```

Open:

```text
http://127.0.0.1:8002/admin/breeding-outcomes
http://127.0.0.1:8002/admin/progeny-explorer
```

Test the PDF using the **Print Heredity PDF** action after selecting an animal and generation depth.

---

## D. Global settings used by the PDF and screen

The module reads the existing global settings helper:

```text
farm.name
farm.tagline
farm.phone
farm.email
farm.county
farm.address

theme.primary
theme.secondary
theme.accent
theme.success
theme.danger

branding.logo_light
branding.signature
branding.stamp
```

It also supports these existing payment/branding image fields when available:

```text
authorized_signature_image
signature_path
authorized_signature_path
payment_stamp_image
stamp_path
official_stamp_path
```

No separate progeny branding configuration is required.

---

## E. Dashboard integration

The package includes:

```text
app/View/Components/BreedingPerformanceDashboard.php
resources/views/components/breeding-performance-dashboard.blade.php
```

Place this inside the livestock or breeding area of the dashboard:

```blade
@can('view progeny analytics')
    <x-breeding-performance-dashboard />
@endcan
```

To protect your existing dashboard customisations, the v2 ZIP does **not** overwrite `resources/views/filament/pages/dashboard.blade.php` during extraction. An optional dashboard example is included under:

```text
optional/dashboard-with-breeding-component-example.blade.php
```

Use the small integration snippet instead:

```text
integration/dashboard-snippet.blade.php
```

---

## F. Commit locally

```bash
cd ~/LocalDev/Penzi

git status --short

git add \
app/Filament/Pages/ProgenyExplorer.php \
app/Filament/Resources/AnimalResource.php \
app/Filament/Resources/BreedingOutcomeResource.php \
app/Filament/Resources/BreedingOutcomeResource \
app/Http/Controllers/ProgenyReportController.php \
app/Models/AnimalBreedingReview.php \
app/Models/BreedingRecord.php \
app/Services/ProgenyAnalyticsService.php \
app/View/Components/BreedingPerformanceDashboard.php \
database/migrations/2026_07_09_210000_upgrade_breeding_records_for_progeny_analytics.php \
database/migrations/2026_07_09_210100_create_animal_breeding_reviews_table.php \
database/seeders/ProgenyPermissionSeeder.php \
resources/views/components/breeding-performance-dashboard.blade.php \
resources/views/filament/pages/progeny-explorer.blade.php \
resources/views/filament/pages/partials/progeny-tree-node.blade.php \
resources/views/pdf/progeny-report.blade.php \
resources/views/pdf/partials/progeny-tree-node.blade.php \
routes/progeny.php \
routes/web.php
```

After inserting the dashboard component into your existing customised dashboard, add that file deliberately:

```bash
git add resources/views/filament/pages/dashboard.blade.php
```

Commit and push:

```bash
git commit -m "Fix breeding outcomes and improve progeny analytics reports"
git push origin main
```

---

## G. Deploy to the live server

```bash
cd ~/apps/PenziFarm

git pull --ff-only origin main

php artisan migrate --force
php artisan db:seed --class=ProgenyPermissionSeeder --force
php artisan permission:cache-reset
composer dump-autoload -o
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Confirm deployment:

```bash
grep -n "formatDateValue" app/Filament/Resources/BreedingOutcomeResource.php
grep -n "progeny-select-chevron" resources/views/filament/pages/progeny-explorer.blade.php
grep -n "progenyPdfImageBase64" resources/views/pdf/progeny-report.blade.php
```

---

## H. Data requirements

The family tree is built from:

```text
animals.sire_id
animals.dam_id
```

Check lineage coverage:

```bash
php artisan tinker --execute="
dump([
    'total_animals' => \\App\\Models\\Animal::count(),
    'with_sire' => \\App\\Models\\Animal::whereNotNull('sire_id')->count(),
    'with_dam' => \\App\\Models\\Animal::whereNotNull('dam_id')->count(),
    'with_both_parents' => \\App\\Models\\Animal::whereNotNull('sire_id')
        ->whereNotNull('dam_id')
        ->count(),
]);
"
```

Maternal scores depend on completed records under **Breeding Outcomes**.

---

## I. Important management rule

The analytics may recommend retain, monitor, sell, or cull. The system does not automatically sell or cull an animal. An authorised user must record and approve the final breeding decision so that the process remains auditable.
