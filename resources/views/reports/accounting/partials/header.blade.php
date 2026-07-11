<table class="header-table">
    <tr>
        <td class="header-logo-cell">
            @if ($logoBase64)
                <img
                    src="{{ $logoBase64 }}"
                    class="header-logo"
                    alt="{{ $farmName }} logo"
                >
            @else
                <div class="header-logo-fallback">
                    {{
                        collect(
                            preg_split('/\s+/', $farmName)
                        )
                            ->filter()
                            ->take(3)
                            ->map(
                                fn ($word) =>
                                    mb_substr($word, 0, 1)
                            )
                            ->implode('')
                    }}
                </div>
            @endif
        </td>

        <td class="header-title-cell">
            <div class="farm-name">{{ $farmName }}</div>

            <div class="report-title">
                {{ $reportTitle }}
            </div>

            <div class="farm-tagline">
                {{ $farmTagline }}
            </div>
        </td>

        <td class="header-contact-cell">
            @if ($farmPhone)
                <strong>Phone:</strong> {{ $farmPhone }}<br>
            @endif

            @if ($farmEmail)
                <strong>Email:</strong> {{ $farmEmail }}<br>
            @endif

            @if ($farmAddress || $farmCounty)
                <strong>Location:</strong>
                {{ $farmAddress ?: $farmCounty }}<br>
            @endif

            @if ($kraPin)
                <strong>KRA PIN:</strong> {{ $kraPin }}
            @endif
        </td>
    </tr>
</table>
