<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Audit Session Report</title>
</head>

<body style="margin:0; padding:0; background:#f3f6f8; color:#172033; font-family:Arial, Helvetica, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        Audit session report for {{ $report['actor_name'] }} · {{ $report['reference'] }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="width:100%; background:#f3f6f8; margin:0; padding:0;">
        <tr>
            <td align="center" style="padding:28px 14px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%; max-width:640px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.10);">
                    <tr>
                        <td style="padding:28px 30px; background:{{ $report['primary'] }};">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle">
                                        @if (!empty($report['logo_path']) && is_file($report['logo_path']))
                                            <img src="{{ $message->embed($report['logo_path']) }}"
                                                alt="{{ $report['farm_name'] }}"
                                                style="display:block; max-width:150px; max-height:46px; width:auto; height:auto; margin:0 0 12px;">
                                        @endif
                                        <div style="color:#ffffff; font-size:21px; font-weight:800; line-height:1.25;">
                                            User Audit Session Report
                                        </div>
                                        <div
                                            style="color:rgba(255,255,255,.78); font-size:13px; line-height:1.55; margin-top:6px;">
                                            {{ $report['farm_name'] }} · {{ $report['farm_tagline'] }}
                                        </div>
                                    </td>
                                    <td valign="top" align="right">
                                        <div
                                            style="display:inline-block; padding:7px 10px; border:1px solid rgba(255,255,255,.28); border-radius:999px; color:#ffffff; font-size:11px; font-weight:700; letter-spacing:.08em;">
                                            {{ $report['reference'] }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px 30px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="width:48px;">
                                        <div
                                            style="width:42px; height:42px; border-radius:13px; background:{{ $report['secondary'] }}; color:#ffffff; font-size:15px; line-height:42px; text-align:center; font-weight:800;">
                                            {{ $report['actor_initials'] }}
                                        </div>
                                    </td>
                                    <td valign="middle" style="padding-left:12px;">
                                        <div style="font-size:17px; line-height:1.25; font-weight:800; color:#172033;">
                                            {{ $report['actor_name'] }}
                                        </div>
                                        <div style="font-size:13px; line-height:1.5; color:#657084; margin-top:2px;">
                                            {{ $report['actor_email'] }}
                                        </div>
                                    </td>
                                    <td valign="middle" align="right">
                                        <span
                                            style="display:inline-block; padding:6px 10px; border-radius:999px; background:{{ $report['status_is_active'] ? '#eaf8ef' : '#eef2f6' }}; color:{{ $report['status_is_active'] ? '#138a45' : '#526174' }}; font-size:11px; font-weight:800;">
                                            {{ $report['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size:14px; line-height:1.6; color:#48556a; margin:20px 0 20px;">
                                This is a formal record of the user’s activity from login until logout, session closure,
                                or expiry. A PDF copy is attached for filing.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 30px 22px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="border:1px solid #e6ebf0; border-radius:14px; overflow:hidden;">
                                <tr>
                                    <td width="50%"
                                        style="padding:13px 14px; border-bottom:1px solid #e6ebf0; border-right:1px solid #e6ebf0;">
                                        <div
                                            style="font-size:10px; color:#7a8798; font-weight:800; letter-spacing:.08em; text-transform:uppercase;">
                                            Login Time</div>
                                        <div
                                            style="font-size:13px; line-height:1.5; color:#172033; font-weight:700; margin-top:4px;">
                                            {{ $report['login_at'] }}</div>
                                    </td>
                                    <td width="50%" style="padding:13px 14px; border-bottom:1px solid #e6ebf0;">
                                        <div
                                            style="font-size:10px; color:#7a8798; font-weight:800; letter-spacing:.08em; text-transform:uppercase;">
                                            Closed / Last Seen</div>
                                        <div
                                            style="font-size:13px; line-height:1.5; color:#172033; font-weight:700; margin-top:4px;">
                                            {{ $report['closed_at'] }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:13px 14px; border-right:1px solid #e6ebf0;">
                                        <div
                                            style="font-size:10px; color:#7a8798; font-weight:800; letter-spacing:.08em; text-transform:uppercase;">
                                            Duration</div>
                                        <div
                                            style="font-size:13px; line-height:1.5; color:#172033; font-weight:700; margin-top:4px;">
                                            {{ $report['duration'] }}</div>
                                    </td>
                                    <td width="50%" style="padding:13px 14px;">
                                        <div
                                            style="font-size:10px; color:#7a8798; font-weight:800; letter-spacing:.08em; text-transform:uppercase;">
                                            Close Reason</div>
                                        <div
                                            style="font-size:13px; line-height:1.5; color:#172033; font-weight:700; margin-top:4px;">
                                            {{ $report['close_reason'] }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 30px 18px;">
                            <div style="font-size:14px; font-weight:800; color:#172033; margin-bottom:10px;">Session at
                                a glance</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="25%" style="padding:0 5px 0 0;">
                                        <div
                                            style="padding:13px 10px; border-radius:12px; background:#f6f8fa; text-align:center;">
                                            <div
                                                style="font-size:19px; line-height:1; font-weight:800; color:{{ $report['primary'] }};">
                                                {{ number_format($report['request_count']) }}</div>
                                            <div
                                                style="font-size:10px; color:#748195; font-weight:800; letter-spacing:.05em; text-transform:uppercase; margin-top:7px;">
                                                Requests</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding:0 3px;">
                                        <div
                                            style="padding:13px 10px; border-radius:12px; background:#f6f8fa; text-align:center;">
                                            <div
                                                style="font-size:19px; line-height:1; font-weight:800; color:{{ $report['secondary'] }};">
                                                {{ number_format($report['event_count']) }}</div>
                                            <div
                                                style="font-size:10px; color:#748195; font-weight:800; letter-spacing:.05em; text-transform:uppercase; margin-top:7px;">
                                                Events</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding:0 3px;">
                                        <div
                                            style="padding:13px 10px; border-radius:12px; background:#fff8ea; text-align:center;">
                                            <div style="font-size:19px; line-height:1; font-weight:800; color:#c77a00;">
                                                {{ number_format($report['risk_count']) }}</div>
                                            <div
                                                style="font-size:10px; color:#748195; font-weight:800; letter-spacing:.05em; text-transform:uppercase; margin-top:7px;">
                                                Risk Events</div>
                                        </div>
                                    </td>
                                    <td width="25%" style="padding:0 0 0 5px;">
                                        <div
                                            style="padding:13px 10px; border-radius:12px; background:#f6f8fa; text-align:center;">
                                            <div
                                                style="font-size:15px; line-height:1.2; font-weight:800; color:{{ $report['accent'] }};">
                                                {{ $report['duration'] }}</div>
                                            <div
                                                style="font-size:10px; color:#748195; font-weight:800; letter-spacing:.05em; text-transform:uppercase; margin-top:7px;">
                                                Duration</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 30px 22px;">
                            <div
                                style="padding:15px 16px; background:#f8fafc; border-left:4px solid {{ $report['accent'] }}; border-radius:8px;">
                                <div
                                    style="font-size:10px; color:#7a8798; font-weight:800; letter-spacing:.08em; text-transform:uppercase; margin-bottom:5px;">
                                    Activity Summary</div>
                                <div style="font-size:13px; line-height:1.6; color:#3e4b60;">{{ $report['summary'] }}
                                </div>
                            </div>
                        </td>
                    </tr>

                    @if (count($report['modules']))
                        <tr>
                            <td style="padding:0 30px 22px;">
                                <div style="font-size:14px; font-weight:800; color:#172033; margin-bottom:10px;">Module
                                    activity</div>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                    border="0"
                                    style="border-collapse:separate; border-spacing:0; border:1px solid #e6ebf0; border-radius:12px; overflow:hidden;">
                                    <tr style="background:#f8fafc;">
                                        <th align="left"
                                            style="padding:10px 12px; color:#627087; font-size:10px; letter-spacing:.06em; text-transform:uppercase;">
                                            Module</th>
                                        <th align="right"
                                            style="padding:10px 12px; color:#627087; font-size:10px; letter-spacing:.06em; text-transform:uppercase;">
                                            Events</th>
                                    </tr>
                                    @foreach ($report['modules'] as $module)
                                        <tr>
                                            <td
                                                style="padding:10px 12px; border-top:1px solid #edf0f3; font-size:12px; font-weight:700; color:#27344a;">
                                                {{ $module['module'] }}</td>
                                            <td align="right"
                                                style="padding:10px 12px; border-top:1px solid #edf0f3; font-size:12px; font-weight:800; color:{{ $report['primary'] }};">
                                                {{ number_format($module['total']) }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:0 30px 8px;">
                            <div style="font-size:14px; font-weight:800; color:#172033; margin-bottom:10px;">Recent
                                activities</div>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                border="0"
                                style="border-collapse:separate; border-spacing:0; border:1px solid #e6ebf0; border-radius:12px; overflow:hidden;">
                                <tr style="background:#f8fafc;">
                                    <th align="left"
                                        style="padding:10px 10px; color:#627087; font-size:10px; letter-spacing:.06em; text-transform:uppercase;">
                                        Time</th>
                                    <th align="left"
                                        style="padding:10px 10px; color:#627087; font-size:10px; letter-spacing:.06em; text-transform:uppercase;">
                                        Activity</th>
                                    <th align="left"
                                        style="padding:10px 10px; color:#627087; font-size:10px; letter-spacing:.06em; text-transform:uppercase;">
                                        Details</th>
                                </tr>
                                @forelse($report['activities'] as $activity)
                                    <tr>
                                        <td valign="top"
                                            style="padding:11px 10px; border-top:1px solid #edf0f3; white-space:nowrap;">
                                            <div style="font-size:11px; font-weight:800; color:#27344a;">
                                                {{ $activity['time'] }}</div>
                                            <div style="font-size:10px; color:#8894a6; margin-top:2px;">
                                                {{ $activity['date'] }}</div>
                                        </td>
                                        <td valign="top" style="padding:11px 10px; border-top:1px solid #edf0f3;">
                                            <div style="font-size:12px; font-weight:800; color:#27344a;">
                                                {{ $activity['action'] }}</div>
                                            <div style="font-size:10px; color:#738096; margin-top:3px;">
                                                {{ $activity['module'] }}</div>
                                        </td>
                                        <td valign="top"
                                            style="padding:11px 10px; border-top:1px solid #edf0f3; font-size:11px; line-height:1.45; color:#4d5b70;">
                                            {{ \Illuminate\Support\Str::limit($activity['description'], 120) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3"
                                            style="padding:18px 10px; font-size:12px; color:#738096; text-align:center;">
                                            No activity records were captured for this session.</td>
                                    </tr>
                                @endforelse
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:26px 30px 28px;">
                            <a href="{{ $report['download_url'] }}"
                                style="display:inline-block; background:{{ $report['primary'] }}; color:#ffffff; text-decoration:none; border-radius:9px; padding:12px 18px; font-size:13px; line-height:1; font-weight:800;">
                                Download signed PDF report
                            </a>
                            <div style="font-size:11px; line-height:1.55; color:#7c8798; margin-top:10px;">
                                The download link is valid for seven days. A PDF copy is also attached to this email.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:17px 30px 24px; background:#f8fafc; border-top:1px solid #edf0f3;">
                            <div style="font-size:11px; line-height:1.65; color:#778397;">
                                <strong style="color:#48556a;">Technical trace:</strong>
                                IP {{ $report['ip_address'] }} · First page {{ $report['first_url'] }} · Last page
                                {{ $report['last_url'] }}
                            </div>
                            <div style="font-size:11px; line-height:1.65; color:#778397; margin-top:7px;">
                                {{ $report['farm_name'] }} Audit System · Generated {{ $report['generated_at'] }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
