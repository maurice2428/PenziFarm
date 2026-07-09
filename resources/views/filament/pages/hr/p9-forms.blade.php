<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-3xl border border-gray-200 dark:border-white/10 bg-gradient-to-r from-emerald-200 via-emerald-700 to-teal-300 p-6 text- shadow-xl">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <div class="lg:col-span-8">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-/15 ring-1 ring-white/20">
                            <x-heroicon-o-document-text class="h-8 w-8" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight">KRA P9 Forms</h1>
                            <p class="mt-2 max-w-2xl text-sm text-emerald-50/90">
                                Generate official employee P9A tax deduction cards for annual KRA filing.
                                Use the single generator for one employee, or use the bulk option for all staff for a selected year.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur-sm">
                            <div class="text-xs uppercase tracking-wide text-emerald-100">Document Type</div>
                            <div class="mt-1 text-sm font-semibold">KRA P9A</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur-sm">
                            <div class="text-xs uppercase tracking-wide text-emerald-100">Coverage</div>
                            <div class="mt-1 text-sm font-semibold">Annual Payroll</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-7 space-y-6">
                <x-filament::section
                    heading="Generate Individual P9"
                    description="Select one employee and a tax year to generate a single P9A certificate.">
                    <div class="rounded-2xl border border-gray-200 dark:border-white/10 bg-gray-50/70 dark:bg-white/[0.03] p-4">
                        {{ $this->form }}
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <x-filament::button
                            wire:click="generateP9"
                            icon="heroicon-o-document-arrow-down"
                            color="success"
                            size="lg">
                            Generate Individual P9 PDF
                        </x-filament::button>

                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Use this for one employee at a time.
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section
                    heading="Generate Bulk P9 Forms"
                    description="Generate P9A forms for all eligible employees for one year.">
                    <div class="rounded-2xl border border-dashed border-emerald-300 dark:border-emerald-700 bg-emerald-50/60 dark:bg-emerald-500/5 p-5">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <div class="md:col-span-8">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2 p-4">
                                    Bulk P9 Year
                                </label>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="bulkYear">
                                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>

                            <div class="md:col-span-4">
                                <x-filament::button
                                    wire:click="generateBulkP9"
                                    icon="heroicon-o-printer"
                                    color="warning"
                                    size="lg"
                                    class="w-full">
                                    Generate Bulk P9 PDF
                                </x-filament::button>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 p-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <x-heroicon-o-information-circle class="h-5 w-5 text-amber-500" />
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300 leading-6">
                                    Bulk generation is ideal when you have many employees and want one consolidated PDF output for filing or internal review.
                                </div>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <div class="xl:col-span-5 space-y-6">
                <x-filament::section
                    heading="KRA P9 Summary"
                    description="What the form contains and how it is used.">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-gray-200 dark:border-white/10 -white dark:-white/[0.03] p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300">
                                    <x-heroicon-o-calculator class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">What it includes</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300 leading-6">
                                        Basic salary, benefits/non-cash, gross pay, AHL, SHIF/SHA, allowable deductions,
                                        chargeable pay, tax charged, reliefs and PAYE.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 dark:border-white/10 -white dark:-white/[0.03] p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    <x-heroicon-o-document-check class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">Usage</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300 leading-6">
                                        Employees use the P9A form to support annual tax return filing on the KRA iTax platform.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 dark:border-white/10 -white dark:-white/[0.03] p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                                    <x-heroicon-o-circle-stack class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">Data source</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300 leading-6">
                                        The form is generated directly from payroll items and payroll periods for the selected year.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 dark:border-white/10 -white dark:-white/[0.03] p-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">Compliance note</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300 leading-6">
                                        Ensure payroll values for gross pay, chargeable pay, SHIF/SHA, AHL and PAYE are finalized before generating the P9A.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
