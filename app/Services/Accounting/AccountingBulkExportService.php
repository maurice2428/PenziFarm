<?php

namespace App\Services\Accounting;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingBulkExportService
{
    /**
     * @param array<string, callable|string> $columns
     */
    public function csv(
        Collection $records,
        array $columns,
        string $filename
    ): StreamedResponse {
        return response()->streamDownload(
            function () use ($records, $columns): void {
                $handle = fopen('php://output', 'wb');
                fputcsv($handle, array_keys($columns));

                foreach ($records as $record) {
                    $row = [];

                    foreach ($columns as $resolver) {
                        $value = is_callable($resolver)
                            ? $resolver($record)
                            : data_get($record, $resolver);

                        if ($value instanceof \BackedEnum) {
                            $value = $value->value;
                        }

                        if ($value instanceof \DateTimeInterface) {
                            $value = $value->format('Y-m-d H:i:s');
                        }

                        if (is_array($value)) {
                            $value = json_encode($value);
                        }

                        $row[] = $value;
                    }

                    fputcsv($handle, $row);
                }

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }
}
