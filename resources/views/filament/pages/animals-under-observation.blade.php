<x-filament-panels::page>
    <style>
        .observation-dashboard {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .observation-card {
            --tone: #2f8f1d;
            --tone-soft: #edf8e9;
            --tone-border: #cce8c4;

            position: relative;
            min-height: 142px;
            overflow: hidden;
            border: 1px solid var(--tone-border);
            border-radius: 16px;
            background: linear-gradient(135deg, #ffffff 0%, var(--tone-soft) 100%);
            padding: 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .observation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .10);
        }

        .observation-card::after {
            position: absolute;
            right: -25px;
            top: -25px;
            width: 96px;
            height: 96px;
            border-radius: 999px;
            background: var(--tone);
            content: "";
            opacity: .10;
        }

        .observation-card--primary {
            --tone: #2f8f1d;
            --tone-soft: #f3fbf1;
            --tone-border: #c9e8c2;
        }

        .observation-card--warning {
            --tone: #e69a00;
            --tone-soft: #fffaf0;
            --tone-border: #f6dda5;
        }

        .observation-card--danger {
            --tone: #d94242;
            --tone-soft: #fff5f5;
            --tone-border: #f2c5c5;
        }

        .observation-card--info {
            --tone: #2582c4;
            --tone-soft: #f2f8fd;
            --tone-border: #c8e1f4;
        }

        .observation-card--critical {
            --tone: #b91c1c;
            --tone-soft: #fff3f3;
            --tone-border: #efb9b9;
        }

        .observation-card--slate {
            --tone: #475569;
            --tone-soft: #f8fafc;
            --tone-border: #d7dee7;
        }

        .observation-card__top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .observation-card__icon {
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

        .observation-card__icon svg {
            width: 19px;
            height: 19px;
        }

        .observation-card__status {
            border: 1px solid var(--tone-border);
            border-radius: 999px;
            background: #ffffff;
            color: var(--tone);
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            line-height: 1;
            text-transform: uppercase;
        }

        .observation-card__label {
            position: relative;
            z-index: 1;
            margin-top: 14px;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .observation-card__value {
            position: relative;
            z-index: 1;
            margin-top: 5px;
            color: #0f172a;
            font-size: 30px;
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1;
        }

        .observation-card__description {
            position: relative;
            z-index: 1;
            margin: 8px 0 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.35;
        }

        .observation-register {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 4px;
            overflow: hidden;
            border: 1px solid #d8e7d3;
            border-radius: 16px;
            background: linear-gradient(135deg, #f8fcf6 0%, #ffffff 55%, #fffaf0 100%);
            padding: 15px 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        }

        .observation-register__left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .observation-register__icon {
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

        .observation-register__icon svg {
            width: 21px;
            height: 21px;
        }

        .observation-register__title {
            color: #172033;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.2;
        }

        .observation-register__copy {
            max-width: 760px;
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .observation-register__statuses {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 7px;
        }

        .observation-register__chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 6px 9px;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
        }

        .observation-register__dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
        }

        .chip-open {
            background: #fff5d8;
            color: #9a6500;
        }

        .chip-open .observation-register__dot {
            background: #e69a00;
        }

        .chip-treatment {
            background: #fff0f0;
            color: #ba2727;
        }

        .chip-treatment .observation-register__dot {
            background: #d94242;
        }

        .chip-referred {
            background: #edf7ff;
            color: #216da4;
        }

        .chip-referred .observation-register__dot {
            background: #2582c4;
        }

        @media (max-width: 1100px) {
            .observation-dashboard {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .observation-dashboard {
                grid-template-columns: minmax(0, 1fr);
            }

            .observation-register {
                align-items: flex-start;
                flex-direction: column;
            }

            .observation-register__statuses {
                justify-content: flex-start;
            }
        }

        .dark .observation-card {
            background: linear-gradient(135deg, #111827 0%, #172033 100%);
            border-color: rgba(148, 163, 184, .25);
        }

        .dark .observation-card__label {
            color: #94a3b8;
        }

        .dark .observation-card__value,
        .dark .observation-register__title {
            color: #f8fafc;
        }

        .dark .observation-card__description,
        .dark .observation-register__copy {
            color: #cbd5e1;
        }

        .dark .observation-card__status {
            background: rgba(15, 23, 42, .75);
        }

        .dark .observation-register {
            border-color: rgba(148, 163, 184, .24);
            background: linear-gradient(135deg, #111827 0%, #172033 100%);
        }
    </style>

    <div class="observation-dashboard">
        <div class="observation-card observation-card--primary">
            <div class="observation-card__top">
                <div class="observation-card__icon">
                    <x-heroicon-o-eye />
                </div>

                <span class="observation-card__status">Live Register</span>
            </div>

            <div class="observation-card__label">Under Observation</div>

            <div class="observation-card__value">
                {{ number_format($observationSummary['animals']) }}
            </div>

            <p class="observation-card__description">
                Animals currently under active clinical monitoring.
            </p>
        </div>

        <div class="observation-card observation-card--warning">
            <div class="observation-card__top">
                <div class="observation-card__icon">
                    <x-heroicon-o-exclamation-triangle />
                </div>

                <span class="observation-card__status">Review Needed</span>
            </div>

            <div class="observation-card__label">Open Clinical Cases</div>

            <div class="observation-card__value">
                {{ number_format($observationSummary['openAnimals']) }}
            </div>

            <p class="observation-card__description">
                Cases awaiting clinical assessment or further action.
            </p>
        </div>

        <div class="observation-card observation-card--danger">
            <div class="observation-card__top">
                <div class="observation-card__icon">
                    <x-heroicon-o-heart />
                </div>

                <span class="observation-card__status">Treatment Active</span>
            </div>

            <div class="observation-card__label">Under Treatment</div>

            <div class="observation-card__value">
                {{ number_format($observationSummary['treatmentAnimals']) }}
            </div>

            <p class="observation-card__description">
                Animals currently receiving medication or care.
            </p>
        </div>

        <div class="observation-card observation-card--info">
            <div class="observation-card__top">
                <div class="observation-card__icon">
                    <x-heroicon-o-arrow-top-right-on-square />
                </div>

                <span class="observation-card__status">Follow-Up</span>
            </div>

            <div class="observation-card__label">Referred Cases</div>

            <div class="observation-card__value">
                {{ number_format($observationSummary['referredAnimals']) }}
            </div>

            <p class="observation-card__description">
                Animals requiring external or specialist review.
            </p>
        </div>

        <div class="observation-card observation-card--critical">
            <div class="observation-card__top">
                <div class="observation-card__icon">
                    <x-heroicon-o-fire />
                </div>

                <span class="observation-card__status">Priority</span>
            </div>

            <div class="observation-card__label">Critical Cases</div>

            <div class="observation-card__value">
                {{ number_format($observationSummary['criticalCases']) }}
            </div>

            <p class="observation-card__description">
                Cases that require immediate veterinary attention.
            </p>
        </div>

        <div class="observation-card observation-card--slate">
            <div class="observation-card__top">
                <div class="observation-card__icon">
                    <x-heroicon-o-clipboard-document-check />
                </div>

                <span class="observation-card__status">All Active</span>
            </div>

            <div class="observation-card__label">Active Case Records</div>

            <div class="observation-card__value">
                {{ number_format($observationSummary['activeCases']) }}
            </div>

            <p class="observation-card__description">
                Total active clinical records across the farm.
            </p>
        </div>
    </div>

    <div class="observation-register">
        <div class="observation-register__left">
            <div class="observation-register__icon">
                <x-heroicon-o-eye />
            </div>

            <div>
                <div class="observation-register__title">
                    Clinical Observation Register
                </div>

                <div class="observation-register__copy">
                    Select animal rows to export records or resolve the latest active clinical case after veterinary confirmation. Every active clinical case appears here regardless of severity.
                </div>
            </div>
        </div>

        <div class="observation-register__statuses">
            <span class="observation-register__chip chip-open">
                <span class="observation-register__dot"></span>
                Open
            </span>

            <span class="observation-register__chip chip-treatment">
                <span class="observation-register__dot"></span>
                Under Treatment
            </span>

            <span class="observation-register__chip chip-referred">
                <span class="observation-register__dot"></span>
                Referred
            </span>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
