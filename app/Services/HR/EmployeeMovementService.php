<?php

namespace App\Services\HR;

use App\Models\HR\Employee;
use App\Models\HR\EmployeeMovement;
use Illuminate\Support\Facades\DB;

class EmployeeMovementService
{
    public function changePosition(Employee $employee, array $data, string $movementType): EmployeeMovement
    {
        return DB::transaction(function () use ($employee, $data, $movementType): EmployeeMovement {
            $movement = $employee->movements()->create([
                'movement_type' => $movementType,
                'effective_date' => $data['effective_date'],
                'from_department_id' => $employee->department_id,
                'to_department_id' => $data['department_id'] ?? $employee->department_id,
                'from_job_title_id' => $employee->job_title_id,
                'to_job_title_id' => $data['job_title_id'] ?? $employee->job_title_id,
                'from_basic_salary' => $employee->basic_salary,
                'to_basic_salary' => $data['basic_salary'] ?? $employee->basic_salary,
                'previous_status' => $employee->status,
                'new_status' => $employee->status,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'supporting_document_path' => $data['supporting_document_path'] ?? null,
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'created_by' => auth()->id(),
            ]);

            $employee->update([
                'department_id' => $data['department_id'] ?? $employee->department_id,
                'job_title_id' => $data['job_title_id'] ?? $employee->job_title_id,
                'basic_salary' => $data['basic_salary'] ?? $employee->basic_salary,
                'updated_by' => auth()->id(),
            ]);

            return $movement;
        });
    }

    public function changeStatus(Employee $employee, array $data, string $movementType, string $status): EmployeeMovement
    {
        return DB::transaction(function () use ($employee, $data, $movementType, $status): EmployeeMovement {
            $movement = $employee->movements()->create([
                'movement_type' => $movementType,
                'effective_date' => $data['effective_date'],
                'from_department_id' => $employee->department_id,
                'to_department_id' => $employee->department_id,
                'from_job_title_id' => $employee->job_title_id,
                'to_job_title_id' => $employee->job_title_id,
                'from_basic_salary' => $employee->basic_salary,
                'to_basic_salary' => $employee->basic_salary,
                'previous_status' => $employee->status,
                'new_status' => $status,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'supporting_document_path' => $data['supporting_document_path'] ?? null,
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'created_by' => auth()->id(),
            ]);

            $updates = [
                'status' => $status,
                'is_active' => $status === 'active',
                'updated_by' => auth()->id(),
            ];

            if ($movementType === 'termination') {
                $updates['exit_date'] = $data['effective_date'];
                $updates['exit_reason'] = $data['reason'];
                $updates['clearance_status'] = 'pending';
            }

            if ($movementType === 'reinstatement') {
                $updates['exit_date'] = null;
                $updates['exit_reason'] = null;
            }

            $employee->update($updates);

            return $movement;
        });
    }
}
