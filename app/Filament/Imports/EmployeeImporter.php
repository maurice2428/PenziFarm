<?php

namespace App\Filament\Imports;

use App\Models\HR\Employee;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('employee_number')
                ->label('Employee Number')
                ->guess(['employee_number', 'employee no', 'employee number'])
                ->exampleHeader('employee_number')
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:50'])
                ->example(''),

            ImportColumn::make('first_name')
                ->label('First Name')
                ->guess(['first_name', 'first name', 'firstname'])
                ->exampleHeader('first_name')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'string', 'max:100'])
                ->example('Amina'),

            ImportColumn::make('middle_name')
                ->label('Middle Name')
                ->rules(['nullable', 'string', 'max:100'])
                ->example('Wanjiku'),

            ImportColumn::make('last_name')
                ->label('Last Name')
                ->guess(['last_name', 'last name', 'lastname', 'surname'])
                ->exampleHeader('last_name')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'string', 'max:100'])
                ->example('Kariuki'),

            ImportColumn::make('id_passport_number')
                ->label('ID / Passport Number')
                ->guess([
                    'id_passport_number',
                    'id passport number',
                    'id/passport number',
                    'national id',
                    'national id number',
                    'national id / passport no.',
                    'passport number',
                ])
                ->exampleHeader('id_passport_number')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'string', 'max:50'])
                ->example('31000001'),

            ImportColumn::make('kra_pin')
                ->label('KRA PIN')
                ->rules(['nullable', 'string', 'max:30'])
                ->example('A010000001A'),

            ImportColumn::make('nssf_number')
                ->label('NSSF No.')
                ->rules(['nullable', 'string', 'max:30'])
                ->example('NSSF-DEMO-001'),

            ImportColumn::make('nhif_sha_number')
                ->label('SHA No.')
                ->guess(['sha_number', 'nhif_number', 'nhif_sha_number'])
                ->rules(['nullable', 'string', 'max:30'])
                ->example('SHA-DEMO-001'),

            ImportColumn::make('phone')
                ->label('Phone')
                ->guess(['phone', 'phone_number', 'phone number', 'mobile', 'mobile number'])
                ->exampleHeader('phone')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'string', 'max:20'])
                ->example('0700000001'),

            ImportColumn::make('alternate_phone')
                ->rules(['nullable', 'string', 'max:20']),

            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:255'])
                ->example('amina.kariuki@example.com'),

            ImportColumn::make('gender')
                ->rules(['nullable', 'in:male,female,other'])
                ->example('female'),

            ImportColumn::make('nationality')
                ->rules(['nullable', 'string', 'max:80'])
                ->example('Kenyan'),

            ImportColumn::make('date_of_birth')
                ->label('Date of Birth')
                ->rules(['nullable', 'date_format:Y-m-d', 'before_or_equal:today'])
                ->example('1995-05-20'),

            ImportColumn::make('place_of_birth')
                ->rules(['nullable', 'string', 'max:120'])
                ->example('Nakuru'),

            ImportColumn::make('marital_status')
                ->rules(['nullable', 'in:single,married,divorced,widowed'])
                ->example('single'),

            ImportColumn::make('county')
                ->rules(['nullable', 'string', 'max:100'])
                ->example('Nakuru'),

            ImportColumn::make('address')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Main Farm Staff Quarters'),

            ImportColumn::make('postal_address')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('P.O. Box 100-20100'),

            ImportColumn::make('hire_date')
                ->label('Hire Date')
                ->guess(['hire_date', 'hire date', 'employment date', 'date hired'])
                ->exampleHeader('hire_date')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'date_format:Y-m-d'])
                ->example('2026-07-01'),

            ImportColumn::make('department')
                ->label('Department')
                ->relationship(resolveUsing: 'name')
                ->guess(['department', 'department_name', 'department name'])
                ->exampleHeader('department')
                ->example('Human Resource'),

            ImportColumn::make('jobTitle')
                ->label('Job Title')
                ->relationship(resolveUsing: 'name')
                ->guess(['jobTitle', 'job_title', 'job title', 'job_title_name'])
                ->exampleHeader('jobTitle')
                ->example('HR Officer'),

            ImportColumn::make('manager')
                ->label('Reporting Manager Employee Number')
                ->relationship(resolveUsing: 'employee_number')
                ->exampleHeader('manager')
                ->guess([
                    'manager',
                    'manager_employee_number',
                    'reporting_manager',
                    'reporting_manager_employee_number',
                ])
                ->helperText('Use an employee number that already exists in the system, or leave blank.'),

            ImportColumn::make('employment_type')
                ->rules(['nullable', 'in:permanent,contract,casual,internship,probation'])
                ->example('permanent'),

            ImportColumn::make('work_station')
                ->label('Work Station')
                ->rules(['nullable', 'string', 'max:150'])
                ->example('Main Farm'),

            ImportColumn::make('status')
                ->rules(['nullable', 'in:active,on_leave,suspended,inactive,exited'])
                ->example('active'),

            ImportColumn::make('contract_start_date')
                ->rules(['nullable', 'date_format:Y-m-d'])
                ->example(''),

            ImportColumn::make('contract_end_date')
                ->rules(['nullable', 'date_format:Y-m-d', 'after_or_equal:contract_start_date'])
                ->example(''),

            ImportColumn::make('basic_salary')
                ->label('Basic Salary')
                ->guess(['basic_salary', 'basic salary', 'salary'])
                ->exampleHeader('basic_salary')
                ->numeric(decimalPlaces: 2)
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'numeric', 'min:0'])
                ->example('50000'),

            ImportColumn::make('house_allowance')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('10000'),

            ImportColumn::make('transport_allowance')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('5000'),

            ImportColumn::make('other_allowance')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('0'),

            ImportColumn::make('payment_method')
                ->label('Payment Method')
                ->guess(['payment_method', 'payment method', 'salary payment method'])
                ->exampleHeader('payment_method')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'in:bank,mpesa,airtel_money,cash'])
                ->example('bank'),

            ImportColumn::make('bank_name')
                ->rules(['nullable', 'string', 'max:150'])
                ->example('KCB Bank'),

            ImportColumn::make('bank_branch')
                ->rules(['nullable', 'string', 'max:150'])
                ->example('Nakuru'),

            ImportColumn::make('account_number')
                ->label('Bank Account Number')
                ->rules(['nullable', 'string', 'max:100'])
                ->example('1100000001'),

            ImportColumn::make('mpesa_number')
                ->label('M-Pesa Number')
                ->rules(['nullable', 'string', 'max:20']),

            ImportColumn::make('airtel_money_number')
                ->label('Airtel Money Number')
                ->rules(['nullable', 'string', 'max:20']),

            ImportColumn::make('tax_enabled')
                ->label('Apply PAYE')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('TRUE'),

            ImportColumn::make('is_tax_resident')
                ->label('Kenyan Tax Resident')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('TRUE'),

            ImportColumn::make('nssf_enabled')
                ->label('Apply NSSF')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('TRUE'),

            ImportColumn::make('sha_enabled')
                ->label('Apply SHIF / SHA')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('TRUE'),

            ImportColumn::make('housing_levy_enabled')
                ->label('Apply Housing Levy')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('TRUE'),

            ImportColumn::make('registered_pension_contribution')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:30000'])
                ->example('0'),

            ImportColumn::make('post_retirement_medical_contribution')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:15000'])
                ->example('0'),

            ImportColumn::make('mortgage_interest')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:30000'])
                ->example('0'),

            ImportColumn::make('insurance_premium')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('2500'),

            ImportColumn::make('tax_exemption_number')
                ->rules(['nullable', 'string', 'max:80']),

            ImportColumn::make('tax_exemption_expiry')
                ->rules(['nullable', 'date_format:Y-m-d']),

            ImportColumn::make('notes')
                ->rules(['nullable', 'string'])
                ->example('Imported from the approved HR employee template.'),
        ];
    }

    public function resolveRecord(): ?Employee
    {
        $employeeNumber = trim((string) ($this->data['employee_number'] ?? ''));
        $idNumber = trim((string) ($this->data['id_passport_number'] ?? ''));

        if ($employeeNumber !== '') {
            return Employee::withTrashed()->firstOrNew([
                'employee_number' => $employeeNumber,
            ]);
        }

        if ($idNumber !== '') {
            return Employee::withTrashed()->firstOrNew([
                'id_passport_number' => $idNumber,
            ]);
        }

        return new Employee();
    }

    protected function beforeCreate(): void
    {
        $this->record->created_by = $this->import->user_id;
    }

    protected function beforeSave(): void
    {
        if ($this->record->trashed()) {
            $this->record->restore();
        }

        $this->record->updated_by = $this->import->user_id;
        $this->record->status = $this->record->status ?: 'active';
        $this->record->is_active = $this->record->status === 'active';
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Employee import completed: '
            . number_format($import->successful_rows)
            . ' '
            . str('row')->plural($import->successful_rows)
            . ' imported successfully.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '
                . number_format($failedRowsCount)
                . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed. Open the import notification to download the failed rows CSV.';
        }

        return $body;
    }
}
