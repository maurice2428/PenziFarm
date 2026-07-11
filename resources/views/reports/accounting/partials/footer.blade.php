<table class="footer-table">
    <tr>
        <td class="footer-left">
            Generated
            {{ $generatedAt->format('d M Y, H:i') }}
            EAT
        </td>

        <td class="footer-center">
            {{ $reportTitle }} - {{ $reportCode }}
        </td>

        <td class="footer-right">
            Prepared by
            {{ $generatedBy?->name ?? 'System' }}
        </td>
    </tr>

    <tr>
        <td colspan="3" class="footer-contact">
            {{ $farmName }}

            @if ($farmPhone)
                - {{ $farmPhone }}
            @endif

            @if ($farmEmail)
                - {{ $farmEmail }}
            @endif

            - {{ $confidentialityNote }}
        </td>
    </tr>
</table>
