<?php

namespace App\Console\Commands;

use App\Models\Animal;
use App\Services\BreedPurityService;
use Illuminate\Console\Command;

class RecalculateAnimalPurity extends Command
{
    protected $signature = 'animals:recalculate-purity
                            {--pending : Recalculate only records that are still pending}';

    protected $description = 'Recalculate animal breed purity from foundation flags, verified purity and recorded parentage.';

    public function handle(BreedPurityService $purityService): int
    {
        $query = Animal::query()->orderBy('id');

        if ($this->option('pending')) {
            $query->where(function ($query): void {
                $query
                    ->whereNull('breed_purity_percent')
                    ->orWhere('purity_status', 'pending');
            });
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No animal records require purity recalculation.');

            return self::SUCCESS;
        }

        $this->info("Recalculating purity for {$total} animal record(s)...");
        $bar = $this->output->createProgressBar($total * 4);
        $bar->start();

        /*
         * Four passes support parent/offspring chains even where historic
         * records were entered out of chronological order.
         */
        for ($pass = 1; $pass <= 4; $pass++) {
            (clone $query)
                ->chunkById(100, function ($animals) use ($purityService, $bar): void {
                    foreach ($animals as $animal) {
                        $purityService->recalculate(
                            $animal,
                            cascadeToDescendants: false,
                        );

                        $bar->advance();
                    }
                });
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Breed purity recalculation completed.');

        return self::SUCCESS;
    }
}
