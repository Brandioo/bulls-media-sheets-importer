<?php

namespace App\Services\Import;

use App\Models\ImportRun;
use App\Models\ImportTarget;
use App\Models\SheetSource;
use App\Services\GoogleSheets\GoogleSheetsService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class SheetImporter
{
    public function __construct(
        private readonly GoogleSheetsService $googleSheets,
        private readonly TableSchemaManager $schemaManager,
        private readonly ColumnMapper $columnMapper,
    ) {
    }

    public function importSource(SheetSource $source): ImportRun
    {
        $run = ImportRun::create([
            'sheet_source_id' => $source->id,
            'status' => 'running',
            'rows_read' => 0,
            'rows_written' => 0,
            'started_at' => now(),
        ]);

        $rowsRead = 0;
        $rowsWritten = 0;

        try {
            $source->load([
                'tabs' => fn ($query) => $query
                    ->where('active', true)
                    ->with([
                        'targets' => fn ($query) => $query
                            ->where('active', true)
                            ->with('mappings'),
                    ]),
            ]);

            foreach ($source->tabs as $tab) {
                $sheetData = $this->googleSheets->getTabData(
                    $source->spreadsheet_id,
                    $tab->name,
                    $tab->header_row
                );

                $rows = $sheetData['rows'];

                $rowsRead += count($rows);

                foreach ($tab->targets as $target) {
                    $rowsWritten += $this->importTarget(
                        $target,
                        $rows
                    );
                }
            }

            $run->update([
                'status' => 'success',
                'rows_read' => $rowsRead,
                'rows_written' => $rowsWritten,
                'finished_at' => now(),
            ]);

            return $run->fresh();
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'rows_read' => $rowsRead,
                'rows_written' => $rowsWritten,
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function importTarget(
        ImportTarget $target,
        array $sourceRows
    ): int {
        $this->schemaManager->ensureTargetStructure($target);

        $tableName = $this->validateIdentifier(
            $target->table_name
        );

        $mappings = $target->mappings;

        if ($mappings->isEmpty()) {
            throw new InvalidArgumentException(
                "Target [{$tableName}] has no mappings."
            );
        }

        $rows = [];

        foreach ($sourceRows as $sourceRow) {
            $mappedRow = $this->columnMapper->mapRow(
                $sourceRow,
                $mappings
            );

            $mappedRow['created_at'] = now();
            $mappedRow['updated_at'] = now();

            $rows[] = $mappedRow;
        }

        if ($rows === []) {
            return 0;
        }

        return match ($target->write_mode) {
            'insert' => $this->insertRows(
                $tableName,
                $rows
            ),

            'truncate_insert' => $this->truncateAndInsert(
                $tableName,
                $rows
            ),

            'upsert' => $this->upsertRows(
                $target,
                $tableName,
                $rows
            ),

            default => throw new InvalidArgumentException(
                "Unsupported write mode [{$target->write_mode}]."
            ),
        };
    }

    private function insertRows(
        string $tableName,
        array $rows
    ): int {
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($tableName)->insert($chunk);
        }

        return count($rows);
    }

    private function truncateAndInsert(
        string $tableName,
        array $rows
    ): int {
        DB::table($tableName)->truncate();

        return $this->insertRows(
            $tableName,
            $rows
        );
    }

    private function upsertRows(
        ImportTarget $target,
        string $tableName,
        array $rows
    ): int {
        $uniqueBy = $target->unique_by ?? [];

        if ($uniqueBy === []) {
            throw new InvalidArgumentException(
                "Target [{$tableName}] uses upsert but has no unique_by configuration."
            );
        }

        foreach ($uniqueBy as $column) {
            $this->validateIdentifier($column);
        }

        foreach ($rows as $row) {
            $conditions = [];

            foreach ($uniqueBy as $column) {
                if (! array_key_exists($column, $row)) {
                    throw new InvalidArgumentException(
                        "Upsert column [{$column}] is not mapped for target [{$tableName}]."
                    );
                }

                $conditions[$column] = $row[$column];
            }

            $values = $row;

            unset($values['created_at']);

            DB::table($tableName)->updateOrInsert(
                $conditions,
                $values
            );
        }

        return count($rows);
    }

    private function validateIdentifier(string $value): string
    {
        if (! preg_match(
            '/^[A-Za-z_][A-Za-z0-9_]*$/',
            $value
        )) {
            throw new InvalidArgumentException(
                "Invalid database identifier [{$value}]."
            );
        }

        return $value;
    }
}
