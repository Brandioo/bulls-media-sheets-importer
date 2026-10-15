<?php

namespace Tests\Feature;

use App\Models\ColumnMapping;
use App\Models\ImportTarget;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\GoogleSheets\GoogleSheetsService;
use App\Services\Import\SheetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SheetImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_mapped_rows_into_target_table(): void
    {
        $source = SheetSource::create([
            'name' => 'Google Sales',
            'spreadsheet_id' => 'google-sheet-id',
            'active' => true,
        ]);

        $tab = SheetTab::create([
            'sheet_source_id' => $source->id,
            'name' => 'Sales',
            'header_row' => 1,
            'active' => true,
        ]);

        $target = ImportTarget::create([
            'sheet_tab_id' => $tab->id,
            'table_name' => 'sales',
            'write_mode' => 'insert',
            'active' => true,
        ]);

        $mappings = [
            [
                'source_column' => 'date',
                'target_column' => 'sale_date',
                'target_type' => 'date',
            ],
            [
                'source_column' => 'product_name',
                'target_column' => 'name',
                'target_type' => 'string',
            ],
            [
                'source_column' => 'price',
                'target_column' => 'product_price',
                'target_type' => 'decimal',
            ],
            [
                'source_column' => 'amount',
                'target_column' => 'amount',
                'target_type' => 'integer',
            ],
        ];

        foreach ($mappings as $mapping) {
            ColumnMapping::create([
                'import_target_id' => $target->id,
                ...$mapping,
                'nullable' => false,
            ]);
        }

        $this->mock(
            GoogleSheetsService::class,
            function (MockInterface $mock) {
                $mock
                    ->shouldReceive('getTabData')
                    ->once()
                    ->with(
                        'google-sheet-id',
                        'Sales',
                        1
                    )
                    ->andReturn([
                        'headers' => [
                            'date',
                            'product_name',
                            'price',
                            'amount',
                        ],
                        'rows' => [
                            [
                                'date' => '2026-10-15',
                                'product_name' => 'Laptop',
                                'price' => '999.95',
                                'amount' => '3',
                            ],
                        ],
                    ]);
            }
        );

        $run = app(
            SheetImporter::class
        )->importSource($source);

        $this->assertSame(
            'success',
            $run->status
        );

        $this->assertSame(
            1,
            $run->rows_read
        );

        $this->assertSame(
            1,
            $run->rows_written
        );

        $this->assertDatabaseHas(
            'sales',
            [
                'name' => 'Laptop',
                'product_price' => 999.95,
                'amount' => 3,
                'sale_date' => '2026-10-15',
            ]
        );
    }
}
