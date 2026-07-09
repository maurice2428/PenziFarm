@php
    $hour = now()->hour;

    if ($hour < 12) {
        $greeting = 'Good Morning';
    } elseif ($hour < 18) {
        $greeting = 'Good Afternoon';
    } else {
        $greeting = 'Good Evening';
    }
@endphp

<div class="flex items-center gap-3">

    {{-- Theme Toggle --}}
<button
    type="button"
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = !this.dark;

            if (this.dark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }
    }"
    x-on:click="toggle()"
    class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200/70 bg-white/70 text-gray-700 shadow-sm backdrop-blur-xl transition-all duration-200 hover:scale-105 hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
    title="Toggle Theme"
>
    <x-heroicon-o-moon x-show="!dark" x-cloak class="h-5 w-5" />
    <x-heroicon-o-sun x-show="dark" x-cloak class="h-5 w-5" />
</button>

    {{-- Greeting --}}
    <div class="hidden lg:flex flex-col leading-tight text-right">

        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
            {{ $greeting }}
        </span>

        <span class="text-sm font-semibold text-gray-800 dark:text-white">
            {{ auth()->user()->name }}
        </span>

    </div>

</div>

<style>
    .farm-topbar-greeting-inline {
        display: flex;
        align-items: center;
        white-space: nowrap;
        font-size: .875rem;
        font-weight: 700;
        color: rgb(55 65 81);
        margin-right: .5rem;
    }

    .dark .farm-topbar-greeting-inline {
        color: rgb(229 231 235);
    }

    @media (max-width: 768px) {
        .farm-topbar-greeting-inline {
            display: none !important;
        }
    }

    /* Greeting section */
    .topbar-greeting {
        line-height: 1.1;
    }

    /* Smooth transitions */
    .topbar-greeting * {
        transition: all .2s ease;
    }
</style>
