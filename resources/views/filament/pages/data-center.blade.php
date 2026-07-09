<x-filament-panels::page>
    <style>
        .data-dashboard {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .data-card {
            --tone: #2f8f1d;
            --soft: #f3fbf1;
            --border: #c9e8c2;

            position: relative;
            min-height: 124px;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(135deg, #ffffff 0%, var(--soft) 100%);
            padding: 15px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
        }

        .data-card::after {
            position: absolute;
            top: -28px;
            right: -28px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: var(--tone);
            opacity: .10;
            content: "";
        }

        .data-card--success {
            --tone: #2f8f1d;
            --soft: #f3fbf1;
            --border: #c9e8c2;
        }

        .data-card--danger {
            --tone: #d94242;
            --soft: #fff5f5;
            --border: #f2c5c5;
        }

        .data-card--primary {
            --tone: #2582c4;
            --soft: #f2f8fd;
            --border: #c8e1f4;
        }

        .data-card--warning {
            --tone: #e69a00;
            --soft: #fffaf0;
            --border: #f6dda5;
        }

        .data-card__top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .data-card__icon {
            display: flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--tone);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, .12);
        }

        .data-card__icon svg {
            width: 19px;
            height: 19px;
        }

        .data-card__status {
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #ffffff;
            color: var(--tone);
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .data-card__label {
            position: relative;
            z-index: 1;
            margin-top: 13px;
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .data-card__value {
            position: relative;
            z-index: 1;
            margin-top: 5px;
            color: #0f172a;
            font-size: 25px;
            font-weight: 950;
            letter-spacing: -.04em;
            line-height: 1;
            word-break: break-word;
        }

        .data-card__value-sm {
            font-size: 13px;
            line-height: 1.25;
        }

        .data-card__copy {
            position: relative;
            z-index: 1;
            margin-top: 7px;
            color: #475569;
            font-size: 12px;
            line-height: 1.35;
        }

        .data-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border: 1px solid #d8e7d3;
            border-radius: 16px;
            background: linear-gradient(135deg, #f8fcf6 0%, #ffffff 55%, #fffaf0 100%);
            padding: 15px 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        }

        .data-notice__icon {
            display: flex;
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: #2f8f1d;
            color: #ffffff;
        }

        .data-notice__icon svg {
            width: 21px;
            height: 21px;
        }

        .data-notice__title {
            color: #172033;
            font-size: 14px;
            font-weight: 900;
        }

        .data-notice__copy {
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 1200px) {
            .data-dashboard {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .data-dashboard {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .dark .data-card {
            border-color: rgba(148, 163, 184, .25);
            background: linear-gradient(135deg, #111827 0%, #172033 100%);
        }

        .dark .data-card__label {
            color: #94a3b8;
        }

        .dark .data-card__value,
        .dark .data-notice__title {
            color: #f8fafc;
        }

        .dark .data-card__copy,
        .dark .data-notice__copy {
            color: #cbd5e1;
        }

        .dark .data-card__status {
            background: rgba(15, 23, 42, .75);
        }

        .dark .data-notice {
            border-color: rgba(148, 163, 184, .24);
            background: linear-gradient(135deg, #111827 0%, #172033 100%);
        }
    </style>

    <div class="data-dashboard">
        <div class="data-card data-card--success">
            <div class="data-card__top">
                <div class="data-card__icon">
                    <x-heroicon-o-check-badge />
                </div>

                <span class="data-card__status">Successful</span>
            </div>

            <div class="data-card__label">Completed Backups</div>

            <div class="data-card__value">
                {{ number_format($completedBackups) }}
            </div>

            <div class="data-card__copy">
                Full database SQL files created successfully.
            </div>
        </div>

        <div class="data-card data-card--danger">
            <div class="data-card__top">
                <div class="data-card__icon">
                    <x-heroicon-o-exclamation-triangle />
                </div>

                <span class="data-card__status">Review</span>
            </div>

            <div class="data-card__label">Failed Backups</div>

            <div class="data-card__value">
                {{ number_format($failedBackups) }}
            </div>

            <div class="data-card__copy">
                Failed attempts requiring administrator review.
            </div>
        </div>

        <div class="data-card data-card--primary">
            <div class="data-card__top">
                <div class="data-card__icon">
                    <x-heroicon-o-clock />
                </div>

                <span class="data-card__status">
                    {{ $backupSetting->is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>

            <div class="data-card__label">Scheduled Time</div>

            <div class="data-card__value">
                {{ substr((string) $backupSetting->run_time, 0, 5) }}
            </div>

            <div class="data-card__copy">
                {{ $backupSetting->timezone }} · keeps latest {{ number_format($backupSetting->keep_last) }} active backups.
            </div>
        </div>

        <div class="data-card data-card--warning">
            <div class="data-card__top">
                <div class="data-card__icon">
                    <x-heroicon-o-circle-stack />
                </div>

                <span class="data-card__status">Storage</span>
            </div>

            <div class="data-card__label">Backup Size</div>

            <div class="data-card__value">
                {{ $totalBackupSize }}
            </div>

            <div class="data-card__copy">
                Total completed backup storage used.
            </div>
        </div>
    </div>

    <div class="data-card data-card--primary">
        <div class="data-card__top">
            <div class="data-card__icon">
                <x-heroicon-o-server-stack />
            </div>

            <span class="data-card__status">
                {{ ucfirst($latestBackup?->status ?? 'None') }}
            </span>
        </div>

        <div class="data-card__label">Latest Backup</div>

        <div class="data-card__value data-card__value-sm">
            {{ $latestBackup?->filename ?? 'No backup has been generated yet.' }}
        </div>

        <div class="data-card__copy">
            Archived backups: {{ number_format($archivedBackups) }}.
            Use the table below to download, archive, restore, or delete backup records.
        </div>
    </div>

    <div class="data-notice">
        <div class="data-notice__icon">
            <x-heroicon-o-shield-check />
        </div>

        <div>
            <div class="data-notice__title">
                Data Management Notice
            </div>

            <div class="data-notice__copy">
                Database backups are stored in private application storage. Documents and pictures are managed through the document library.
                On the live server, keep Laravel Scheduler active so automatic backups run at the configured time.
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
