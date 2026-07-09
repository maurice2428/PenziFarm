<x-filament-panels::page>
    <div class="space-y-6">
        <style>
            .att-report-shell {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .att-hero {
                position: relative;
                overflow: hidden;
                border-radius: 1.75rem;
                padding: 1.5rem;
                background:
                    radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 32%),
                    linear-gradient(135deg, #14532d 0%, #166534 45%, #15803d 100%);
                color: white;
                box-shadow: 0 18px 45px rgba(21, 128, 61, 0.18);
            }

            .att-hero-grid {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
            }

            .att-hero h2 {
                margin: 0;
                font-size: 1.75rem;
                line-height: 1.1;
                font-weight: 800;
                letter-spacing: -0.03em;
            }

            .att-hero p {
                margin: 0.6rem 0 0;
                color: rgba(255,255,255,0.9);
                font-size: 0.95rem;
                max-width: 48rem;
            }

            .att-period-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1rem;
                border-radius: 9999px;
                background: rgba(255,255,255,0.12);
                border: 1px solid rgba(255,255,255,0.18);
                backdrop-filter: blur(12px);
                font-size: 0.9rem;
                font-weight: 600;
            }

            .att-panel {
                border-radius: 1.5rem;
                border: 1px solid rgba(229, 231, 235, 1);
                background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(249,250,251,0.96));
                box-shadow: 0 10px 28px rgba(2, 6, 23, 0.04);
                padding: 1.25rem;
            }

            .dark .att-panel {
                border-color: rgba(255,255,255,0.08);
                background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
                box-shadow: 0 12px 34px rgba(0, 0, 0, 0.22);
            }

            .att-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
                margin-bottom: 1rem;
            }

            .att-toolbar-copy h3 {
                margin: 0;
                font-size: 1.05rem;
                font-weight: 700;
                color: rgb(17 24 39);
            }

            .dark .att-toolbar-copy h3 {
                color: white;
            }

            .att-toolbar-copy p {
                margin: 0.3rem 0 0;
                font-size: 0.88rem;
                color: rgb(107 114 128);
            }

            .dark .att-toolbar-copy p {
                color: rgb(156 163 175);
            }

            .att-toolbar-actions {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .att-kpi-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
            }

            @media (min-width: 768px) {
                .att-kpi-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (min-width: 1280px) {
                .att-kpi-grid {
                    grid-template-columns: repeat(6, minmax(0, 1fr));
                }
            }

            .att-kpi-card {
                border-radius: 1.25rem;
                border: 1px solid rgba(229, 231, 235, 1);
                background: white;
                padding: 1rem;
                box-shadow: 0 6px 20px rgba(2, 6, 23, 0.04);
            }

            .dark .att-kpi-card {
                border-color: rgba(255,255,255,0.08);
                background: rgba(255,255,255,0.03);
            }

            .att-kpi-label {
                font-size: 0.72rem;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: rgb(107 114 128);
                font-weight: 700;
            }

            .att-kpi-value {
                margin-top: 0.6rem;
                font-size: 1.7rem;
                line-height: 1;
                font-weight: 800;
                letter-spacing: -0.03em;
                color: rgb(17 24 39);
            }

            .dark .att-kpi-value {
                color: white;
            }

            .att-kpi-value--present { color: rgb(22 163 74); }
            .att-kpi-value--late { color: rgb(217 119 6); }
            .att-kpi-value--absent { color: rgb(220 38 38); }
            .att-kpi-value--leave { color: rgb(2 132 199); }
            .att-kpi-value--hours { color: rgb(17 24 39); }
            .att-kpi-value--ot { color: rgb(124 58 237); }

            .dark .att-kpi-value--hours { color: white; }

            .att-table-wrap {
                overflow-x: auto;
                border-radius: 1rem;
            }

            .att-table {
                width: 100%;
                min-width: 980px;
                border-collapse: collapse;
            }

            .att-table thead th {
                text-align: left;
                font-size: 0.78rem;
                font-weight: 700;
                color: rgb(55 65 81);
                background: rgb(249 250 251);
                padding: 0.95rem 1rem;
                border-bottom: 1px solid rgb(229 231 235);
            }

            .dark .att-table thead th {
                background: rgba(255,255,255,0.03);
                color: rgb(209 213 219);
                border-bottom-color: rgba(255,255,255,0.06);
            }

            .att-table tbody td {
                padding: 0.95rem 1rem;
                border-bottom: 1px solid rgb(243 244 246);
                color: rgb(31 41 55);
                vertical-align: top;
            }

            .dark .att-table tbody td {
                border-bottom-color: rgba(255,255,255,0.05);
                color: rgb(229 231 235);
            }

            .att-table tbody tr:hover {
                background: rgba(22, 163, 74, 0.03);
            }

            .att-status-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.65rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .att-status-present { background: #dcfce7; color: #166534; }
            .att-status-late { background: #fef3c7; color: #92400e; }
            .att-status-absent { background: #fee2e2; color: #991b1b; }
            .att-status-on_leave { background: #dbeafe; color: #1d4ed8; }
            .att-status-half_day { background: #ede9fe; color: #6d28d9; }
            .att-status-holiday { background: #cffafe; color: #155e75; }
            .att-status-off_day { background: #e5e7eb; color: #374151; }

            .att-empty {
                padding: 2.5rem 1rem;
                text-align: center;
                color: rgb(107 114 128);
            }

            .dark .att-empty {
                color: rgb(156 163 175);
            }
        </style>

        <div class="att-report-shell">
            <section class="att-hero">
                <div class="att-hero-grid">
                    <div>
                        <h2>Attendance Report</h2>
                        <p>
                            Review daily, weekly, or monthly attendance performance, then export a branded PDF report for management and records.
                        </p>
                    </div>

                    <div class="att-period-chip">
                        <span>Period</span>
                        <span>
                            {{ $this->startDate ? \Carbon\Carbon::parse($this->startDate)->format('d M Y') : '-' }}
                            -
                            {{ $this->endDate ? \Carbon\Carbon::parse($this->endDate)->format('d M Y') : '-' }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="att-panel">
                <div class="att-toolbar">
                    <div class="att-toolbar-copy">
                        <h3>Report Controls</h3>
                        <p>Choose the reporting period, load the records, then export the final report to PDF.</p>
                    </div>

                    <div class="att-toolbar-actions">
                        <x-filament::button wire:click="loadReport" color="primary" icon="heroicon-o-magnifying-glass">
                            Load Report
                        </x-filament::button>

                        <x-filament::button wire:click="exportPdf" color="success" icon="heroicon-o-document-arrow-down">
                            Export PDF
                        </x-filament::button>
                    </div>
                </div>

                {{ $this->form }}
            </section>

            <section class="att-kpi-grid">
                <div class="att-kpi-card">
                    <div class="att-kpi-label">Present</div>
                    <div class="att-kpi-value att-kpi-value--present">{{ $this->presentCount }}</div>
                </div>

                <div class="att-kpi-card">
                    <div class="att-kpi-label">Late</div>
                    <div class="att-kpi-value att-kpi-value--late">{{ $this->lateCount }}</div>
                </div>

                <div class="att-kpi-card">
                    <div class="att-kpi-label">Absent</div>
                    <div class="att-kpi-value att-kpi-value--absent">{{ $this->absentCount }}</div>
                </div>

                <div class="att-kpi-card">
                    <div class="att-kpi-label">Leave</div>
                    <div class="att-kpi-value att-kpi-value--leave">{{ $this->leaveCount }}</div>
                </div>

                <div class="att-kpi-card">
                    <div class="att-kpi-label">Hours Worked</div>
                    <div class="att-kpi-value att-kpi-value--hours">{{ number_format($this->totalHoursWorked, 2) }}</div>
                </div>

                <div class="att-kpi-card">
                    <div class="att-kpi-label">Overtime</div>
                    <div class="att-kpi-value att-kpi-value--ot">{{ number_format($this->totalOvertimeHours, 2) }}</div>
                </div>
            </section>

            <section class="att-panel">
                <div class="att-toolbar">
                    <div class="att-toolbar-copy">
                        <h3>Attendance Records</h3>
                        <p>All loaded records for the selected report period.</p>
                    </div>
                </div>

                <div class="att-table-wrap">
                    <table class="att-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Status</th>
                                <th>In</th>
                                <th>Out</th>
                                <th>Hours</th>
                                <th>OT</th>
                                <th>Late</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->records as $r)
                                @php
                                    $status = $r['status'] ?? '-';
                                    $statusClass = 'att-status-' . str_replace('-', '_', str_replace(' ', '_', $status));
                                @endphp
                                <tr>
                                    <td>
                                        {{ !empty($r['attendance_date']) ? \Carbon\Carbon::parse($r['attendance_date'])->format('d M Y') : '-' }}
                                    </td>
                                    <td>{{ $r['employee_name'] ?? '-' }}</td>
                                    <td>
                                        <span class="att-status-pill {{ $statusClass }}">
                                            {{ strtoupper(str_replace('_', ' ', $status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $r['check_in'] ?? '-' }}</td>
                                    <td>{{ $r['check_out'] ?? '-' }}</td>
                                    <td>{{ number_format((float) ($r['hours_worked'] ?? 0), 2) }}</td>
                                    <td>{{ number_format((float) ($r['overtime_hours'] ?? 0), 2) }}</td>
                                    <td>{{ $r['late_minutes'] ?? 0 }}</td>
                                    <td>{{ $r['remarks'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="att-empty">
                                        No attendance records found for the selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
