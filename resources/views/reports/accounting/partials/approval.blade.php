<section class="approval-section">
    <table class="approval-table">
        <tr>
            <td class="approval-card">
                <div class="approval-title">
                    Prepared By
                </div>

                <div class="approval-name">
                    {{ $generatedBy?->name ?? 'System' }}
                </div>

                <div class="approval-role">
                    {{ $generatedByRole }}
                </div>

                <div class="approval-line"></div>

                <div class="approval-date">
                    {{
                        $generatedAt->format(
                            'd M Y, H:i'
                        )
                    }}
                    EAT
                </div>
            </td>

            <td class="approval-card">
                <div class="approval-title">
                    Authorised Signature
                </div>

                @if ($signatureBase64)
                    <img
                        src="{{ $signatureBase64 }}"
                        class="signature-image"
                        alt="Authorised signature"
                    >
                @else
                    <div class="signature-fallback">
                        Digitally Approved
                    </div>
                @endif

                <div class="approval-line"></div>

                <div class="approval-name">
                    {{ $authorizedName }}
                </div>

                <div class="approval-role">
                    {{ $authorizedTitle }}
                </div>
            </td>

            <td class="approval-card">
                <div class="approval-title">
                    Official Stamp
                </div>

                @if ($stampBase64)
                    <img
                        src="{{ $stampBase64 }}"
                        class="stamp-image"
                        alt="Official stamp"
                    >
                @else
                    <div class="stamp-fallback">
                        OFFICIAL<br>STAMP
                    </div>
                @endif

                <div class="approval-line"></div>

                <div class="approval-role">
                    {{ $farmName }}
                </div>
            </td>
        </tr>
    </table>
</section>
