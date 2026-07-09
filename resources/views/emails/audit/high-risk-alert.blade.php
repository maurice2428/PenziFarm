@php
    $farmName = function_exists('setting')
        ? setting('farm.name', config('app.name', 'Farm ERP'))
        : config('app.name', 'Farm ERP');

    $secondaryColor = function_exists('setting') ? setting('theme.secondary', '#111827') : '#111827';
@endphp

<x-mail::message>
    <div
        style="background: linear-gradient(135deg, #dc2626, {{ $secondaryColor }}); padding: 22px; border-radius: 18px; color: #ffffff; margin-bottom: 20px;">
        <div style="font-size: 12px; opacity: .85; text-transform: uppercase; letter-spacing: 1px;">
            {{ $farmName }}
        </div>

        <div style="font-size: 24px; font-weight: 800; margin-top: 6px;">
            High-Risk Audit Alert
        </div>

        <div style="font-size: 13px; margin-top: 8px; opacity: .9;">
            A sensitive action was detected in the ERP and may require review.
        </div>
    </div>

    ## Alert Summary

    <x-mail::panel>
        **Time:** {{ $log->created_at?->format('d M Y, H:i:s') ?? 'N/A' }}
        **User:** {{ $log->actor_label }}
        **Email:** {{ $log->user_email ?: 'N/A' }}
        **Action:** {{ $log->event_label }}
        **Module:** {{ $log->module ?: 'System' }}
        **Record:** {{ $log->record_label }}
        **IP Address:** {{ $log->ip_address ?: 'N/A' }}
    </x-mail::panel>

    ## Description

    {{ $log->description ?: 'No description provided.' }}

    @if (!empty($log->old_values))
        ## Old Values

        <pre style="background:#f8fafc; padding:12px; border-radius:10px; overflow:auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
    @endif

    @if (!empty($log->new_values))
        ## New Values

        <pre style="background:#f8fafc; padding:12px; border-radius:10px; overflow:auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
    @endif

    <x-mail::panel>
        **URL:** {{ $log->url ?: 'N/A' }}
        **Device:** {{ $log->user_agent ?: 'N/A' }}
    </x-mail::panel>

    Thanks,
    {{ $farmName }} Audit System
</x-mail::message>
