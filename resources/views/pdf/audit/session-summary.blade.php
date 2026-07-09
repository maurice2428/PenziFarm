<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <style>
        /* Force Courier across the full audit PDF */
        html,
        body,
        body *,
        table,
        thead,
        tbody,
        tr,
        th,
        td,
        div,
        span,
        p,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        li,
        small,
        strong,
        b {
            font-family: "Courier", "Courier New", monospace !important;
        }

        body {
            font-family: "Courier", "Courier New", monospace !important;
            color: #111827;
            font-size: 10px;
            line-height: 1.45;
        }

        .report-title,
        .section-title,
        h1,
        h2,
        h3,
        th {
            font-family: "Courier", "Courier New", monospace !important;
            font-weight: 700;
        }

        @page {
            margin: 26px 28px 35px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 10px;
        }

        .header {
            background: {{ $report['primary'] }};
            color: #ffffff;
            padding: 19px 20px;
            border-radius: 12px;
        }

        .header-table,
        .summary-table,
        .kpi-table,
        .activity-table,
        .module-table,
        .trace-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-logo {
            max-height: 38px;
            max-width: 145px;
            margin-bottom: 8px;
        }

        .title {
            font-size: 19px;
            font-weight: bold;
            line-height: 1.25;
        }

        .subtitle {
            font-size: 9px;
            margin-top: 4px;
            opacity: .88;
        }

        .ref {
            border: 1px solid rgba(255, 255, 255, .45);
            padding: 6px 8px;
            font-size: 8px;
            border-radius: 15px;
        }

        .section-title {
            margin: 19px 0 8px;
            font-size: 12px;
            font-weight: bold;
            color: {{ $report['secondary'] }};
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            background: #ffffff;
        }

        .label {
            color: #6b7280;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: .5px;
        }

        .value {
            color: #172033;
            font-size: 10px;
            font-weight: bold;
            margin-top: 4px;
        }

        .kpi {
            background: #f7f9fb;
            border: 1px solid #e7ebef;
            padding: 11px 7px;
            text-align: center;
        }

        .kpi-value {
            font-size: 15px;
            font-weight: bold;
            color: {{ $report['primary'] }};
        }

        .kpi-label {
            color: #6b7280;
            font-size: 8px;
            text-transform: uppercase;
            margin-top: 5px;
            font-weight: bold;
        }

        .summary {
            padding: 11px;
            background: #f8fafc;
            border-left: 4px solid {{ $report['accent'] }};
            line-height: 1.6;
            color: #475569;
        }

        .activity-table th,
        .module-table th {
            background: #f8fafc;
            color: #64748b;
            text-align: left;
            padding: 8px;
            font-size: 8px;
            text-transform: uppercase;
        }

        .activity-table td,
        .module-table td {
            padding: 8px;
            border-top: 1px solid #e9edf1;
            vertical-align: top;
            line-height: 1.45;
        }

        .muted {
            color: #6b7280;
        }

        .footer {
            position: fixed;
            bottom: -17px;
            left: 0;
            right: 0;
            text-align: center;
            color: #7b8796;
            font-size: 8px;
        }
    </style>
</head>

<body>
    <table class="header-table header">
        <tr>
            <td>
                @if ($report['logo_data_uri'])
                    <img class="brand-logo" src="{{ $report['logo_data_uri'] }}" alt="{{ $report['farm_name'] }}">
                @endif
                <div class="title">User Audit Session Report</div>
                <div class="subtitle">{{ $report['farm_name'] }} · {{ $report['farm_tagline'] }}</div>
            </td>
            <td align="right" valign="top">
                <span class="ref">{{ $report['reference'] }}</span>
            </td>
        </tr>
    </table>

    <div class="section-title">Session Summary</div>
    <table class="summary-table">
        <tr>
            <td width="50%" style="padding:0 5px 6px 0;">
                <div class="card">
                    <div class="label">User</div>
                    <div class="value">{{ $report['actor_name'] }}</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 6px 5px;">
                <div class="card">
                    <div class="label">Email</div>
                    <div class="value">{{ $report['actor_email'] }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 5px 6px 0;">
                <div class="card">
                    <div class="label">Status / Close Reason</div>
                    <div class="value">{{ $report['status'] }} · {{ $report['close_reason'] }}</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 6px 5px;">
                <div class="card">
                    <div class="label">Duration</div>
                    <div class="value">{{ $report['duration'] }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 5px 0 0;">
                <div class="card">
                    <div class="label">Login Time</div>
                    <div class="value">{{ $report['login_at'] }}</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 5px;">
                <div class="card">
                    <div class="label">Closed / Last Seen</div>
                    <div class="value">{{ $report['closed_at'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Activity at a Glance</div>
    <table class="kpi-table">
        <tr>
            <td width="25%" style="padding-right:4px;">
                <div class="kpi">
                    <div class="kpi-value">{{ number_format($report['request_count']) }}</div>
                    <div class="kpi-label">Requests</div>
                </div>
            </td>
            <td width="25%" style="padding:0 2px;">
                <div class="kpi">
                    <div class="kpi-value">{{ number_format($report['event_count']) }}</div>
                    <div class="kpi-label">Events</div>
                </div>
            </td>
            <td width="25%" style="padding:0 2px;">
                <div class="kpi">
                    <div class="kpi-value">{{ number_format($report['risk_count']) }}</div>
                    <div class="kpi-label">Risk Events</div>
                </div>
            </td>
            <td width="25%" style="padding-left:4px;">
                <div class="kpi">
                    <div class="kpi-value">{{ $report['duration'] }}</div>
                    <div class="kpi-label">Duration</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Activity Summary</div>
    <div class="summary">{{ $report['summary'] }}</div>

    @if (count($report['modules']))
        <div class="section-title">Module Activity</div>
        <table class="module-table">
            <thead>
                <tr>
                    <th>Module</th>
                    <th align="right">Events</th>
                    <th align="right">Created</th>
                    <th align="right">Updated</th>
                    <th align="right">Risk</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['modules'] as $module)
                    <tr>
                        <td>{{ $module['module'] }}</td>
                        <td align="right">{{ number_format($module['total']) }}</td>
                        <td align="right">{{ number_format($module['created']) }}</td>
                        <td align="right">{{ number_format($module['updated']) }}</td>
                        <td align="right">{{ number_format($module['risk']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Recent Activities</div>
    <table class="activity-table">
        <thead>
            <tr>
                <th width="16%">Time</th>
                <th width="23%">Action</th>
                <th width="19%">Module</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['activities'] as $activity)
                <tr>
                    <td><strong>{{ $activity['time'] }}</strong><br><span
                            class="muted">{{ $activity['date'] }}</span></td>
                    <td>{{ $activity['action'] }}</td>
                    <td>{{ $activity['module'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($activity['description'], 165) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" align="center" class="muted">No activity records were captured for this session.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Technical Trace</div>
    <table class="trace-table">
        <tr>
            <td width="50%" style="padding:0 5px 5px 0;">
                <div class="card">
                    <div class="label">IP Address</div>
                    <div class="value">{{ $report['ip_address'] }}</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 5px 5px;">
                <div class="card">
                    <div class="label">First Page</div>
                    <div class="value">{{ $report['first_url'] }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 5px 0 0;">
                <div class="card">
                    <div class="label">Last Page</div>
                    <div class="value">{{ $report['last_url'] }}</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 5px;">
                <div class="card">
                    <div class="label">Browser / Device</div>
                    <div class="value">{{ \Illuminate\Support\Str::limit($report['user_agent'], 95) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">{{ $report['farm_name'] }} Audit System · Generated {{ $report['generated_at'] }}</div>
</body>

</html>
