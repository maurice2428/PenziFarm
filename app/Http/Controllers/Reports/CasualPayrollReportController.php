<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\HR\CasualPayroll;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CasualPayrollReportController extends Controller
{
    public function __invoke(CasualPayroll $casualPayroll)
    {
        abort_unless(auth()->user()?->can('export casual payroll'), 403);

        $casualPayroll->load(['items', 'uploader']);

        $farmName = setting('farm.name', $casualPayroll->farm_name ?? ' Farm Limited');

        $verificationText = $farmName
            . ' Casual Payroll Report | Payroll ID: ' . $casualPayroll->id
            . ' | Period: ' . optional($casualPayroll->week_start)->format('Y-m-d')
            . ' to ' . optional($casualPayroll->week_end)->format('Y-m-d')
            . ' | Total: KES ' . number_format((float) $casualPayroll->total_amount, 2)
            . ' | Generated: ' . now('Africa/Nairobi')->format('Y-m-d H:i:s') . ' EAT';

        $qrImage = null;

        try {
            $qrImage = 'data:image/png;base64,' . base64_encode(
                QrCode::format('png')
                    ->size(130)
                    ->margin(1)
                    ->generate($verificationText)
            );
        } catch (\Throwable) {
            $qrImage = null;
        }

        $pdf = Pdf::loadView('pdf.casual-payroll-report', [
            'payroll' => $casualPayroll,
            'farmName' => $farmName,
            'generatedBy' => auth()->user(),
            'generatedAt' => now('Africa/Nairobi'),
            'verificationText' => $verificationText,
            'qrImage' => $qrImage,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('casual-payroll-report-' . $casualPayroll->id . '.pdf');
    }
}
