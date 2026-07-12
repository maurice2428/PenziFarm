@php
    $groups = collect($results)->groupBy('group');
    $minimum = (int) config('universal-search.minimum_query_length', 2);
    $resultCount = count($results);
@endphp

<div
    wire:key="penzi-universal-global-search-root"
    class="penzi-search-root"
    x-data
    x-on:click.outside="$wire.closeSearch()"
    x-on:keydown.escape.window="$wire.closeSearch()"
    x-on:keydown.window.prevent.ctrl.k="$refs.universalSearch.focus(); $wire.openSearch()"
    x-on:keydown.window.prevent.meta.k="$refs.universalSearch.focus(); $wire.openSearch()"
>
    <style>
        .penzi-search-root {
            position: relative;
            width: 100%;
        }

        .penzi-search-box {
            position: relative;
            width: 100%;
        }

        .penzi-search-leading-icon {
            position: absolute;
            top: 50%;
            left: 0.875rem;
            z-index: 2;
            display: flex;
            width: 1rem;
            height: 1rem;
            align-items: center;
            justify-content: center;
            color: rgb(156 163 175);
            pointer-events: none;
            transform: translateY(-50%);
        }

        .penzi-search-input {
            display: block;
            width: 100%;
            height: 2.625rem;
            padding: 0.625rem 5rem 0.625rem 2.75rem !important;
            border: 0;
            border-radius: 0.85rem;
            color: rgb(17 24 39);
            background: rgb(255 255 255);
            font-size: 0.875rem;
            line-height: 1.25rem;
            outline: none;
            box-shadow:
                inset 0 0 0 1px rgb(229 231 235),
                0 1px 2px rgba(15, 23, 42, 0.04);
            transition:
                box-shadow 160ms ease,
                background-color 160ms ease;
        }

        .penzi-search-input::placeholder {
            color: rgb(156 163 175);
        }

        .penzi-search-input:focus {
            box-shadow:
                inset 0 0 0 2px rgb(var(--primary-500, 34 197 94)),
                0 7px 20px rgba(15, 23, 42, 0.08);
        }

        .dark .penzi-search-input {
            color: rgb(255 255 255);
            background: rgb(17 24 39);
            box-shadow:
                inset 0 0 0 1px rgba(255, 255, 255, 0.10),
                0 1px 2px rgba(0, 0, 0, 0.20);
        }

        .dark .penzi-search-input::placeholder,
        .dark .penzi-search-leading-icon {
            color: rgb(107 114 128);
        }

        .penzi-search-actions {
            position: absolute;
            inset-block: 0;
            right: 0;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding-right: 0.75rem;
        }

        .penzi-search-clear {
            display: inline-flex;
            width: 1.75rem;
            height: 1.75rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 0.5rem;
            color: rgb(156 163 175);
            background: transparent;
            cursor: pointer;
            transition: 150ms ease;
        }

        .penzi-search-clear:hover {
            color: rgb(55 65 81);
            background: rgb(243 244 246);
        }

        .dark .penzi-search-clear:hover {
            color: rgb(229 231 235);
            background: rgba(255, 255, 255, 0.06);
        }

        .penzi-search-shortcut {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.4rem;
            padding: 0.18rem 0.38rem;
            color: rgb(107 114 128);
            background: rgb(249 250 251);
            font-size: 0.625rem;
            font-weight: 700;
        }

        .dark .penzi-search-shortcut {
            border-color: rgba(255, 255, 255, 0.10);
            color: rgb(156 163 175);
            background: rgba(255, 255, 255, 0.04);
        }

        .penzi-search-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9988;
            background: rgba(15, 23, 42, 0.22);
            backdrop-filter: blur(2px);
        }

        .dark .penzi-search-backdrop {
            background: rgba(0, 0, 0, 0.48);
        }

        .penzi-search-mega {
            position: fixed;
            top: 4.8rem;
            left: 50%;
            z-index: 9999;
            display: flex;
            width: min(72rem, calc(100vw - 2rem));
            max-height: min(76vh, 46rem);
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgb(226 232 240);
            border-radius: 1.15rem;
            background: rgba(255, 255, 255, 0.985);
            box-shadow:
                0 28px 70px rgba(15, 23, 42, 0.20),
                0 8px 24px rgba(15, 23, 42, 0.10);
            transform: translateX(-50%);
        }

        .dark .penzi-search-mega {
            border-color: rgba(255, 255, 255, 0.10);
            background: rgba(17, 24, 39, 0.985);
            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.52),
                0 8px 24px rgba(0, 0, 0, 0.26);
        }

        .penzi-search-mega-header {
            display: flex;
            min-height: 4.15rem;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.9rem 1.15rem;
            border-bottom: 1px solid rgb(241 245 249);
            background:
                linear-gradient(
                    100deg,
                    rgba(var(--primary-500, 34 197 94), 0.10),
                    rgba(var(--primary-500, 34 197 94), 0.02) 42%,
                    transparent 70%
                );
        }

        .dark .penzi-search-mega-header {
            border-color: rgba(255, 255, 255, 0.06);
            background:
                linear-gradient(
                    100deg,
                    rgba(var(--primary-500, 34 197 94), 0.12),
                    rgba(var(--primary-500, 34 197 94), 0.03) 42%,
                    transparent 70%
                );
        }

        .penzi-search-mega-title {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 0.75rem;
        }

        .penzi-search-mega-icon {
            display: flex;
            width: 2.4rem;
            height: 2.4rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 0.8rem;
            color: rgb(var(--primary-700, 21 128 61));
            background: rgba(var(--primary-500, 34 197 94), 0.13);
        }

        .penzi-search-mega-heading {
            margin: 0;
            overflow: hidden;
            color: rgb(15 23 42);
            font-size: 0.95rem;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .penzi-search-mega-subheading {
            margin-top: 0.16rem;
            overflow: hidden;
            color: rgb(100 116 139);
            font-size: 0.75rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .penzi-search-mega-heading {
            color: rgb(248 250 252);
        }

        .dark .penzi-search-mega-subheading {
            color: rgb(148 163 184);
        }

        .penzi-search-count {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid rgb(226 232 240);
            border-radius: 999px;
            padding: 0.38rem 0.65rem;
            color: rgb(71 85 105);
            background: rgba(255, 255, 255, 0.78);
            font-size: 0.72rem;
            font-weight: 700;
        }

        .dark .penzi-search-count {
            border-color: rgba(255, 255, 255, 0.08);
            color: rgb(203 213 225);
            background: rgba(255, 255, 255, 0.04);
        }

        .penzi-search-mega-body {
            min-height: 8rem;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 1rem;
            scrollbar-color:
                rgba(var(--primary-500, 34 197 94), 0.72)
                rgba(148, 163, 184, 0.16);
            scrollbar-width: thin;
        }

        .penzi-search-mega-body::-webkit-scrollbar {
            width: 0.7rem;
        }

        .penzi-search-mega-body::-webkit-scrollbar-track {
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.12);
        }

        .penzi-search-mega-body::-webkit-scrollbar-thumb {
            border: 2px solid transparent;
            border-radius: 999px;
            background:
                rgba(var(--primary-500, 34 197 94), 0.76);
            background-clip: padding-box;
        }

        .penzi-search-mega-body::-webkit-scrollbar-thumb:hover {
            background:
                rgba(var(--primary-600, 22 163 74), 0.92);
            background-clip: padding-box;
        }

        .penzi-search-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
            align-items: start;
        }

        .penzi-search-group {
            min-width: 0;
            overflow: hidden;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.95rem;
            background: rgb(255 255 255);
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.035);
        }

        .dark .penzi-search-group {
            border-color: rgba(255, 255, 255, 0.075);
            background: rgba(31, 41, 55, 0.70);
            box-shadow: none;
        }

        .penzi-search-group-header {
            position: sticky;
            top: -1rem;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.7rem 0.85rem;
            border-bottom: 1px solid rgb(241 245 249);
            color: rgb(71 85 105);
            background: rgb(248 250 252);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.075em;
            text-transform: uppercase;
        }

        .dark .penzi-search-group-header {
            border-color: rgba(255, 255, 255, 0.055);
            color: rgb(148 163 184);
            background: rgba(15, 23, 42, 0.58);
        }

        .penzi-search-group-count {
            display: inline-flex;
            min-width: 1.35rem;
            height: 1.35rem;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: rgb(var(--primary-700, 21 128 61));
            background: rgba(var(--primary-500, 34 197 94), 0.12);
            font-size: 0.65rem;
            font-weight: 900;
        }

        .penzi-search-group-list {
            padding: 0.38rem;
        }

        .penzi-search-result {
            display: flex;
            min-width: 0;
            align-items: flex-start;
            gap: 0.7rem;
            border-radius: 0.75rem;
            padding: 0.65rem 0.7rem;
            color: inherit;
            text-decoration: none;
            transition:
                transform 140ms ease,
                background-color 140ms ease,
                box-shadow 140ms ease;
        }

        .penzi-search-result:hover {
            background: rgba(var(--primary-500, 34 197 94), 0.075);
            box-shadow:
                inset 0 0 0 1px
                rgba(var(--primary-500, 34 197 94), 0.14);
            transform: translateY(-1px);
        }

        .penzi-search-result-icon {
            display: flex;
            width: 2rem;
            height: 2rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 0.65rem;
            color: rgb(100 116 139);
            background: rgb(241 245 249);
            transition: 140ms ease;
        }

        .penzi-search-result:hover .penzi-search-result-icon {
            color: rgb(var(--primary-700, 21 128 61));
            background: rgba(var(--primary-500, 34 197 94), 0.15);
        }

        .dark .penzi-search-result-icon {
            color: rgb(148 163 184);
            background: rgba(255, 255, 255, 0.055);
        }

        .penzi-search-result-content {
            min-width: 0;
            flex: 1 1 auto;
        }

        .penzi-search-result-title {
            overflow: hidden;
            color: rgb(15 23 42);
            font-size: 0.82rem;
            font-weight: 750;
            line-height: 1.25rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .penzi-search-result-subtitle {
            margin-top: 0.12rem;
            overflow: hidden;
            color: rgb(100 116 139);
            font-size: 0.7rem;
            line-height: 1rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .penzi-search-result-title {
            color: rgb(248 250 252);
        }

        .dark .penzi-search-result-subtitle {
            color: rgb(148 163 184);
        }

        .penzi-search-result-arrow {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
            margin-top: 0.42rem;
            color: rgb(203 213 225);
            transition: 140ms ease;
        }

        .penzi-search-result:hover .penzi-search-result-arrow {
            color: rgb(var(--primary-600, 22 163 74));
            transform: translateX(2px);
        }

        .penzi-search-empty {
            display: flex;
            min-height: 15rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .penzi-search-empty-icon {
            display: flex;
            width: 3.5rem;
            height: 3.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            color: rgb(148 163 184);
            background: rgb(241 245 249);
        }

        .dark .penzi-search-empty-icon {
            color: rgb(100 116 139);
            background: rgba(255, 255, 255, 0.05);
        }

        .penzi-search-empty-title {
            margin-top: 0.85rem;
            color: rgb(51 65 85);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .penzi-search-empty-text {
            max-width: 30rem;
            margin-top: 0.35rem;
            color: rgb(100 116 139);
            font-size: 0.75rem;
            line-height: 1.15rem;
        }

        .dark .penzi-search-empty-title {
            color: rgb(226 232 240);
        }

        .dark .penzi-search-empty-text {
            color: rgb(148 163 184);
        }

        .penzi-search-mega-footer {
            display: flex;
            min-height: 2.8rem;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.65rem 1rem;
            border-top: 1px solid rgb(241 245 249);
            color: rgb(100 116 139);
            background: rgb(248 250 252);
            font-size: 0.68rem;
        }

        .dark .penzi-search-mega-footer {
            border-color: rgba(255, 255, 255, 0.055);
            color: rgb(148 163 184);
            background: rgba(15, 23, 42, 0.62);
        }

        .penzi-search-footer-keys {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .penzi-search-footer-key {
            display: inline-flex;
            min-width: 1.45rem;
            height: 1.35rem;
            align-items: center;
            justify-content: center;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.35rem;
            color: rgb(71 85 105);
            background: rgb(255 255 255);
            font-size: 0.6rem;
            font-weight: 800;
        }

        .dark .penzi-search-footer-key {
            border-color: rgba(255, 255, 255, 0.08);
            color: rgb(203 213 225);
            background: rgba(255, 255, 255, 0.04);
        }

        @media (max-width: 900px) {
            .penzi-search-grid {
                grid-template-columns: 1fr;
            }

            .penzi-search-mega {
                top: 4.55rem;
                width: calc(100vw - 1rem);
                max-height: calc(100vh - 5.1rem);
                border-radius: 1rem;
            }

            .penzi-search-mega-body {
                padding: 0.75rem;
            }
        }

        @media (max-width: 640px) {
            .penzi-search-input {
                padding-right: 2.75rem !important;
            }

            .penzi-search-shortcut {
                display: none;
            }

            .penzi-search-mega {
                top: 4.2rem;
                width: calc(100vw - 0.5rem);
                max-height: calc(100vh - 4.45rem);
                border-radius: 0.85rem;
            }

            .penzi-search-mega-header {
                min-height: 3.75rem;
                padding: 0.75rem 0.85rem;
            }

            .penzi-search-mega-subheading {
                max-width: 15rem;
            }

            .penzi-search-count {
                padding: 0.32rem 0.52rem;
            }

            .penzi-search-mega-body {
                padding: 0.55rem;
            }

            .penzi-search-group-header {
                top: -0.55rem;
            }

            .penzi-search-mega-footer {
                display: none;
            }
        }
    </style>

    <div class="penzi-search-box">
        <div class="penzi-search-leading-icon">
            <x-filament::icon
                icon="heroicon-m-magnifying-glass"
                class="h-4 w-4"
            />
        </div>

        <input
            x-ref="universalSearch"
            type="search"
            wire:model.live.debounce.400ms="query"
            wire:focus="openSearch"
            wire:keydown.enter.prevent="goToFirstResult"
            autocomplete="off"
            placeholder="Search Here"
            class="penzi-search-input"
        >

        <div class="penzi-search-actions">
            <div
                wire:loading.delay
                wire:target="query"
                style="color: rgb(var(--primary-600, 22 163 74));"
            >
                <x-filament::loading-indicator class="h-4 w-4" />
            </div>

            @if (filled($query))
                <button
                    type="button"
                    wire:click="clearSearch"
                    class="penzi-search-clear"
                    aria-label="Clear search"
                >
                    <x-filament::icon
                        icon="heroicon-m-x-mark"
                        class="h-4 w-4"
                    />
                </button>
            @else
                <kbd class="penzi-search-shortcut">
                    Ctrl K
                </kbd>
            @endif
        </div>
    </div>

    @if ($open)
        <button
            type="button"
            wire:click="closeSearch"
            class="penzi-search-backdrop"
            aria-label="Close search results"
        ></button>

        <section
            class="penzi-search-mega"
            role="dialog"
            aria-modal="true"
            aria-label="Universal search results"
        >
            <header class="penzi-search-mega-header">
                <div class="penzi-search-mega-title">
                    <div class="penzi-search-mega-icon">
                        <x-filament::icon
                            icon="heroicon-o-command-line"
                            class="h-5 w-5"
                        />
                    </div>

                    <div style="min-width: 0;">
                        <h2 class="penzi-search-mega-heading">
                            Universal Search
                        </h2>

                        <div class="penzi-search-mega-subheading">
                            @if (filled($query))
                                Results for “{{ $query }}”
                            @else
                                Search across the entire farm system
                            @endif
                        </div>
                    </div>
                </div>

                <div class="penzi-search-count">
                    {{ $resultCount }}
                    {{ \Illuminate\Support\Str::plural('result', $resultCount) }}
                </div>
            </header>

            <div class="penzi-search-mega-body">
                @if (mb_strlen(trim($query)) < $minimum)
                    <div class="penzi-search-empty">
                        <div class="penzi-search-empty-icon">
                            <x-filament::icon
                                icon="heroicon-o-magnifying-glass"
                                class="h-7 w-7"
                            />
                        </div>

                        <div class="penzi-search-empty-title">
                            Start typing to search
                        </div>

                        <div class="penzi-search-empty-text">
                            Type at least {{ $minimum }} characters. Search by
                            employee number, animal tag, invoice, customer,
                            supplier, stock item, module, report, or page name.
                        </div>
                    </div>
                @elseif ($results === [])
                    <div class="penzi-search-empty">
                        <div class="penzi-search-empty-icon">
                            <x-filament::icon
                                icon="heroicon-o-document-magnifying-glass"
                                class="h-7 w-7"
                            />
                        </div>

                        <div class="penzi-search-empty-title">
                            No results found
                        </div>

                        <div class="penzi-search-empty-text">
                            Try another name, employee number, animal tag,
                            invoice number, customer, supplier, module, or
                            report name.
                        </div>
                    </div>
                @else
                    <div class="penzi-search-grid">
                        @foreach ($groups as $group => $groupResults)
                            <section
                                wire:key="search-group-{{ md5((string) $group) }}"
                                class="penzi-search-group"
                            >
                                <div class="penzi-search-group-header">
                                    <span>{{ $group }}</span>

                                    <span class="penzi-search-group-count">
                                        {{ $groupResults->count() }}
                                    </span>
                                </div>

                                <div class="penzi-search-group-list">
                                    @foreach ($groupResults as $result)
                                        <a
                                            wire:key="search-result-{{ md5($result['url']) }}"
                                            href="{{ $result['url'] }}"
                                            class="penzi-search-result"
                                        >
                                            <div class="penzi-search-result-icon">
                                                <x-filament::icon
                                                    :icon="$result['icon'] ?? 'heroicon-o-document-text'"
                                                    class="h-4 w-4"
                                                />
                                            </div>

                                            <div class="penzi-search-result-content">
                                                <div class="penzi-search-result-title">
                                                    {{ $result['title'] }}
                                                </div>

                                                @if (filled($result['subtitle'] ?? null))
                                                    <div class="penzi-search-result-subtitle">
                                                        {{ $result['subtitle'] }}
                                                    </div>
                                                @endif
                                            </div>

                                            <x-filament::icon
                                                icon="heroicon-m-chevron-right"
                                                class="penzi-search-result-arrow"
                                            />
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>

            <footer class="penzi-search-mega-footer">
                <span>
                    Results respect the signed-in user’s permissions.
                </span>

                <div class="penzi-search-footer-keys">
                    <span class="penzi-search-footer-key">Enter</span>
                    <span>Open first result</span>
                    <span class="penzi-search-footer-key">Esc</span>
                    <span>Close</span>
                </div>
            </footer>
        </section>
    @endif
</div>
