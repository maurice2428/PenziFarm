<x-filament-panels::page>
    @php
        $farmName = setting('farm.name', config('app.name', 'Farm ERP'));

        $primaryColor = trim(setting('theme.primary', '#008f00'));
        $secondaryColor = trim(setting('theme.secondary', '#111827'));
    @endphp

    <div class="space-y-4 sm:space-y-6">

        <div
            class="overflow-hidden rounded-xl border p-4 text-white shadow-lg sm:rounded-2xl sm:p-6"
            style="
                background: linear-gradient(135deg, {{ $primaryColor }}, {{ $secondaryColor }});
                border-color: {{ $primaryColor }};
            "
        >
            <div class="flex flex-col gap-4 sm:gap-5 md:flex-row md:items-center md:justify-between">

                <div class="min-w-0 flex-1">
                    <div
                        class="break-words text-xs font-semibold uppercase tracking-wider sm:text-sm"
                        style="color: rgba(255,255,255,.82);"
                    >
                        {{ $farmName }}
                    </div>

                    <h1 class="mt-2 break-words text-2xl font-bold leading-tight text-white sm:text-3xl lg:text-4xl">
                        Email Configuration Center
                    </h1>

                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 sm:text-base"
                        style="color: rgba(255,255,255,.88);"
                    >
                        Configure SMTP mail delivery for invoices, receipts, alerts,
                        notifications, payroll emails, and automated ERP communication.
                    </p>
                </div>

                <div class="flex justify-start md:justify-end">
                    <div
                        class="rounded-xl p-3 sm:rounded-2xl sm:p-5"
                        style="background: rgba(0,0,0,.18);"
                    >
                        <x-heroicon-o-envelope class="h-10 w-10 text-white sm:h-14 sm:w-14 md:h-20 md:w-20" />
                    </div>
                </div>

            </div>
        </div>

        <div class="overflow-hidden rounded-xl sm:rounded-2xl">
            <form wire:submit.prevent="save" class="space-y-4 sm:space-y-6">
                {{ $this->form }}
            </form>
        </div>

    </div>
</x-filament-panels::page>
