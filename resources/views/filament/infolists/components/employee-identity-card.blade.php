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

    $resolveUploadedImage = static function (mixed $storedPath): ?string {
        if (blank($storedPath)) {
            return null;
        }

        if (is_array($storedPath)) {
            $storedPath = collect($storedPath)
                ->flatten()
                ->first(
                    fn (mixed $value): bool =>
                        is_string($value) && trim($value) !== ''
                );
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
                $mimeType = Storage::disk('public')->mimeType($diskPath)
                    ?: 'image/jpeg';

                $contents = Storage::disk('public')->get($diskPath);

                return 'data:'
                    . $mimeType
                    . ';base64,'
                    . base64_encode($contents);
            }
        } catch (Throwable) {
            // Continue to public-path fallback.
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
        ->map(
            fn ($name) =>
                mb_strtoupper(mb_substr((string) $name, 0, 1))
        )
        ->take(3)
        ->implode('');

    $status = $employee->status ?: 'inactive';
    $statusLabel = str($status)->headline()->toString();

    $issueDate = $employee->created_at?->format('d.m.Y')
        ?: now()->format('d.m.Y');

    $validUntil = $employee->contract_end_date?->format('d.m.Y')
        ?: 'WHILE EMPLOYED';

    $givenNames = trim(implode(' ', array_filter([
        $employee->first_name,
        $employee->middle_name,
    ])));

    $identityFields = [
        [
            'label' => 'Sex',
            'value' => $employee->gender
                ? mb_strtoupper($employee->gender)
                : 'NOT PROVIDED',
            'icon' => 'heroicon-o-user',
        ],
        [
            'label' => 'Nationality',
            'value' => mb_strtoupper(
                $employee->nationality ?: 'NOT PROVIDED'
            ),
            'icon' => 'heroicon-o-globe-alt',
        ],
        [
            'label' => 'Date of Birth',
            'value' => $employee->date_of_birth?->format('d.m.Y')
                ?: 'NOT PROVIDED',
            'icon' => 'heroicon-o-calendar-days',
        ],
        [
            'label' => 'Place of Birth',
            'value' => mb_strtoupper(
                $employee->place_of_birth ?: 'NOT PROVIDED'
            ),
            'icon' => 'heroicon-o-map-pin',
        ],
        [
            'label' => 'National ID / Passport',
            'value' => $employee->masked_id_number,
            'icon' => 'heroicon-o-identification',
        ],
        [
            'label' => 'County',
            'value' => mb_strtoupper(
                $employee->county ?: 'NOT PROVIDED'
            ),
            'icon' => 'heroicon-o-map',
        ],
        [
            'label' => 'Date of Issue',
            'value' => $issueDate,
            'icon' => 'heroicon-o-document-check',
        ],
        [
            'label' => 'Valid Until',
            'value' => $validUntil,
            'icon' => 'heroicon-o-shield-check',
        ],
    ];

    $employmentFields = [
        [
            'label' => 'Job Title',
            'value' => $employee->jobTitle?->name ?: 'Not assigned',
            'icon' => 'heroicon-o-briefcase',
        ],
        [
            'label' => 'Department',
            'value' => $employee->department?->name ?: 'Not assigned',
            'icon' => 'heroicon-o-building-office-2',
        ],
        [
            'label' => 'Work Station',
            'value' => $employee->work_station ?: 'Not assigned',
            'icon' => 'heroicon-o-map-pin',
        ],
    ];
@endphp

<div
    class="employee-id-card"
    style="
        --employee-primary: {{ $primary }};
        --employee-secondary: {{ $secondary }};
    "
>
    <div class="employee-id-card__security" aria-hidden="true"></div>

    <header class="employee-id-card__header">
        <div class="employee-id-card__header-side employee-id-card__header-side--left">
            <strong>{{ mb_strtoupper($organizationName) }}</strong>
            <span>STAFF IDENTIFICATION</span>
        </div>

        <div class="employee-id-card__emblem" aria-hidden="true">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="">
            @else
                <span>{{ $initials ?: 'HR' }}</span>
            @endif
        </div>

        <div class="employee-id-card__header-side employee-id-card__header-side--right">
            <strong>INTERNAL STAFF CARD</strong>
            <span>HUMAN RESOURCE RECORD</span>
        </div>
    </header>

    <main class="employee-id-card__main">
        <aside class="employee-id-card__photo-column">
            <div class="employee-id-card__photo">
                @if ($avatarUrl)
                    <img
                        src="{{ $avatarUrl }}"
                        alt="{{ $employee->full_name }}"
                    >
                @else
                    <span>{{ $initials ?: 'STAFF' }}</span>
                @endif
            </div>

            <div class="employee-id-card__badges">
                <span class="employee-id-card__number">
                    {{ $employee->employee_number ?: 'PENDING' }}
                </span>

                <span
                    class="
                        employee-id-card__status
                        employee-id-card__status--{{ $status }}
                    "
                >
                    <i></i>
                    {{ $statusLabel }}
                </span>
            </div>
        </aside>

        <section class="employee-id-card__identity">
            <div class="employee-id-card__names">
                <div class="employee-id-card__name">
                    <span>Surname</span>
                    <strong>
                        {{ mb_strtoupper(
                            $employee->last_name ?: 'NOT PROVIDED'
                        ) }}
                    </strong>
                </div>

                <div class="employee-id-card__name">
                    <span>Given names</span>
                    <strong>
                        {{ mb_strtoupper(
                            $givenNames ?: 'NOT PROVIDED'
                        ) }}
                    </strong>
                </div>
            </div>

            <div class="employee-id-card__fields">
                @foreach ($identityFields as $field)
                    <article class="employee-id-card__field">
                        <span class="employee-id-card__field-icon">
                            <x-filament::icon
                                :icon="$field['icon']"
                                class="employee-id-card__icon"
                            />
                        </span>

                        <div>
                            <span>{{ $field['label'] }}</span>
                            <strong>{{ $field['value'] }}</strong>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    <section class="employee-id-card__employment">
        @foreach ($employmentFields as $field)
            <article class="employee-id-card__employment-item">
                <span class="employee-id-card__employment-icon">
                    <x-filament::icon
                        :icon="$field['icon']"
                        class="employee-id-card__icon"
                    />
                </span>

                <div>
                    <span>{{ $field['label'] }}</span>
                    <strong>{{ $field['value'] }}</strong>
                </div>
            </article>
        @endforeach
    </section>

    <footer class="employee-id-card__footer">
        <div class="employee-id-card__internal-use">
            <span class="employee-id-card__footer-icon">
                <x-filament::icon
                    icon="heroicon-o-lock-closed"
                    class="employee-id-card__icon"
                />
            </span>

            <div>
                <strong>INTERNAL USE ONLY</strong>
                <span>
                    Organization-issued staff identification.
                    Not a Kenyan National Identity Card.
                </span>
            </div>
        </div>

        <div class="employee-id-card__verified">
            <span>HR VERIFIED RECORD</span>
            <strong>{{ now()->format('d M Y') }}</strong>
        </div>
    </footer>
</div>

<style>
    .employee-id-card {
        position: relative;
        isolation: isolate;
        container-type: inline-size;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid color-mix(
            in srgb,
            var(--employee-primary) 30%,
            #d1d5db
        );
        border-radius: 1.35rem;
        color: #17251d;
        background:
            radial-gradient(
                circle at 8% 88%,
                color-mix(
                    in srgb,
                    var(--employee-secondary) 17%,
                    transparent
                ) 0 18%,
                transparent 40%
            ),
            radial-gradient(
                circle at 96% 4%,
                color-mix(
                    in srgb,
                    var(--employee-primary) 13%,
                    transparent
                ) 0 20%,
                transparent 42%
            ),
            linear-gradient(
                135deg,
                #fbfffc 0%,
                #eef8f1 52%,
                #fbfffc 100%
            );
        box-shadow: 0 18px 48px rgb(15 23 42 / 0.12);
    }

    .employee-id-card *,
    .employee-id-card *::before,
    .employee-id-card *::after {
        box-sizing: border-box;
    }

    .dark .employee-id-card {
        color: #f8fafc;
        border-color: color-mix(
            in srgb,
            var(--employee-primary) 48%,
            #334155
        );
        background:
            radial-gradient(
                circle at 8% 88%,
                color-mix(
                    in srgb,
                    var(--employee-secondary) 20%,
                    transparent
                ) 0 18%,
                transparent 40%
            ),
            radial-gradient(
                circle at 96% 4%,
                color-mix(
                    in srgb,
                    var(--employee-primary) 18%,
                    transparent
                ) 0 20%,
                transparent 42%
            ),
            linear-gradient(
                135deg,
                #111827 0%,
                #14241a 52%,
                #101b15 100%
            );
    }

    .employee-id-card__security {
        position: absolute;
        inset: 0;
        z-index: -1;
        opacity: 0.58;
        background:
            repeating-radial-gradient(
                ellipse at 50% 50%,
                transparent 0 14px,
                color-mix(
                    in srgb,
                    var(--employee-primary) 6%,
                    transparent
                ) 15px 16px
            ),
            repeating-linear-gradient(
                118deg,
                transparent 0 20px,
                color-mix(
                    in srgb,
                    var(--employee-secondary) 5%,
                    transparent
                ) 21px 22px
            );
        pointer-events: none;
    }

    .employee-id-card__icon {
        width: 1.05rem;
        height: 1.05rem;
    }

    .employee-id-card__header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.35rem;
        border-bottom: 1px solid color-mix(
            in srgb,
            var(--employee-primary) 17%,
            transparent
        );
        background: linear-gradient(
            90deg,
            color-mix(
                in srgb,
                var(--employee-primary) 8%,
                transparent
            ),
            transparent 52%
        );
    }

    .employee-id-card__header-side {
        min-width: 0;
    }

    .employee-id-card__header-side strong,
    .employee-id-card__header-side span {
        display: block;
    }

    .employee-id-card__header-side strong {
        overflow-wrap: anywhere;
        color: var(--employee-primary);
        font-size: clamp(0.85rem, 2vw, 1.25rem);
        font-weight: 950;
        letter-spacing: 0.055em;
        line-height: 1.08;
    }

    .employee-id-card__header-side span {
        margin-top: 0.28rem;
        color: #64748b;
        font-size: 0.58rem;
        font-weight: 850;
        letter-spacing: 0.11em;
    }

    .employee-id-card__header-side--right {
        text-align: right;
    }

    .employee-id-card__header-side--right strong {
        font-size: clamp(0.72rem, 1.55vw, 0.95rem);
    }

    .dark .employee-id-card__header-side span,
    .dark .employee-id-card__name > span,
    .dark .employee-id-card__field > div > span,
    .dark .employee-id-card__employment-item > div > span,
    .dark .employee-id-card__footer span {
        color: #94a3b8;
    }

    .employee-id-card__emblem {
        display: grid;
        width: 3.6rem;
        height: 3.6rem;
        place-items: center;
        overflow: hidden;
        border: 2px solid color-mix(
            in srgb,
            var(--employee-primary) 35%,
            transparent
        );
        border-radius: 50%;
        color: white;
        background: linear-gradient(
            145deg,
            var(--employee-primary),
            var(--employee-secondary)
        );
        box-shadow: 0 9px 22px rgb(15 23 42 / 0.15);
    }

    .employee-id-card__emblem img {
        width: 100%;
        height: 100%;
        padding: 0.35rem;
        object-fit: contain;
        background: rgb(255 255 255 / 0.94);
    }

    .employee-id-card__emblem span {
        font-size: 0.75rem;
        font-weight: 950;
        letter-spacing: 0.08em;
    }

    .employee-id-card__main {
        display: grid;
        grid-template-columns: 11rem minmax(0, 1fr);
        gap: 1.35rem;
        align-items: center;
        padding: 1.35rem;
    }

    .employee-id-card__photo-column {
        display: flex;
        min-width: 0;
        flex-direction: column;
        align-items: center;
        gap: 0.7rem;
    }

    .employee-id-card__photo {
        display: grid;
        width: min(9.4rem, 100%);
        aspect-ratio: 4 / 5;
        place-items: center;
        overflow: hidden;
        border: 1px solid color-mix(
            in srgb,
            var(--employee-primary) 25%,
            #cbd5e1
        );
        border-radius: 0.82rem;
        color: var(--employee-primary);
        background: linear-gradient(145deg, #e2e8f0, #f8fafc);
        box-shadow:
            0 0 0 0.3rem rgb(255 255 255 / 0.78),
            0 14px 30px rgb(15 23 42 / 0.16);
        font-size: 1.6rem;
        font-weight: 950;
        letter-spacing: 0.08em;
    }

    .dark .employee-id-card__photo {
        background: linear-gradient(145deg, #1e293b, #0f172a);
        box-shadow:
            0 0 0 0.3rem rgb(15 23 42 / 0.7),
            0 14px 30px rgb(0 0 0 / 0.28);
    }

    .employee-id-card__photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .employee-id-card__badges {
        display: grid;
        justify-items: center;
        gap: 0.4rem;
        width: 100%;
    }

    .employee-id-card__number,
    .employee-id-card__status {
        max-width: 100%;
        overflow-wrap: anywhere;
        border-radius: 999px;
        text-align: center;
        text-transform: uppercase;
    }

    .employee-id-card__number {
        padding: 0.42rem 0.72rem;
        color: white;
        background: var(--employee-primary);
        box-shadow: 0 8px 18px color-mix(
            in srgb,
            var(--employee-primary) 22%,
            transparent
        );
        font-size: 0.65rem;
        font-weight: 900;
        letter-spacing: 0.07em;
    }

    .employee-id-card__status {
        display: inline-flex;
        align-items: center;
        gap: 0.32rem;
        padding: 0.32rem 0.62rem;
        font-size: 0.58rem;
        font-weight: 900;
        letter-spacing: 0.08em;
    }

    .employee-id-card__status i {
        width: 0.4rem;
        height: 0.4rem;
        border-radius: 50%;
        background: currentColor;
    }

    .employee-id-card__status--active {
        color: #166534;
        background: #dcfce7;
    }

    .employee-id-card__status--on_leave {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .employee-id-card__status--suspended {
        color: #92400e;
        background: #fef3c7;
    }

    .employee-id-card__status--inactive {
        color: #374151;
        background: #e5e7eb;
    }

    .employee-id-card__status--exited {
        color: #991b1b;
        background: #fee2e2;
    }

    .employee-id-card__identity {
        min-width: 0;
    }

    .employee-id-card__names {
        display: grid;
        grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.2fr);
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }

    .employee-id-card__name {
        min-width: 0;
        padding: 0.75rem 0.85rem;
        border: 1px solid color-mix(
            in srgb,
            var(--employee-primary) 14%,
            transparent
        );
        border-radius: 0.78rem;
        background: color-mix(
            in srgb,
            var(--employee-primary) 4%,
            transparent
        );

    }

    .employee-id-card__name > span,
    .employee-id-card__field > div > span,
    .employee-id-card__employment-item > div > span {
        display: block;
        margin-bottom: 0.2rem;
        color: #64748b;
        font-size: 0.45rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .employee-id-card__name strong {
        display: block;
        overflow-wrap: anywhere;
        color: var(--employee-primary);
        font-size: clamp(0.55rem, 2vw, 1.05rem);
        font-weight: 950;
        letter-spacing: 0.018em;
        line-height: 1.08;
    }

    .employee-id-card__fields {
        display: grid;
        grid-template-columns: repeat(
            auto-fit,
            minmax(min(9.5rem, 100%), 1fr)
        );
        gap: 0.62rem;
    }

    .employee-id-card__field {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 0.52rem;
        min-width: 0;
        padding: 0.62rem;
        border: 1px solid color-mix(
            in srgb,
            var(--employee-primary) 11%,
            transparent
        );
        border-radius: 0.7rem;
        background: rgb(255 255 255 / 0.42);
    }

    .dark .employee-id-card__field {
        background: rgb(15 23 42 / 0.3);
    }

    .employee-id-card__field-icon,
    .employee-id-card__employment-icon,
    .employee-id-card__footer-icon {
        display: grid;
        flex: 0 0 auto;
        width: 1.85rem;
        height: 1.85rem;
        place-items: center;
        border-radius: 0.58rem;
        color: var(--employee-primary);
        background: color-mix(
            in srgb,
            var(--employee-primary) 9%,
            white
        );
    }

    .dark .employee-id-card__field-icon,
    .dark .employee-id-card__employment-icon,
    .dark .employee-id-card__footer-icon {
        background: color-mix(
            in srgb,
            var(--employee-primary) 18%,
            #0f172a
        );
    }

    .employee-id-card__field > div {
        min-width: 0;
    }

    .employee-id-card__field strong,
    .employee-id-card__employment-item strong {
        display: block;
        overflow-wrap: anywhere;
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1.3;
    }

    .employee-id-card__employment {
        display: grid;
        grid-template-columns: repeat(
            3,
            minmax(0, 1fr)
        );
        gap: 0.65rem;
        margin: 0 1.35rem 1rem;
        padding: 0.8rem;
        border: 1px solid color-mix(
            in srgb,
            var(--employee-primary) 17%,
            transparent
        );
        border-radius: 0.85rem;
        background: color-mix(
            in srgb,
            var(--employee-primary) 6%,
            transparent
        );
    }

    .employee-id-card__employment-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 0.52rem;
        min-width: 0;
    }

    .employee-id-card__footer {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 1rem;
        padding: 0.78rem 1.35rem;
        border-top: 1px solid color-mix(
            in srgb,
            var(--employee-primary) 17%,
            transparent
        );
        background: linear-gradient(
            90deg,
            color-mix(
                in srgb,
                var(--employee-primary) 7%,
                transparent
            ),
            transparent
        );
    }

    .employee-id-card__internal-use {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.62rem;
        min-width: 0;
        text-align: center;
    }

    .employee-id-card__internal-use > div {
        min-width: 0;
    }

    .employee-id-card__footer strong,
    .employee-id-card__footer span {
        display: block;
    }

    .employee-id-card__internal-use strong {
        color: var(--employee-primary);
        font-size: 0.61rem;
        font-weight: 900;
        letter-spacing: 0.08em;
    }

    .employee-id-card__internal-use span {
        margin-top: 0.14rem;
        color: #64748b;
        font-size: 0.56rem;
        line-height: 1.35;
    }

    .employee-id-card__verified {
        flex: 0 0 auto;
        text-align: right;
    }

    .employee-id-card__verified span {
        color: #64748b;
        font-size: 0.5rem;
        font-weight: 900;
        letter-spacing: 0.09em;
    }

    .employee-id-card__verified strong {
        margin-top: 0.14rem;
        color: var(--employee-primary);
        font-size: 0.64rem;
    }

    /*
     * Mobile layout:
     * Keep the employee photograph and identity information side by side,
     * matching the landscape structure of a physical identification card.
     */
    @container (max-width: 640px) {
        .employee-id-card {
            border-radius: 0.95rem;
        }

        .employee-id-card__header {
            gap: 0.38rem;
            padding: 0.55rem 0.62rem;
        }

        .employee-id-card__header-side {
            text-align: center;
        }

        .employee-id-card__header-side strong {
            font-size: clamp(0.55rem, 2.7vw, 0.75rem);
            letter-spacing: 0.035em;
        }

        .employee-id-card__header-side--right strong {
            font-size: clamp(0.48rem, 2.3vw, 0.67rem);
        }

        .employee-id-card__header-side span {
            margin-top: 0.14rem;
            font-size: clamp(0.36rem, 1.65vw, 0.46rem);
            letter-spacing: 0.055em;
        }

        .employee-id-card__emblem {
            width: 2.35rem;
            height: 2.35rem;
        }

        .employee-id-card__main {
            grid-template-columns: minmax(5.7rem, 31%) minmax(0, 69%);
            gap: 0.55rem;
            align-items: start;
            padding: 0.65rem;
        }

        .employee-id-card__photo-column {
            gap: 0.45rem;
        }

        .employee-id-card__photo {
            width: min(6.6rem, 100%);
            border-radius: 0.55rem;
            box-shadow:
                0 0 0 0.16rem rgb(255 255 255 / 0.78),
                0 8px 16px rgb(15 23 42 / 0.15);
            font-size: 1rem;
        }

        .employee-id-card__badges {
            gap: 0.28rem;
        }

        .employee-id-card__number {
            padding: 0.27rem 0.42rem;
            font-size: 0.43rem;
            letter-spacing: 0.045em;
        }

        .employee-id-card__status {
            gap: 0.2rem;
            padding: 0.21rem 0.36rem;
            font-size: 0.39rem;
            letter-spacing: 0.045em;
        }

        .employee-id-card__status i {
            width: 0.27rem;
            height: 0.27rem;
        }

        .employee-id-card__names {
            grid-template-columns: 1fr;
            gap: 0.35rem;
            margin-bottom: 0.4rem;
        }

        .employee-id-card__name {
            padding: 0.35rem 0.42rem;
            border-radius: 0.45rem;
            text-align: center;
        }

        .employee-id-card__name > span {
            margin-bottom: 0.08rem;
            font-size: 0.38rem;
            letter-spacing: 0.055em;
        }

        .employee-id-card__name strong {
            font-size: clamp(0.68rem, 3.4vw, 0.92rem);
            line-height: 1;
        }

        .employee-id-card__fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.28rem;
        }

        .employee-id-card__field {
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.27rem;
            padding: 0.28rem;
            border-radius: 0.4rem;
        }

        .employee-id-card__field-icon {
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 0.34rem;
        }

        .employee-id-card__field-icon .employee-id-card__icon {
            width: 0.68rem;
            height: 0.68rem;
        }

        .employee-id-card__field > div > span {
            margin-bottom: 0.05rem;
            font-size: 0.32rem;
            letter-spacing: 0.045em;
        }

        .employee-id-card__field strong {
            font-size: clamp(0.42rem, 2.1vw, 0.55rem);
            line-height: 1.15;
        }

        .employee-id-card__employment {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.28rem;
            margin: 0 0.65rem 0.55rem;
            padding: 0.4rem;
            border-radius: 0.5rem;
        }

        .employee-id-card__employment-item {
            display: block;
            text-align: center;
        }

        .employee-id-card__employment-icon {
            width: 1.2rem;
            height: 1.2rem;
            margin: 0 auto 0.18rem;
            border-radius: 0.35rem;
        }

        .employee-id-card__employment-icon .employee-id-card__icon {
            width: 0.7rem;
            height: 0.7rem;
        }

        .employee-id-card__employment-item > div > span {
            margin-bottom: 0.05rem;
            font-size: 0.32rem;
            letter-spacing: 0.045em;
        }

        .employee-id-card__employment-item strong {
            font-size: clamp(0.4rem, 2vw, 0.53rem);
            line-height: 1.15;
        }

        .employee-id-card__footer {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.6rem;
            text-align: center;
        }

        .employee-id-card__internal-use {
            width: 100%;
        }

        .employee-id-card__footer-icon {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 0.4rem;
        }

        .employee-id-card__footer-icon .employee-id-card__icon {
            width: 0.72rem;
            height: 0.72rem;
        }

        .employee-id-card__internal-use strong {
            font-size: 0.42rem;
        }

        .employee-id-card__internal-use span {
            font-size: 0.37rem;
        }

        .employee-id-card__verified {
            display: none;
        }
    }

    @container (max-width: 390px) {
        .employee-id-card__main {
            grid-template-columns: minmax(5.25rem, 30%) minmax(0, 70%);
            gap: 0.42rem;
            padding: 0.52rem;
        }

        .employee-id-card__fields {
            gap: 0.22rem;
        }

        .employee-id-card__field {
            gap: 0.2rem;
            padding: 0.22rem;
        }

        .employee-id-card__field-icon {
            width: 1rem;
            height: 1rem;
        }

        .employee-id-card__employment {
            margin-right: 0.52rem;
            margin-left: 0.52rem;
        }
    }

    @media print {
        .employee-id-card {
            box-shadow: none;
            break-inside: avoid;
        }
    }
</style>
