<?php

namespace App\Console\Commands;

use App\Models\SheetSource;
use App\Services\Import\SheetImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportGoogleSheets extends Command
{
    protected $signature = 'sheets:import
                            {--source= : Import only one SheetSource ID}';

    protected $description =
        'Import configured Google Sheets data into database tables';

    public function handle(
        SheetImporter $importer
    ): int {
        $query = SheetSource::query()
            ->where('active', true);

        if ($sourceId = $this->option('source')) {
            $query->whereKey($sourceId);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->warn('No active Google Sheet sources found.');

            return self::SUCCESS;
        }

        $failed = false;

        foreach ($sources as $source) {
            $this->info(
                "Importing: {$source->name}"
            );

            try {
                $run = $importer->importSource($source);

                $this->info(
                    "Finished: {$run->rows_read} rows read, ".
                    "{$run->rows_written} rows written."
                );
            } catch (Throwable $exception) {
                $failed = true;

                $this->error(
                    "Failed: {$exception->getMessage()}"
                );
            }
        }

        return $failed
            ? self::FAILURE
            : self::SUCCESS;
    }
}
