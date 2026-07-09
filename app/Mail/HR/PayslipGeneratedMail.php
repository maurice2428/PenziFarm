<?php

namespace App\Mail\HR;

use App\Models\HR\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Payslip $payslip;

    public function __construct(Payslip $payslip)
    {
        $this->payslip = $payslip->load([
            'employee.department',
            'employee.jobTitle',
            'payroll',
            'payroll.items',
        ]);
    }

    public function envelope(): Envelope
    {
        $monthName = \Carbon\Carbon::create()->month((int) $this->payslip->payroll->month)->format('F');
        $year = $this->payslip->payroll->year;

        return new Envelope(
            subject: "Payslip for {$monthName} {$year}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hr.payslip-generated',
            with: [
                'payslip' => $this->payslip,
                'employee' => $this->payslip->employee,
                'payroll' => $this->payslip->payroll,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.hr.payslip', [
            'payslip' => $this->payslip,
            'employee' => $this->payslip->employee,
            'payroll' => $this->payslip->payroll,
            'generatedBy' => auth()->user(),
        ])->setPaper('a4', 'portrait');

        $employeeNumber = $this->payslip->employee->employee_number ?? $this->payslip->employee_id;
        $monthName = \Carbon\Carbon::create()->month((int) $this->payslip->payroll->month)->format('F');
        $year = $this->payslip->payroll->year;

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "Payslip-{$employeeNumber}-{$monthName}-{$year}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
