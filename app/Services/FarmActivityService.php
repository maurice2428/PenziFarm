<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FarmActivityService
{
    public function activities(?string $from = null, ?string $to = null, string $module = 'all', ?string $search = null, int $limit = 300): Collection
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(30)->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $activities = collect();

        if (in_array($module, ['all', 'animals'], true)) {
            $activities = $activities->merge($this->animalActivities($fromDate, $toDate));
        }

        if (in_array($module, ['all', 'sales'], true)) {
            $activities = $activities->merge($this->salesActivities($fromDate, $toDate));
        }

        if (in_array($module, ['all', 'accounting'], true)) {
            $activities = $activities->merge($this->accountingActivities($fromDate, $toDate));
        }

        if (in_array($module, ['all', 'projects'], true)) {
            $activities = $activities->merge($this->projectActivities($fromDate, $toDate));
        }

        if (in_array($module, ['all', 'inventory'], true)) {
            $activities = $activities->merge($this->inventoryActivities($fromDate, $toDate));
        }

        if ($search) {
            $needle = mb_strtolower($search);
            $activities = $activities->filter(function (array $activity) use ($needle) {
                return str_contains(mb_strtolower(implode(' ', array_filter([
                    $activity['title'] ?? '',
                    $activity['description'] ?? '',
                    $activity['reference'] ?? '',
                    $activity['module'] ?? '',
                    $activity['badge'] ?? '',
                ]))), $needle);
            });
        }

        return $activities
            ->sortByDesc('date_sort')
            ->take($limit)
            ->values();
    }

    protected function animalActivities(Carbon $from, Carbon $to): Collection
    {
        $table = $this->firstExistingTable(['AnimalsRecords', 'animals_records', 'animals']);
        if (! $table) {
            return collect();
        }

        $dateColumn = $this->firstExistingColumn($table, ['created_at', 'DateCreated', 'createdDate', 'date_created', 'DOB', 'BirthDate', 'DateOfBirth', 'updated_at']);
        if (! $dateColumn) {
            return collect();
        }

        return $this->safe(function () use ($table, $dateColumn, $from, $to) {
            return DB::table($table)
                ->whereBetween($dateColumn, [$from, $to])
                ->orderByDesc($dateColumn)
                ->limit(120)
                ->get()
                ->map(function ($row) use ($dateColumn) {
                    $tag = $this->value($row, ['TagName', 'tag_name', 'tag', 'AnimalName', 'name'], 'Animal Record');
                    $status = $this->value($row, ['Status', 'status'], 'Recorded');
                    $stage = $this->value($row, ['AnimalStage', 'stage', 'animal_stage'], null);
                    $breed = $this->value($row, ['SubBreed', 'Breed', 'breed', 'sub_breed'], null);

                    return $this->activity(
                        module: 'Animals',
                        tone: 'emerald',
                        icon: '🐄',
                        date: $this->value($row, [$dateColumn]),
                        title: $tag,
                        description: trim(implode(' • ', array_filter([$breed, $stage, $status]))),
                        reference: $this->value($row, ['ID', 'id']),
                        amount: null,
                        badge: $status
                    );
                });
        });
    }

    protected function salesActivities(Carbon $from, Carbon $to): Collection
    {
        $table = $this->firstExistingTable(['Sales', 'sales']);
        if (! $table) {
            return collect();
        }

        $dateColumn = $this->firstExistingColumn($table, ['SaleDate', 'sale_date', 'created_at', 'date']);
        if (! $dateColumn) {
            return collect();
        }

        return $this->safe(function () use ($table, $dateColumn, $from, $to) {
            return DB::table($table)
                ->whereBetween($dateColumn, [$from, $to])
                ->orderByDesc($dateColumn)
                ->limit(100)
                ->get()
                ->map(function ($row) use ($dateColumn) {
                    $buyer = $this->value($row, ['BuyerName', 'buyer_name', 'customer_name', 'customer'], 'Customer');
                    $item = $this->value($row, ['AnimalName', 'ItemType', 'item_name', 'product_name'], 'Sale');
                    $status = $this->value($row, ['Status', 'status'], 'Recorded');
                    $amount = (float) $this->value($row, ['SalePrice', 'sale_price', 'total_amount', 'grand_total', 'amount'], 0);
                    $invoice = $this->value($row, ['InvoiceNumber', 'invoice_number', 'invoice_no', 'id']);

                    return $this->activity(
                        module: 'Sales',
                        tone: 'sky',
                        icon: '🧾',
                        date: $this->value($row, [$dateColumn]),
                        title: $buyer,
                        description: $item,
                        reference: $invoice,
                        amount: $amount,
                        badge: $status
                    );
                });
        });
    }

    protected function accountingActivities(Carbon $from, Carbon $to): Collection
    {
        $table = 'accounting_journal_entries';
        if (! Schema::hasTable($table)) {
            return collect();
        }

        return $this->safe(function () use ($from, $to, $table) {
            return DB::table($table)
                ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->limit(120)
                ->get()
                ->map(function ($row) {
                    return $this->activity(
                        module: 'Accounting',
                        tone: $row->status === 'posted' ? 'violet' : 'amber',
                        icon: '📒',
                        date: $row->transaction_date,
                        title: $row->journal_number ?? 'Journal Entry',
                        description: $row->narration ?: ($row->source_type ?: 'Accounting movement'),
                        reference: $row->reference ?? null,
                        amount: (float) ($row->total_debit ?? 0),
                        badge: $row->status ?? 'draft'
                    );
                });
        });
    }

    protected function projectActivities(Carbon $from, Carbon $to): Collection
    {
        $table = 'accounting_project_fund_transactions';
        if (! Schema::hasTable($table)) {
            return collect();
        }

        return $this->safe(function () use ($from, $to, $table) {
            return DB::table($table)
                ->leftJoin('accounting_project_funds as pf', 'pf.id', '=', $table . '.project_fund_id')
                ->whereBetween($table . '.transaction_date', [$from->toDateString(), $to->toDateString()])
                ->orderByDesc($table . '.transaction_date')
                ->orderByDesc($table . '.id')
                ->limit(100)
                ->select($table . '.*', 'pf.name as project_name', 'pf.fund_code')
                ->get()
                ->map(function ($row) {
                    return $this->activity(
                        module: 'Project Funds',
                        tone: 'amber',
                        icon: '🏗️',
                        date: $row->transaction_date,
                        title: $row->project_name ?: 'Project Fund',
                        description: $row->narration ?: ucfirst((string) $row->transaction_type),
                        reference: $row->transaction_number ?? $row->reference ?? $row->fund_code,
                        amount: (float) ($row->amount ?? 0),
                        badge: $row->transaction_type ?? 'project'
                    );
                });
        });
    }

    protected function inventoryActivities(Carbon $from, Carbon $to): Collection
    {
        $table = $this->firstExistingTable(['stock_movements', 'inventory_movements', 'inventory_transactions']);
        if (! $table) {
            return collect();
        }

        $dateColumn = $this->firstExistingColumn($table, ['created_at', 'movement_date', 'transaction_date', 'date']);
        if (! $dateColumn) {
            return collect();
        }

        return $this->safe(function () use ($table, $dateColumn, $from, $to) {
            return DB::table($table)
                ->whereBetween($dateColumn, [$from, $to])
                ->orderByDesc($dateColumn)
                ->limit(80)
                ->get()
                ->map(function ($row) use ($dateColumn) {
                    $type = $this->value($row, ['movement_type', 'type', 'transaction_type'], 'Movement');
                    $qty = $this->value($row, ['quantity', 'qty', 'stock_quantity'], null);

                    return $this->activity(
                        module: 'Inventory',
                        tone: 'lime',
                        icon: '📦',
                        date: $this->value($row, [$dateColumn]),
                        title: ucfirst((string) $type),
                        description: $qty ? ('Quantity: ' . $qty) : 'Stock movement recorded',
                        reference: $this->value($row, ['reference', 'reference_number', 'id']),
                        amount: null,
                        badge: $type
                    );
                });
        });
    }

    protected function activity(string $module, string $tone, string $icon, mixed $date, string $title, ?string $description, mixed $reference, ?float $amount, ?string $badge): array
    {
        $carbon = $date ? Carbon::parse($date) : now();

        return [
            'module' => $module,
            'tone' => $tone,
            'icon' => $icon,
            'date' => $carbon->format('d M Y'),
            'time' => $carbon->format('H:i'),
            'date_sort' => $carbon->timestamp,
            'title' => $title,
            'description' => $description,
            'reference' => $reference,
            'amount' => $amount,
            'badge' => $badge,
        ];
    }

    protected function firstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function value(object $row, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (property_exists($row, $key) && $row->{$key} !== null && $row->{$key} !== '') {
                return $row->{$key};
            }
        }

        return $default;
    }

    protected function safe(callable $callback): Collection
    {
        try {
            return collect($callback());
        } catch (Throwable) {
            return collect();
        }
    }
}
