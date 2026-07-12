<?php

namespace App\Filament\Imports;

use App\Models\HR\Employee;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Checkbox;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('employee_number')
                ->label('Employee Number')
                ->guess(['employee_no', 'staff_number'])
                ->ignoreBlankState(),

            ImportColumn::make('first_name')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:100'])
                ->example('Amina'),

            ImportColumn::make('middle_name')
                ->rules(['nullable', 'max:100'])
                ->example('Wanjiku'),

            ImportColumn::make('last_name')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:100'])
                ->example('Kariuki'),

            ImportColumn::make('id_passport_number')
                ->label('ID / Passport Number')
                ->requiredMappingForNewRecordsOnly()
                ->sensitive()
                ->rules(['required', 'max:50'])
                ->example('00000000'),

            ImportColumn::make('kra_pin')
                ->label('KRA PIN')
                ->sensitive()
                ->rules(['nullable', 'max:30']),

            ImportColumn::make('nssf_number')
                ->label('NSSF Number')
                ->sensitive()
                ->rules(['nullable', 'max:30']),

            ImportColumn::make('nhif_sha_number')
                ->label('SHA Number')
                ->guess(['sha_number', 'nhif_number'])
                ->sensitive()
                ->rules(['nullable', 'max:30']),

            ImportColumn::make('phone')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:20'])
                ->example('0700000000'),

            ImportColumn::make('alternate_phone')
                ->rules(['nullable', 'max:20']),

            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:255']),

            ImportColumn::make('gender')
                ->rules(['nullable', 'in:male,female,other']),

            ImportColumn::make('nationality')
                ->rules(['nullable', 'max:80'])
                ->example('Kenyan'),

            ImportColumn::make('date_of_birth')
                ->rules(['nullable', 'date']),

            ImportColumn::make('place_of_birth')
                ->rules(['nullable', 'max:120']),

            ImportColumn::make('marital_status')
                ->rules(['nullable', 'in:single,married,divorced,widowed']),

            ImportColumn::make('county')
                ->rules(['nullable', 'max:100']),

            ImportColumn::make('address')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('postal_address')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('hire_date')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'date']),

            ImportColumn::make('department')
                ->relationship(resolveUsing: 'name')
                ->helperText('Must match an existing department name.'),

            ImportColumn::make('jobTitle')
                ->label('Job Title')
                ->guess(['job_title'])
                ->relationship(resolveUsing: 'name')
                ->helperText('Must match an existing job title name.'),

            ImportColumn::make('manager')
                ->label('Reporting Manager Employee Number')
                ->guess(['reporting_manager_employee_number', 'manager_employee_number'])
                ->relationship(resolveUsing: 'employee_number')
                ->helperText('Use the manager employee number, and import managers first.'),

            ImportColumn::make('employment_type')
                ->rules(['nullable', 'in:permanent,contract,casual,internship,probation']),

            ImportColumn::make('work_station')
                ->rules(['nullable', 'max:150']),

            ImportColumn::make('status')
                ->rules(['nullable', 'in:active,on_leave,suspended,inactive,exited'])
                ->example('active'),

            ImportColumn::make('contract_start_date')
                ->rules(['nullable', 'date']),

            ImportColumn::make('contract_end_date')
                ->rules(['nullable', 'date', 'after_or_equal:contract_start_date']),

            ImportColumn::make('basic_salary')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('house_allowance')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('transport_allowance')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('other_allowance')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('payment_method')
                ->rules(['nullable', 'in:bank,mpesa,airtel_money,cash']),

            ImportColumn::make('bank_name')->rules(['nullable', 'max:120']),
            ImportColumn::make('bank_branch')->rules(['nullable', 'max:120']),

            ImportColumn::make('account_number')
                ->sensitive()
                ->rules(['nullable', 'max:80']),

            ImportColumn::make('mpesa_number')
                ->sensitive()
                ->rules(['nullable', 'max:20']),

            ImportColumn::make('airtel_money_number')
                ->sensitive()
                ->rules(['nullable', 'max:20']),

            ImportColumn::make('tax_enabled')->boolean(),
            ImportColumn::make('is_tax_resident')->boolean(),
            ImportColumn::make('nssf_enabled')->boolean(),
            ImportColumn::make('sha_enabled')->boolean(),
            ImportColumn::make('housing_levy_enabled')->boolean(),

            ImportColumn::make('registered_pension_contribution')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:' . config('hr.paye.registered_pension_monthly_cap', 30000)]),

            ImportColumn::make('post_retirement_medical_contribution')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:' . config('hr.paye.post_retirement_medical_monthly_cap', 15000)]),

            ImportColumn::make('mortgage_interest')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:' . config('hr.paye.mortgage_interest_monthly_cap', 30000)]),

            ImportColumn::make('insurance_premium')
                ->label('Qualifying Monthly Insurance Premium')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('tax_exemption_number')
                ->sensitive()
                ->rules(['nullable', 'max:80']),

            ImportColumn::make('tax_exemption_expiry')
                ->rules(['nullable', 'date']),

            ImportColumn::make('notes')
                ->rules(['nullable', 'max:5000']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Checkbox::make('updateExisting')
                ->label('Update existing employees when employee number or ID/passport number matches')
                ->default(true),
        ];
    }

    public function resolveRecord(): ?Employee
    {
        if ($this->options['updateExisting'] ?? true) {
            $employeeNumber = trim((string) ($this->data['employee_number'] ?? ''));
            $identityNumber = trim((string) ($this->data['id_passport_number'] ?? ''));

            if ($employeeNumber !== '') {
                return Employee::query()->firstOrNew([
                    'employee_number' => $employeeNumber,
                ]);
            }

            if ($identityNumber !== '') {
                return Employee::query()->firstOrNew([
                    'id_passport_number' => $identityNumber,
                ]);
            }
        }

        return new Employee();
    }

    protected function beforeSave(): void
    {
        $userId = $this->import->user_id;

        if (! $this->record->exists) {
            $this->record->created_by = $userId;
        }

        $this->record->updated_by = $userId;
        $this->record->status = $this->record->status ?: 'active';
        $this->record->nationality = $this->record->nationality ?: 'Kenyan';
        $this->record->is_active = $this->record->status === 'active';
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'The employee import completed. '
            . number_format($import->successful_rows)
            . ' row(s) were imported successfully.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
                . number_format($failedRowsCount)
                . ' row(s) failed and are available in the failed-rows file.';
        }

        return $body;
    }
}
