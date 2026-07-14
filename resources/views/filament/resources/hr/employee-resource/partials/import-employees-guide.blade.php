<div class="employee-import-guide">
    <div class="employee-import-guide__hero">
        <div class="employee-import-guide__hero-icon">
            <x-filament::icon
                icon="heroicon-o-user-group"
                class="employee-import-guide__icon employee-import-guide__icon--hero"
            />
        </div>

        <div class="employee-import-guide__hero-copy">
            <span class="employee-import-guide__eyebrow">Bulk employee registration</span>
            <h3>Prepare the template, upload the CSV, then confirm the column mapping</h3>
            <p>
                Use the Excel workbook to prepare and review employee data. Export the
                <strong>Employees Import</strong> sheet as <strong>CSV UTF-8</strong>
                before uploading it below.
            </p>
        </div>
    </div>

    <div class="employee-import-guide__downloads">
        <a
            href="{{ asset('templates/hr/employee_import_template.xlsx') }}"
            class="employee-import-guide__download employee-import-guide__download--excel"
            target="_blank"
            rel="noopener noreferrer"
        >
            <span class="employee-import-guide__download-icon">
                <x-filament::icon
                    icon="heroicon-o-table-cells"
                    class="employee-import-guide__icon"
                />
            </span>

            <span class="employee-import-guide__download-copy">
                <strong>Excel preparation workbook</strong>
                <small>Best for entering, reviewing and validating employee records.</small>
            </span>

            <x-filament::icon
                icon="heroicon-o-arrow-down-tray"
                class="employee-import-guide__download-arrow"
            />
        </a>

        <a
            href="{{ route('hr.employee-import-template.csv') }}"
            class="employee-import-guide__download employee-import-guide__download--csv"
        >
            <span class="employee-import-guide__download-icon">
                <x-filament::icon
                    icon="heroicon-o-document-text"
                    class="employee-import-guide__icon"
                />
            </span>

            <span class="employee-import-guide__download-copy">
                <strong>CSV import template</strong>
                <small>Ready for direct upload after replacing the sample information.</small>
            </span>

            <x-filament::icon
                icon="heroicon-o-arrow-down-tray"
                class="employee-import-guide__download-arrow"
            />
        </a>
    </div>

    <div class="employee-import-guide__steps">
        <div class="employee-import-guide__step">
            <span class="employee-import-guide__step-number">1</span>
            <span class="employee-import-guide__step-icon">
                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="employee-import-guide__icon" />
            </span>
            <div>
                <strong>Download</strong>
                <small>Choose the Excel workbook or CSV template.</small>
            </div>
        </div>

        <div class="employee-import-guide__step">
            <span class="employee-import-guide__step-number">2</span>
            <span class="employee-import-guide__step-icon">
                <x-filament::icon icon="heroicon-o-pencil-square" class="employee-import-guide__icon" />
            </span>
            <div>
                <strong>Complete</strong>
                <small>Replace the sample rows with actual employee details.</small>
            </div>
        </div>

        <div class="employee-import-guide__step">
            <span class="employee-import-guide__step-number">3</span>
            <span class="employee-import-guide__step-icon">
                <x-filament::icon icon="heroicon-o-arrows-right-left" class="employee-import-guide__icon" />
            </span>
            <div>
                <strong>Export</strong>
                <small>Save the completed sheet as CSV UTF-8.</small>
            </div>
        </div>

        <div class="employee-import-guide__step">
            <span class="employee-import-guide__step-number">4</span>
            <span class="employee-import-guide__step-icon">
                <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="employee-import-guide__icon" />
            </span>
            <div>
                <strong>Upload</strong>
                <small>Select the CSV below and verify all required mappings.</small>
            </div>
        </div>
    </div>

    <div class="employee-import-guide__notice">
        <x-filament::icon
            icon="heroicon-o-information-circle"
            class="employee-import-guide__notice-icon"
        />

        <div>
            <strong>Important before starting</strong>
            <p>
                Employee numbers may remain blank because the system generates them automatically.
                Department and job-title names must already exist and match the system records.
                A reporting manager must be entered using an existing employee number.
            </p>
        </div>
    </div>
</div>

<style>
    .employee-import-guide {
        --import-primary: rgb(var(--primary-600, 37 99 235));
        display: grid;
        gap: 1rem;
        width: 100%;
        margin-bottom: 1.25rem;
    }

    .employee-import-guide *,
    .employee-import-guide *::before,
    .employee-import-guide *::after {
        box-sizing: border-box;
    }

    .employee-import-guide__hero {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        overflow: hidden;
        border: 1px solid rgb(226 232 240);
        border-radius: 1rem;
        background:
            radial-gradient(circle at 100% 0%, rgb(var(--primary-500, 59 130 246) / 0.13), transparent 42%),
            linear-gradient(135deg, rgb(248 250 252), rgb(255 255 255));
    }

    .dark .employee-import-guide__hero {
        border-color: rgb(51 65 85);
        background:
            radial-gradient(circle at 100% 0%, rgb(var(--primary-500, 59 130 246) / 0.18), transparent 42%),
            linear-gradient(135deg, rgb(15 23 42), rgb(17 24 39));
    }

    .employee-import-guide__hero-icon {
        display: grid;
        flex: 0 0 auto;
        width: 3rem;
        height: 3rem;
        place-items: center;
        border-radius: 0.875rem;
        color: rgb(255 255 255);
        background: linear-gradient(
            135deg,
            rgb(var(--primary-600, 37 99 235)),
            rgb(var(--primary-500, 59 130 246))
        );
        box-shadow: 0 12px 24px rgb(var(--primary-600, 37 99 235) / 0.22);
    }

    .employee-import-guide__icon {
        width: 1.35rem;
        height: 1.35rem;
    }

    .employee-import-guide__icon--hero {
        width: 1.6rem;
        height: 1.6rem;
    }

    .employee-import-guide__hero-copy {
        min-width: 0;
    }

    .employee-import-guide__eyebrow {
        display: block;
        margin-bottom: 0.25rem;
        color: rgb(var(--primary-600, 37 99 235));
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .employee-import-guide__hero h3 {
        margin: 0;
        color: rgb(15 23 42);
        font-size: clamp(1rem, 2.5vw, 1.25rem);
        font-weight: 800;
        line-height: 1.3;
    }

    .dark .employee-import-guide__hero h3 {
        color: rgb(248 250 252);
    }

    .employee-import-guide__hero p {
        margin: 0.45rem 0 0;
        color: rgb(71 85 105);
        font-size: 0.875rem;
        line-height: 1.55;
    }

    .dark .employee-import-guide__hero p {
        color: rgb(203 213 225);
    }

    .employee-import-guide__downloads {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .employee-import-guide__download {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        padding: 0.9rem;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.9rem;
        color: inherit;
        background: rgb(255 255 255);
        text-decoration: none;
        transition:
            border-color 160ms ease,
            box-shadow 160ms ease,
            transform 160ms ease;
    }

    .dark .employee-import-guide__download {
        border-color: rgb(51 65 85);
        background: rgb(17 24 39);
    }

    .employee-import-guide__download:hover {
        border-color: rgb(var(--primary-400, 96 165 250));
        box-shadow: 0 10px 24px rgb(15 23 42 / 0.08);
        transform: translateY(-1px);
    }

    .employee-import-guide__download-icon {
        display: grid;
        width: 2.5rem;
        height: 2.5rem;
        place-items: center;
        border-radius: 0.75rem;
    }

    .employee-import-guide__download--excel .employee-import-guide__download-icon {
        color: rgb(21 128 61);
        background: rgb(220 252 231);
    }

    .employee-import-guide__download--csv .employee-import-guide__download-icon {
        color: rgb(29 78 216);
        background: rgb(219 234 254);
    }

    .dark .employee-import-guide__download--excel .employee-import-guide__download-icon {
        color: rgb(134 239 172);
        background: rgb(20 83 45 / 0.5);
    }

    .dark .employee-import-guide__download--csv .employee-import-guide__download-icon {
        color: rgb(147 197 253);
        background: rgb(30 64 175 / 0.35);
    }

    .employee-import-guide__download-copy {
        min-width: 0;
    }

    .employee-import-guide__download-copy strong,
    .employee-import-guide__download-copy small {
        display: block;
    }

    .employee-import-guide__download-copy strong {
        color: rgb(15 23 42);
        font-size: 0.86rem;
        font-weight: 750;
    }

    .dark .employee-import-guide__download-copy strong {
        color: rgb(248 250 252);
    }

    .employee-import-guide__download-copy small {
        margin-top: 0.2rem;
        color: rgb(100 116 139);
        font-size: 0.73rem;
        line-height: 1.35;
    }

    .dark .employee-import-guide__download-copy small {
        color: rgb(148 163 184);
    }

    .employee-import-guide__download-arrow {
        width: 1.15rem;
        height: 1.15rem;
        color: rgb(100 116 139);
    }

    .employee-import-guide__steps {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.65rem;
    }

    .employee-import-guide__step {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
        padding: 0.8rem;
        border: 1px solid rgb(226 232 240);
        border-radius: 0.85rem;
        background: rgb(248 250 252);
    }

    .dark .employee-import-guide__step {
        border-color: rgb(51 65 85);
        background: rgb(15 23 42 / 0.7);
    }

    .employee-import-guide__step-number {
        position: absolute;
        top: 0.35rem;
        right: 0.45rem;
        color: rgb(148 163 184);
        font-size: 0.65rem;
        font-weight: 900;
    }

    .employee-import-guide__step-icon {
        display: grid;
        width: 2rem;
        height: 2rem;
        place-items: center;
        border-radius: 0.65rem;
        color: rgb(var(--primary-600, 37 99 235));
        background: rgb(var(--primary-50, 239 246 255));
    }

    .dark .employee-import-guide__step-icon {
        color: rgb(var(--primary-300, 147 197 253));
        background: rgb(var(--primary-900, 30 58 138) / 0.35);
    }

    .employee-import-guide__step strong,
    .employee-import-guide__step small {
        display: block;
    }

    .employee-import-guide__step strong {
        color: rgb(30 41 59);
        font-size: 0.78rem;
        font-weight: 800;
    }

    .dark .employee-import-guide__step strong {
        color: rgb(241 245 249);
    }

    .employee-import-guide__step small {
        margin-top: 0.15rem;
        color: rgb(100 116 139);
        font-size: 0.68rem;
        line-height: 1.35;
    }

    .dark .employee-import-guide__step small {
        color: rgb(148 163 184);
    }

    .employee-import-guide__notice {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border: 1px solid rgb(253 230 138);
        border-radius: 0.85rem;
        color: rgb(120 53 15);
        background: rgb(255 251 235);
    }

    .dark .employee-import-guide__notice {
        border-color: rgb(146 64 14);
        color: rgb(254 215 170);
        background: rgb(120 53 15 / 0.22);
    }

    .employee-import-guide__notice-icon {
        flex: 0 0 auto;
        width: 1.3rem;
        height: 1.3rem;
        margin-top: 0.1rem;
    }

    .employee-import-guide__notice strong {
        display: block;
        font-size: 0.78rem;
        font-weight: 850;
    }

    .employee-import-guide__notice p {
        margin: 0.2rem 0 0;
        font-size: 0.72rem;
        line-height: 1.5;
    }

    @media (max-width: 900px) {
        .employee-import-guide__steps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .employee-import-guide {
            gap: 0.75rem;
        }

        .employee-import-guide__hero {
            gap: 0.75rem;
            padding: 0.85rem;
        }

        .employee-import-guide__hero-icon {
            width: 2.65rem;
            height: 2.65rem;
        }

        .employee-import-guide__downloads,
        .employee-import-guide__steps {
            grid-template-columns: 1fr;
        }

        .employee-import-guide__download {
            padding: 0.8rem;
        }
    }

    @media (max-width: 420px) {
        .employee-import-guide__hero {
            display: grid;
        }

        .employee-import-guide__download {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .employee-import-guide__download-arrow {
            display: none;
        }
    }
</style>
