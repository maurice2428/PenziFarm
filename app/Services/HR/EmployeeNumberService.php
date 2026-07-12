<?php

namespace App\Services\HR;

use App\Models\HR\EmployeeNumberSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeNumberService
{
    public function next(): string
    {
        $prefix = $this->prefix();

        // Ensure the sequence row exists. The unique index protects concurrent first use.
        try {
            EmployeeNumberSequence::query()->firstOrCreate(
                ['prefix' => $prefix],
                ['last_number' => 0],
            );
        } catch (QueryException) {
            // Another request may have created the sequence at the same time.
        }

        $number = DB::transaction(function () use ($prefix): int {
            $sequence = EmployeeNumberSequence::query()
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->last_number++;
            $sequence->save();

            return $sequence->last_number;
        }, attempts: 5);

        return $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    public function prefix(): string
    {
        $override = $this->setting('hr.employee_number_prefix');

        if (filled($override)) {
            $clean = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $override));

            if ($clean !== '') {
                return Str::endsWith($clean, 'STF') ? $clean : $clean . 'STF';
            }
        }

        $organizationName = $this->setting('company.name')
            ?: $this->setting('farm.name')
            ?: $this->setting('organization.name')
            ?: config('app.name', 'Organization');

        return $this->companyCode((string) $organizationName) . 'STF';
    }

    private function companyCode(string $organizationName): string
    {
        $words = preg_split(
            '/\s+/',
            trim((string) preg_replace('/[^A-Z0-9]+/i', ' ', $organizationName)),
        ) ?: [];

        $ignored = [
            'THE', 'AND', 'KENYA', 'FARM', 'FARMS', 'COMPANY', 'CO',
            'LIMITED', 'LTD', 'PLC', 'LLP', 'GROUP', 'HOLDINGS',
            'ENTERPRISE', 'ENTERPRISES', 'ORGANIZATION', 'ORGANISATION',
        ];

        $meaningful = collect($words)
            ->map(fn (string $word): string => strtoupper($word))
            ->filter(fn (string $word): bool => $word !== '' && ! in_array($word, $ignored, true))
            ->values();

        if ($meaningful->isEmpty()) {
            return 'ORG';
        }

        if ($meaningful->count() >= 3) {
            return $meaningful
                ->take(3)
                ->map(fn (string $word): string => mb_substr($word, 0, 1))
                ->implode('');
        }

        if ($meaningful->count() === 2) {
            $first = $meaningful->get(0);
            $second = $meaningful->get(1);
            $extra = $this->firstUsefulConsonant(mb_substr($first, 1));

            return mb_substr($first, 0, 1)
                . mb_substr($second, 0, 1)
                . ($extra ?: mb_substr($first, 1, 1) ?: 'X');
        }

        $word = $meaningful->first();
        $consonants = preg_replace('/[AEIOU]/i', '', $word) ?: '';
        $code = mb_substr($consonants, 0, 3);

        if (mb_strlen($code) < 3) {
            $code .= mb_substr($word, 0, 3 - mb_strlen($code));
        }

        return str_pad(mb_substr($code, 0, 3), 3, 'X');
    }

    private function firstUsefulConsonant(string $value): ?string
    {
        $consonants = preg_replace('/[AEIOU]/i', '', strtoupper($value)) ?: '';

        return $consonants !== '' ? mb_substr($consonants, 0, 1) : null;
    }

    private function setting(string $key): mixed
    {
        return function_exists('setting') ? setting($key) : null;
    }
}
