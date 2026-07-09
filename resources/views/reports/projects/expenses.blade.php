
<style>
    /*
    |--------------------------------------------------------------------------
    | LELEKWE_ACCOUNTING_PDF_VISIBILITY_FINAL_FIX
    |--------------------------------------------------------------------------
    | Generated accounting reports only.
    | Forces report text to black and Courier.
    | Keeps signature handwritten text in the farm primary color.
    */

    html,
    body,
    main,
    section,
    article {
        font-family: Courier, "Courier New", monospace !important;
        color: #000000 !important;
        background: #ffffff !important;
    }

    body * {
        font-family: Courier, "Courier New", monospace !important;
        color: #000000 !important;
    }

    /*
     * Fix the white/invisible report intro/header text.
     */
    .report-title,
    .report-title *,
    .pdf-report-title,
    .pdf-report-title *,
    .accounting-report-title,
    .accounting-report-title *,
    .accounting-pdf-title,
    .accounting-pdf-title *,
    .report-hero,
    .report-hero *,
    .pdf-hero,
    .pdf-hero *,
    .accounting-report-hero,
    .accounting-report-hero *,
    .accounting-pdf-hero,
    .accounting-pdf-hero *,
    .report-summary,
    .report-summary *,
    .pdf-summary,
    .pdf-summary *,
    .report-meta,
    .report-meta *,
    .pdf-meta,
    .pdf-meta *,
    .report-period,
    .report-period *,
    .pdf-period,
    .pdf-period *,
    .summary-card,
    .summary-card *,
    .period-card,
    .period-card *,
    .meta-card,
    .meta-card *,
    .control-card,
    .control-card *,
    .director-note,
    .director-note *,
    .executive-note,
    .executive-note *,
    .report-description,
    .pdf-description,
    .report-subtitle,
    .pdf-subtitle,
    .report-caption,
    .pdf-caption,
    .report-kicker,
    .pdf-kicker,
    .meta-label,
    .summary-label,
    .period-label,
    .meta-value,
    .summary-value,
    .period-value {
        color: #000000 !important;
        font-family: Courier, "Courier New", monospace !important;
    }

    .report-title,
    .pdf-report-title,
    .accounting-report-title,
    .accounting-pdf-title,
    .report-hero,
    .pdf-hero,
    .accounting-report-hero,
    .accounting-pdf-hero,
    .report-summary,
    .pdf-summary,
    .report-meta,
    .pdf-meta,
    .report-period,
    .pdf-period,
    .summary-card,
    .period-card,
    .meta-card,
    .control-card,
    .director-note,
    .executive-note {
        background: #ffffff !important;
        border-color: #000000 !important;
    }

    /*
     * Catch Tailwind/utility classes that may be making PDF text white.
     */
    .text-white,
    .text-gray-50,
    .text-slate-50,
    .text-zinc-50,
    .text-neutral-50,
    .text-stone-50,
    .text-gray-100,
    .text-slate-100,
    .text-emerald-50,
    .text-green-50,
    .text-lime-50 {
        color: #000000 !important;
    }

    /*
     * Headings and labels.
     */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    small,
    strong,
    b,
    div {
        color: #000000 !important;
    }

    .report-title h1,
    .pdf-report-title h1,
    .accounting-report-title h1,
    .accounting-pdf-title h1,
    .report-hero h1,
    .pdf-hero h1 {
        color: #000000 !important;
        font-weight: 800 !important;
    }

    .meta-label,
    .summary-label,
    .period-label,
    .report-period-label,
    .pdf-period-label,
    .report-kpi-label,
    .pdf-kpi-label {
        color: #000000 !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: .04em !important;
    }

    .meta-value,
    .summary-value,
    .period-value,
    .report-period-value,
    .pdf-period-value,
    .report-kpi-value,
    .pdf-kpi-value {
        color: #000000 !important;
        font-weight: 800 !important;
    }

    /*
     * Table content like 000 — Assets, Total, etc.
     */
    table,
    thead,
    tbody,
    tfoot,
    tr,
    th,
    td,
    table *,
    .report-table,
    .report-table *,
    .accounting-table,
    .accounting-table *,
    table.report,
    table.report * {
        color: #000000 !important;
        font-family: Courier, "Courier New", monospace !important;
    }

    table thead th,
    table.report thead th,
    .report-table thead th,
    .accounting-table thead th {
        color: #000000 !important;
        background: #e5e7eb !important;
        border-color: #000000 !important;
        font-weight: 800 !important;
    }

    .group-row,
    .account-group-row,
    .section-row,
    .total-row,
    .subtotal-row,
    .grand-total-row,
    tr.group-row td,
    tr.account-group-row td,
    tr.section-row td,
    tr.total-row td,
    tr.subtotal-row td,
    tr.grand-total-row td {
        color: #000000 !important;
        background: #f3f4f6 !important;
        border-color: #000000 !important;
        font-weight: 800 !important;
    }

    /*
     * Signature should NOT be black. Use the dynamic farm primary color.
     */
    .signature-handwritten,
    .signature-handwritten *,
    .handwritten,
    .handwritten *,
    .signature-script,
    .signature-script * {
        font-family: "ChopinScript", "ChopinScriptDashboard", cursive !important;
        color: {{ $primaryColor ?? setting('theme.primary', '#14532d') }} !important;
    }
</style>


@extends('reports.layouts.farm-pdf')

@section('content')
    <table class="metric-table">
        <tr>
            <td class="metric-card">
                <div class="metric-title">Total Records</div>
                <div class="metric-value">{{ number_format($totals['total_records']) }}</div>
                <div class="metric-sub">Expense entries</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Total Amount</div>
                <div class="metric-value">KES {{ number_format($totals['total_amount'], 2) }}</div>
                <div class="metric-sub">All listed expenses</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Approved</div>
                <div class="metric-value">KES {{ number_format($totals['approved_amount'], 2) }}</div>
                <div class="metric-sub">Approved expenses</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Paid</div>
                <div class="metric-value">KES {{ number_format($totals['paid_amount'], 2) }}</div>
                <div class="metric-sub">Paid expenses</div>
            </td>

            <td class="metric-card">
                <div class="metric-title">Pending</div>
                <div class="metric-value">KES {{ number_format($totals['pending_amount'], 2) }}</div>
                <div class="metric-sub">Pending approval/payment</div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="9%">Date</th>
                <th width="17%">Project</th>
                <th width="11%">Type</th>
                <th width="14%">Payee</th>
                <th width="23%">Description</th>
                <th width="8%">Method</th>
                <th width="8%">Status</th>
                <th width="10%">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($expenses as $index => $expense)
                @php
                    $statusClass = match ($expense->status) {
                        'paid', 'approved' => 'badge-success',
                        'pending' => 'badge-warning',
                        'rejected', 'cancelled' => 'badge-danger',
                        default => 'badge-gray',
                    };
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ optional($expense->expense_date)->format('d M Y') ?: '-' }}</td>
                    <td>
                        <strong>{{ $expense->project?->name ?: '-' }}</strong><br>
                        <span class="small-muted">{{ $expense->project?->project_number ?: '-' }}</span>
                    </td>
                    <td>{{ str($expense->expense_type)->replace('_', ' ')->headline() }}</td>
                    <td>{{ $expense->payee ?: '-' }}</td>
                    <td>{{ $expense->description ?: '-' }}</td>
                    <td>{{ str($expense->payment_method)->headline() }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ str($expense->status)->headline() }}</span></td>
                    <td>KES {{ number_format((float) $expense->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
