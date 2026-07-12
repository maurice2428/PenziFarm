@php
    use Illuminate\Support\Facades\Storage;

    $employee = $getRecord();

    $organizationName = function_exists('setting')
        ? (
            setting('company.name')
            ?: setting('farm.name')
            ?: setting('organization.name')
            ?: config('app.name', 'Organization')
        )
        : config('app.name', 'Organization');

    $primary = function_exists('setting')
        ? (
            setting('branding.primary_color')
            ?: setting('theme.primary_color')
            ?: '#166534'
        )
        : '#166534';

    $secondary = function_exists('setting')
        ? (
            setting('branding.secondary_color')
            ?: setting('theme.secondary_color')
            ?: '#0f766e'
        )
        : '#0f766e';

    $logoPath = function_exists('setting')
        ? (
            setting('branding.logo')
            ?: setting('company.logo')
            ?: setting('farm.logo')
            ?: null
        )
        : null;

    /**
     * Resolve an uploaded image directly from the public disk.
     *
     * The data URI fallback makes the card image render even when an existing
     * public/storage symbolic link is stale or points to another project.
     */
    $resolveUploadedImage = static function (mixed $storedPath): ?string {
        if (blank($storedPath)) {
            return null;
        }

        if (is_array($storedPath)) {
            $storedPath = collect($storedPath)
                ->flatten()
                ->first(fn (mixed $value): bool => is_string($value) && trim($value) !== '');
        }

        if (! is_string($storedPath) || trim($storedPath) === '') {
            return null;
        }

        $storedPath = trim($storedPath);

        if (
            str_starts_with($storedPath, 'http://')
            || str_starts_with($storedPath, 'https://')
            || str_starts_with($storedPath, 'data:')
        ) {
            return $storedPath;
        }

        $diskPath = ltrim($storedPath, '/');

        foreach (['storage/', 'public/'] as $prefix) {
            if (str_starts_with($diskPath, $prefix)) {
                $diskPath = substr($diskPath, strlen($prefix));
            }
        }

        try {
            if (Storage::disk('public')->exists($diskPath)) {
                $mimeType = Storage::disk('public')->mimeType($diskPath) ?: 'image/jpeg';
                $contents = Storage::disk('public')->get($diskPath);

                return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
            }
        } catch (Throwable) {
            // Continue to the public-path fallback below.
        }

        $publicPath = public_path(ltrim($storedPath, '/'));

        if (is_file($publicPath)) {
            return url('/' . ltrim($storedPath, '/'));
        }

        return null;
    };

    $logoUrl = $resolveUploadedImage($logoPath);
    $avatarUrl = $resolveUploadedImage($employee->avatar_path);

    $initials = collect([
        $employee->first_name,
        $employee->middle_name,
        $employee->last_name,
    ])
        ->filter()
        ->map(fn ($name) => mb_strtoupper(mb_substr((string) $name, 0, 1)))
        ->take(3)
        ->implode('');

    $status = $employee->status ?: 'inactive';
    $statusLabel = str($status)->headline();
    $issueDate = $employee->created_at?->format('d.m.Y') ?: now()->format('d.m.Y');
    $validUntil = $employee->contract_end_date?->format('d.m.Y') ?: 'WHILE EMPLOYED';
@endphp

<div
    class="kenyan-style-staff-card"
    style="--staff-primary: {{ $primary }}; --staff-secondary: {{ $secondary }};"
>
    <div class="kenyan-style-staff-card__security-lines"></div>

    <header class="kenyan-style-staff-card__header">
        <div class="kenyan-style-staff-card__heading-block">
            <div class="kenyan-style-staff-card__organization">
                {{ mb_strtoupper($organizationName) }}
            </div>
            <div class="kenyan-style-staff-card__organization-subtitle">
                STAFF IDENTIFICATION
            </div>
        </div>

        <div class="kenyan-style-staff-card__emblem" aria-hidden="true">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $organizationName }} logo">
            @else
                <span>{{ $initials ?: 'HR' }}</span>
            @endif
        </div>

        <div class="kenyan-style-staff-card__document-title">
            <strong>INTERNAL STAFF CARD</strong>
            <span>Human Resource Record</span>
        </div>
    </header>

    <main class="kenyan-style-staff-card__content">
        <section class="kenyan-style-staff-card__portrait-column">
            <div class="kenyan-style-staff-card__portrait-frame">
                @if ($avatarUrl)
                    <img
                        src="{{ $avatarUrl }}"
                        alt="{{ $employee->full_name }}"
                        class="kenyan-style-staff-card__portrait"
                    >
                @else
                    <div class="kenyan-style-staff-card__portrait-placeholder">
                        {{ $initials ?: 'STAFF' }}
                    </div>
                @endif
            </div>

            <div class="kenyan-style-staff-card__staff-number">
                {{ $employee->employee_number ?: 'PENDING' }}
            </div>

            <div class="kenyan-style-staff-card__status kenyan-style-staff-card__status--{{ $status }}">
                {{ $statusLabel }}
            </div>
        </section>

        <section class="kenyan-style-staff-card__identity-column">
            <div class="kenyan-style-staff-card__identity-row kenyan-style-staff-card__identity-row--names">
                <div class="kenyan-style-staff-card__field kenyan-style-staff-card__field--wide">
                    <span>SURNAME</span>
                    <strong>{{ mb_strtoupper($employee->last_name ?: 'NOT PROVIDED') }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field kenyan-style-staff-card__field--wide">
                    <span>GIVEN NAMES</span>
                    <strong>
                        {{ mb_strtoupper(trim(implode(' ', array_filter([
                            $employee->first_name,
                            $employee->middle_name,
                        ]))) ?: 'NOT PROVIDED') }}
                    </strong>
                </div>
            </div>

            <div class="kenyan-style-staff-card__identity-grid">
                <div class="kenyan-style-staff-card__field">
                    <span>SEX</span>
                    <strong>{{ $employee->gender ? mb_strtoupper($employee->gender) : 'NOT PROVIDED' }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field">
                    <span>NATIONALITY</span>
                    <strong>{{ mb_strtoupper($employee->nationality ?: 'NOT PROVIDED') }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field">
                    <span>DATE OF BIRTH</span>
                    <strong>{{ $employee->date_of_birth?->format('d.m.Y') ?: 'NOT PROVIDED' }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field">
                    <span>PLACE OF BIRTH</span>
                    <strong>{{ mb_strtoupper($employee->place_of_birth ?: 'NOT PROVIDED') }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field">
                    <span>NATIONAL ID / PASSPORT</span>
                    <strong>{{ $employee->masked_id_number }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field">
                    <span>COUNTY</span>
                    <strong>{{ mb_strtoupper($employee->county ?: 'NOT PROVIDED') }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field">
                    <span>DATE OF ISSUE</span>
                    <strong>{{ $issueDate }}</strong>
                </div>

                <div class="kenyan-style-staff-card__field">
                    <span>VALID UNTIL</span>
                    <strong>{{ $validUntil }}</strong>
                </div>
            </div>

            <div class="kenyan-style-staff-card__employment-strip">
                <div>
                    <span>JOB TITLE</span>
                    <strong>{{ $employee->jobTitle?->name ?: 'Not assigned' }}</strong>
                </div>

                <div>
                    <span>DEPARTMENT</span>
                    <strong>{{ $employee->department?->name ?: 'Not assigned' }}</strong>
                </div>

                <div>
                    <span>WORK STATION</span>
                    <strong>{{ $employee->work_station ?: 'Not assigned' }}</strong>
                </div>
            </div>
        </section>
    </main>

    <footer class="kenyan-style-staff-card__footer">
        <div>
            <strong>INTERNAL USE ONLY</strong>
            <span>Organization-issued staff identification. Not a Kenyan National Identity Card.</span>
        </div>

        <div class="kenyan-style-staff-card__verification">
            <span>HR VERIFIED RECORD</span>
            <strong>{{ now()->format('d M Y') }}</strong>
        </div>
    </footer>
</div>

<style>
    .kenyan-style-staff-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        width: 100%;
        border: 1px solid color-mix(in srgb, var(--staff-primary) 34%, #d1d5db);
        border-radius: 22px;
        color: #15251c;
        background:
            radial-gradient(circle at 11% 78%, color-mix(in srgb, var(--staff-secondary) 18%, transparent) 0 20%, transparent 42%),
            radial-gradient(circle at 82% 18%, rgba(190, 24, 93, 0.08) 0 18%, transparent 42%),
            linear-gradient(128deg, #fbfffc 0%, #eef8f1 48%, #f9fffb 100%);
        box-shadow: 0 16px 45px rgba(15, 23, 42, 0.12);
    }

    .dark .kenyan-style-staff-card {
        color: #f8fafc;
        border-color: color-mix(in srgb, var(--staff-primary) 52%, #334155);
        background:
            radial-gradient(circle at 11% 78%, color-mix(in srgb, var(--staff-secondary) 22%, transparent) 0 20%, transparent 42%),
            radial-gradient(circle at 82% 18%, rgba(244, 63, 94, 0.10) 0 18%, transparent 42%),
            linear-gradient(128deg, #111827 0%, #14241a 48%, #101b15 100%);
    }

    .kenyan-style-staff-card__security-lines {
        position: absolute;
        inset: 0;
        z-index: -2;
        opacity: 0.6;
        background:
            repeating-radial-gradient(
                ellipse at 50% 50%,
                transparent 0 13px,
                color-mix(in srgb, var(--staff-primary) 7%, transparent) 14px 15px
            ),
            repeating-linear-gradient(
                116deg,
                transparent 0 18px,
                color-mix(in srgb, var(--staff-secondary) 5%, transparent) 19px 20px
            );
        pointer-events: none;
    }

    .kenyan-style-staff-card__header {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 18px;
        padding: 20px 26px 18px;
        border-bottom: 1px solid color-mix(in srgb, var(--staff-primary) 18%, transparent);
        background:
            linear-gradient(90deg, color-mix(in srgb, var(--staff-primary) 10%, transparent), transparent 45%),
            linear-gradient(270deg, rgba(190, 24, 93, 0.06), transparent 45%);
    }

    .kenyan-style-staff-card__organization,
    .kenyan-style-staff-card__document-title strong {
        color: var(--staff-primary);
        font-size: clamp(15px, 1.8vw, 22px);
        font-weight: 950;
        letter-spacing: 0.06em;
        line-height: 1.05;
    }

    .kenyan-style-staff-card__organization-subtitle,
    .kenyan-style-staff-card__document-title span {
        display: block;
        margin-top: 5px;
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.13em;
        text-transform: uppercase;
    }

    .dark .kenyan-style-staff-card__organization-subtitle,
    .dark .kenyan-style-staff-card__document-title span,
    .dark .kenyan-style-staff-card__field span,
    .dark .kenyan-style-staff-card__employment-strip span,
    .dark .kenyan-style-staff-card__footer span {
        color: #94a3b8;
    }

    .kenyan-style-staff-card__document-title {
        text-align: right;
    }

    .kenyan-style-staff-card__emblem {
        display: grid;
        width: 64px;
        height: 64px;
        place-items: center;
        overflow: hidden;
        border: 2px solid color-mix(in srgb, var(--staff-primary) 35%, transparent);
        border-radius: 50%;
        color: #ffffff;
        background:
            linear-gradient(145deg, var(--staff-primary), var(--staff-secondary));
        box-shadow: 0 9px 24px rgba(15, 23, 42, 0.16);
    }

    .kenyan-style-staff-card__emblem img {
        width: 100%;
        height: 100%;
        padding: 7px;
        object-fit: contain;
        background: rgba(255, 255, 255, 0.92);
    }

    .kenyan-style-staff-card__emblem span {
        font-size: 15px;
        font-weight: 950;
        letter-spacing: 0.08em;
    }

    .kenyan-style-staff-card__content {
        display: grid;
        grid-template-columns: 205px minmax(0, 1fr);
        gap: 26px;
        padding: 26px;
    }

    .kenyan-style-staff-card__portrait-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .kenyan-style-staff-card__portrait-frame {
        width: 166px;
        max-width: 100%;
        aspect-ratio: 4 / 5;
        overflow: hidden;
        border: 1px solid color-mix(in srgb, var(--staff-primary) 25%, #cbd5e1);
        border-radius: 12px;
        background: #e2e8f0;
        box-shadow:
            0 0 0 5px rgba(255, 255, 255, 0.80),
            0 12px 28px rgba(15, 23, 42, 0.16);
    }

    .dark .kenyan-style-staff-card__portrait-frame {
        box-shadow:
            0 0 0 5px rgba(15, 23, 42, 0.70),
            0 12px 28px rgba(0, 0, 0, 0.30);
    }

    .kenyan-style-staff-card__portrait,
    .kenyan-style-staff-card__portrait-placeholder {
        width: 100%;
        height: 100%;
    }

    .kenyan-style-staff-card__portrait {
        object-fit: cover;
    }

    .kenyan-style-staff-card__portrait-placeholder {
        display: grid;
        place-items: center;
        color: var(--staff-primary);
        background:
            linear-gradient(145deg, #e2e8f0, #f8fafc);
        font-size: 30px;
        font-weight: 950;
        letter-spacing: 0.08em;
    }

    .kenyan-style-staff-card__staff-number {
        padding: 7px 13px;
        border-radius: 999px;
        color: #ffffff;
        background: var(--staff-primary);
        box-shadow: 0 8px 18px color-mix(in srgb, var(--staff-primary) 22%, transparent);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.08em;
    }

    .kenyan-style-staff-card__status {
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: 0.10em;
        text-transform: uppercase;
    }

    .kenyan-style-staff-card__status--active {
        color: #166534;
        background: #dcfce7;
    }

    .kenyan-style-staff-card__status--on_leave {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .kenyan-style-staff-card__status--suspended {
        color: #92400e;
        background: #fef3c7;
    }

    .kenyan-style-staff-card__status--inactive {
        color: #374151;
        background: #e5e7eb;
    }

    .kenyan-style-staff-card__status--exited {
        color: #991b1b;
        background: #fee2e2;
    }

    .kenyan-style-staff-card__identity-column {
        min-width: 0;
        align-self: center;
    }

    .kenyan-style-staff-card__identity-row {
        display: grid;
        gap: 15px;
    }

    .kenyan-style-staff-card__identity-row--names {
        grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.25fr);
        margin-bottom: 17px;
    }

    .kenyan-style-staff-card__identity-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 15px 17px;
    }

    .kenyan-style-staff-card__field {
        min-width: 0;
        padding-left: 10px;
        border-left: 3px solid color-mix(in srgb, var(--staff-primary) 42%, transparent);
    }

    .kenyan-style-staff-card__field span,
    .kenyan-style-staff-card__employment-strip span {
        display: block;
        margin-bottom: 4px;
        color: #64748b;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .kenyan-style-staff-card__field strong {
        display: block;
        overflow-wrap: anywhere;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.25;
    }

    .kenyan-style-staff-card__identity-row--names .kenyan-style-staff-card__field strong {
        color: var(--staff-primary);
        font-size: clamp(18px, 2.35vw, 28px);
        line-height: 1.05;
        letter-spacing: 0.025em;
    }

    .kenyan-style-staff-card__identity-row--names .kenyan-style-staff-card__field:last-child strong {
        font-size: clamp(17px, 2.05vw, 25px);
    }

    .kenyan-style-staff-card__employment-strip {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-top: 19px;
        padding: 13px 15px;
        border: 1px solid color-mix(in srgb, var(--staff-primary) 17%, transparent);
        border-radius: 12px;
        background: color-mix(in srgb, var(--staff-primary) 6%, transparent);
    }

    .kenyan-style-staff-card__employment-strip strong {
        display: block;
        overflow-wrap: anywhere;
        font-size: 12px;
        line-height: 1.25;
    }

    .kenyan-style-staff-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 13px 26px;
        border-top: 1px solid color-mix(in srgb, var(--staff-primary) 18%, transparent);
        background:
            linear-gradient(90deg, color-mix(in srgb, var(--staff-primary) 7%, transparent), transparent);
        font-size: 10px;
    }

    .kenyan-style-staff-card__footer strong,
    .kenyan-style-staff-card__footer span {
        display: block;
    }

    .kenyan-style-staff-card__footer > div:first-child strong {
        color: var(--staff-primary);
        letter-spacing: 0.09em;
    }

    .kenyan-style-staff-card__footer span {
        margin-top: 3px;
        color: #64748b;
    }

    .kenyan-style-staff-card__verification {
        text-align: right;
    }

    .kenyan-style-staff-card__verification span {
        font-size: 8px;
        font-weight: 900;
        letter-spacing: 0.10em;
    }

    .kenyan-style-staff-card__verification strong {
        color: var(--staff-primary);
        font-size: 11px;
    }

    @media (max-width: 1050px) {
        .kenyan-style-staff-card__identity-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .kenyan-style-staff-card__header {
            grid-template-columns: 1fr auto;
        }

        .kenyan-style-staff-card__document-title {
            grid-column: 1 / -1;
            text-align: left;
        }

        .kenyan-style-staff-card__content {
            grid-template-columns: 1fr;
        }

        .kenyan-style-staff-card__portrait-column {
            align-items: flex-start;
        }

        .kenyan-style-staff-card__portrait-frame {
            width: 145px;
        }

        .kenyan-style-staff-card__identity-row--names,
        .kenyan-style-staff-card__employment-strip {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 520px) {
        .kenyan-style-staff-card__header,
        .kenyan-style-staff-card__content,
        .kenyan-style-staff-card__footer {
            padding-left: 17px;
            padding-right: 17px;
        }

        .kenyan-style-staff-card__identity-grid {
            grid-template-columns: 1fr;
        }

        .kenyan-style-staff-card__footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .kenyan-style-staff-card__verification {
            text-align: left;
        }
    }
</style>
