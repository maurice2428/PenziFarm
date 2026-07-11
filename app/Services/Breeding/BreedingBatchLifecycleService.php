<?php

namespace App\Services\Breeding;

use App\Models\Animal;
use App\Models\BreedingBatch;
use App\Models\BreedingRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BreedingBatchLifecycleService
{
    public function summary(BreedingBatch $batch): array
    {
        $records = $batch->records()
            ->withTrashed()
            ->get([
                'id',
                'pregnancy_status',
                'birth_outcome',
                'live_birth_count',
                'stillborn_count',
                'deleted_at',
            ]);

        $recordIds = $records->pluck('id');

        $offspringCount = $recordIds->isEmpty()
            ? 0
            : Animal::query()
                ->where(
                    'source_reference_type',
                    BreedingRecord::class
                )
                ->whereIn('source_reference_id', $recordIds)
                ->count();

        return [
            'records' => $records->count(),
            'active_records' => $records
                ->whereNull('deleted_at')
                ->count(),
            'delivered_records' => $records
                ->where('pregnancy_status', 'delivered')
                ->count(),
            'aborted_records' => $records
                ->filter(
                    fn (BreedingRecord $record): bool =>
                        $record->pregnancy_status === 'aborted'
                        || $record->birth_outcome === 'aborted'
                )
                ->count(),
            'live_births' => (int) $records
                ->sum('live_birth_count'),
            'stillborn' => (int) $records
                ->sum('stillborn_count'),
            'registered_offspring' => $offspringCount,
            'can_permanently_delete' =>
                $offspringCount === 0
                && $records
                    ->where('pregnancy_status', 'delivered')
                    ->isEmpty(),
        ];
    }

    public function archive(
        BreedingBatch $batch,
        ?string $reason = null
    ): void {
        DB::transaction(function () use ($batch, $reason): void {
            $batch->refresh();

            if ($batch->trashed()) {
                return;
            }

            $updates = [];

            if (
                Schema::hasColumn(
                    'breeding_batches',
                    'archived_by'
                )
            ) {
                $updates['archived_by'] = auth()->id();
            }

            if (
                Schema::hasColumn(
                    'breeding_batches',
                    'archive_reason'
                )
            ) {
                $updates['archive_reason'] = filled($reason)
                    ? trim((string) $reason)
                    : 'Archived from the breeding batch register.';
            }

            if ($updates !== []) {
                $batch->forceFill($updates)->saveQuietly();
            }

            $batch->delete();
        });
    }

    public function restore(BreedingBatch $batch): void
    {
        DB::transaction(function () use ($batch): void {
            $batch->refresh();

            if (! $batch->trashed()) {
                return;
            }

            $batch->restore();
        });
    }

    public function permanentlyDelete(
        BreedingBatch $batch
    ): void {
        $summary = $this->summary($batch);

        if (! $summary['can_permanently_delete']) {
            throw ValidationException::withMessages([
                'disposition' =>
                    'Permanent deletion is blocked because this batch '
                    . 'contains completed delivery history or registered '
                    . 'offspring. Choose Archive to preserve the evidence '
                    . 'while removing all records from Breeding Outcomes.',
            ]);
        }

        DB::transaction(function () use ($batch): void {
            $batch->forceDelete();
        });
    }
}
