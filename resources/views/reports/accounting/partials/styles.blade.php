<style>
    @page {
        margin: 10mm 11mm 12mm 11mm;
    }

    * {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Courier, "Courier New", monospace;
        font-size: 9px;
        line-height: 1.34;
        color: #243247;
        background: #ffffff;
    }

    .preview-toolbar {
        margin-bottom: 8px;
        text-align: right;
    }

    .preview-toolbar button {
        border: 0;
        border-radius: 4px;
        padding: 7px 12px;
        background: {{ $primaryColor }};
        color: #ffffff;
        font-family: inherit;
        font-size: 8px;
        font-weight: bold;
    }

    .document-layout {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .document-layout > thead {
        display: table-header-group;
    }

    .document-layout > tfoot {
        display: table-footer-group;
    }

    .document-layout > tbody {
        display: table-row-group;
    }

    .document-layout > thead > tr > td,
    .document-layout > tbody > tr > td,
    .document-layout > tfoot > tr > td {
        border-left: 1px solid #b8c8bd;
        border-right: 1px solid #b8c8bd;
    }

    .document-header-cell {
        padding: 7px 10px 8px;
        border-top: 1px solid #b8c8bd;
        border-bottom: 2px solid {{ $primaryColor }};
        background: #ffffff;
    }

    .document-body-cell {
        position: relative;
        padding: 9px 10px 12px;
        vertical-align: top;
        background: #ffffff;
    }

    .content-column {
        position: relative;
        margin-left: auto;
        margin-right: auto;
    }

    .orientation-portrait .content-column {
        width: 91%;
    }

    .orientation-landscape .content-column {
        width: 95%;
    }

    /*
     * Report-specific widths stop short reports from stretching
     * across the whole sheet while preserving room for long ledgers.
     */
    .report-trial-balance .report-table-frame {
        width: 96%;
        margin-left: auto;
        margin-right: auto;
    }

    .report-profit-and-loss .report-table-frame {
        width: 94%;
        margin-left: auto;
        margin-right: auto;
    }

    .report-balance-sheet .report-table-frame {
        width: 97%;
        margin-left: auto;
        margin-right: auto;
    }

    .report-general-ledger .report-table-frame {
        width: 97%;
        margin-left: auto;
        margin-right: auto;
    }

    .report-cash-flow .report-table-frame {
        width: 98%;
        margin-left: auto;
        margin-right: auto;
    }

    .document-footer-cell {
        padding: 6px 10px 7px;
        border-top: 1px solid #ccd7d0;
        border-bottom: 1px solid #b8c8bd;
        background: #ffffff;
    }

    .header-table,
    .footer-table,
    .overview-table,
    .overview-details-table,
    .summary-table,
    .notes-table,
    .report-table,
    .approval-table {
        width: 100%;
        border-collapse: collapse;
    }

    .header-table td {
        vertical-align: middle;
    }

    .header-logo-cell {
        width: 22%;
    }

    .header-logo {
        display: block;
        max-width: 118px;
        max-height: 60px;
        margin: 0 auto;
        object-fit: contain;
    }

    .header-logo-fallback {
        width: 56px;
        height: 56px;
        margin: 0 auto;
        border: 2px solid {{ $primaryColor }};
        border-radius: 50%;
        color: {{ $primaryColor }};
        font-size: 17px;
        font-weight: bold;
        line-height: 56px;
        text-align: center;
    }

    .header-title-cell {
        width: 51%;
        padding: 0 9px;
        text-align: center;
    }

    .farm-name {
        color: {{ $secondaryColor }};
        font-size: 9.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .28px;
    }

    .report-title {
        margin-top: 2px;
        color: {{ $primaryColor }};
        font-size: 17px;
        line-height: 1.08;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .35px;
    }

    .farm-tagline {
        margin-top: 3px;
        color: #6b7280;
        font-size: 7.1px;
        font-style: italic;
    }

    .header-contact-cell {
        width: 27%;
        text-align: right;
        color: #475569;
        font-size: 7px;
        line-height: 1.45;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .report-watermark {
        position: fixed;
        top: 42%;
        left: 34%;
        width: 32%;
        text-align: center;
        opacity: 0.016;
        z-index: -1;
    }

    .report-watermark img {
        max-width: 210px;
        max-height: 150px;
    }

    .report-overview {
        width: 98%;
        margin: 0 auto 9px;
        border: 1px solid #d5dfd8;
        border-left: 5px solid {{ $primaryColor }};
        background: #fbfdfb;
        page-break-inside: avoid;
    }

    .overview-table td {
        vertical-align: middle;
    }

    .overview-status {
        width: 25%;
        padding: 8px 9px;
        border-right: 1px solid #dde5df;
    }

    .overview-details {
        width: 75%;
        padding: 6px 9px;
    }

    .eyebrow,
    .summary-label,
    .note-title,
    .approval-title {
        color: #64748b;
        font-size: 6.8px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .18px;
    }

    .status-badge {
        display: inline-block;
        margin-top: 4px;
        padding: 3px 8px;
        border-radius: 10px;
        background: {{ $primaryColor }};
        color: #ffffff;
        font-size: 6.8px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-success {
        background: {{ $successColor }};
    }

    .status-danger {
        background: {{ $dangerColor }};
    }

    .status-warning {
        background: {{ $accentColor }};
        color: #111827;
    }

    .report-reference {
        margin-top: 5px;
        color: #64748b;
        font-size: 6.3px;
        overflow-wrap: anywhere;
        word-wrap: break-word;
    }

    .overview-details-table {
        font-size: 7.35px;
        table-layout: fixed;
    }

    .overview-details-table td {
        padding: 3px 5px;
        border-bottom: 1px solid #edf2ee;
        vertical-align: top;
        white-space: normal;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .overview-details-table tr:last-child td {
        border-bottom: 0;
    }

    .meta-label {
        width: 18%;
        color: {{ $secondaryColor }};
        font-weight: bold;
        white-space: nowrap !important;
    }

    .report-purpose {
        padding: 6px 9px;
        border-top: 1px solid #e4ebe6;
        color: #526171;
        font-size: 7.15px;
        line-height: 1.38;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .summary-section {
        width: 98%;
        margin: 0 auto 5px;
        page-break-inside: avoid;
    }

    .summary-table {
        table-layout: fixed;
        border-spacing: 6px 0;
        border-collapse: separate;
        margin-bottom: 6px;
    }

    .summary-card {
        padding: 9px 10px;
        border: 1px solid #d5dee5;
        border-top: 4px solid {{ $primaryColor }};
        background: #ffffff;
        vertical-align: top;
    }

    .orientation-portrait .summary-card {
        width: 50%;
    }

    .orientation-landscape .summary-card {
        width: 25%;
    }

    .summary-success {
        border-top-color: {{ $successColor }};
    }

    .summary-danger {
        border-top-color: {{ $dangerColor }};
    }

    .summary-warning {
        border-top-color: {{ $accentColor }};
    }

    .summary-value {
        margin-top: 5px;
        color: #111827;
        font-size: 12.1px;
        line-height: 1.1;
        font-weight: bold;
        white-space: normal;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .notes-table {
        width: 98%;
        margin: 0 auto 9px;
        page-break-inside: avoid;
    }

    .notes-inline {
        border-spacing: 6px 0;
        border-collapse: separate;
    }

    .notes-inline .note-card {
        width: 50%;
    }

    .notes-stacked {
        border-spacing: 0 5px;
        border-collapse: separate;
    }

    .note-card {
        padding: 8px 9px;
        border: 1px solid #d5dee5;
        background: #ffffff;
        vertical-align: top;
        font-size: 7.45px;
        line-height: 1.42;
        white-space: normal;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .note-management {
        border-left: 4px solid {{ $primaryColor }};
    }

    .note-control {
        border-left: 4px solid {{ $accentColor }};
    }

    .note-title {
        margin-bottom: 4px;
        color: {{ $secondaryColor }};
    }

    .report-table-frame {
        width: 100%;
        border: 1px solid #aebdb3;
        background: #ffffff;
    }

    .report-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: {{ $tableFontSize ?? '8px' }};
        line-height: 1.27;
    }

    .report-table thead {
        display: table-header-group;
    }

    .report-table tfoot {
        display: table-row-group;
    }

    .report-table tr {
        page-break-inside: avoid;
    }

    .report-table th {
        padding: 5px 5px;
        border: 1px solid {{ $secondaryColor }};
        background: {{ $primaryColor }};
        color: #ffffff;
        font-size: {{ $tableHeaderFontSize ?? '7.2px' }};
        line-height: 1.2;
        text-align: left;
        text-transform: uppercase;
        vertical-align: middle;
        white-space: normal;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .heading-wrap {
        display: block;
        white-space: normal;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .report-table td {
        padding: 4.5px 5px;
        border: 1px solid #d5dde4;
        vertical-align: top;
        white-space: normal;
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: normal;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #f7faf8;
    }

    .report-table tfoot td {
        padding: 6px;
        border-top: 2px solid {{ $accentColor }};
        background: #edf6ef;
        color: #111827;
        font-weight: bold;
    }

    .cell-right {
        text-align: right !important;
        white-space: nowrap !important;
    }

    .cell-center {
        text-align: center !important;
    }

    .cell-nowrap {
        white-space: nowrap !important;
        overflow: hidden;
    }

    .cell-wrap {
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: normal !important;
        hyphens: none;
    }

    /* Account and description cells receive natural line spacing. */
    .report-table td.cell-wrap {
        line-height: 1.34;
    }

    .empty-state {
        padding: 18px !important;
        color: #64748b;
        text-align: center;
    }

    .approval-section {
        width: 88%;
        margin: 16px auto 0;
        page-break-inside: avoid;
    }

    .orientation-landscape .approval-section {
        width: 74%;
    }

    .approval-table {
        table-layout: fixed;
        border-spacing: 8px 0;
        border-collapse: separate;
    }

    .approval-card {
        width: 33.333%;
        min-height: 92px;
        padding: 9px;
        border: 1px solid #d5dfd8;
        background: #fbfdfb;
        text-align: center;
        vertical-align: bottom;
    }

    .approval-title {
        margin-bottom: 6px;
        color: {{ $secondaryColor }};
    }

    .approval-name {
        color: #111827;
        font-size: 8.6px;
        font-weight: bold;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .approval-role,
    .approval-date {
        color: #64748b;
        font-size: 6.7px;
        line-height: 1.3;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    .signature-image {
        display: block;
        max-width: 135px;
        max-height: 48px;
        margin: 1px auto 4px;
    }

    .stamp-image {
        display: block;
        max-width: 90px;
        max-height: 70px;
        margin: 0 auto 4px;
    }

    .signature-fallback {
        height: 45px;
        padding-top: 15px;
        color: {{ $successColor }};
        font-size: 9px;
        font-weight: bold;
        font-style: italic;
    }

    .stamp-fallback {
        width: 62px;
        height: 62px;
        margin: 0 auto 4px;
        padding-top: 20px;
        border: 1.5px dashed {{ $primaryColor }};
        border-radius: 50%;
        color: {{ $primaryColor }};
        font-size: 6.2px;
        font-weight: bold;
        line-height: 1.3;
    }

    .approval-line {
        width: 80%;
        margin: 5px auto 4px;
        border-top: 1px solid #475569;
    }

    .footer-table {
        table-layout: fixed;
        color: #64748b;
        font-size: 6.8px;
        line-height: 1.3;
    }

    .footer-table td {
        width: 33.333%;
        vertical-align: top;
    }

    .footer-left {
        text-align: left;
    }

    .footer-center {
        text-align: center;
    }

    .footer-right {
        text-align: right;
    }

    .footer-contact {
        padding-top: 3px;
        color: #7c8797;
        font-size: 6.35px;
        text-align: center;
        white-space: normal;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    @media print {
        .preview-toolbar {
            display: none;
        }
    }
</style>
