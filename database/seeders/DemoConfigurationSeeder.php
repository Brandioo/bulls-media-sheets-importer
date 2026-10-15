<?php

namespace Database\Seeders;

use App\Models\ColumnMapping;
use App\Models\ImportTarget;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Import\TableSchemaManager;
use Illuminate\Database\Seeder;

class DemoConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $source = SheetSource::updateOrCreate(
            [
                'spreadsheet_id' => 'demo-sheet-123',
            ],
            [
                'name' => 'Demo Sales Sheet',
                'active' => true,
            ]
        );

        $tab = SheetTab::updateOrCreate(
            [
                'sheet_source_id' => $source->id,
                'name' => 'Sales',
            ],
            [
                'header_row' => 1,
                'active' => true,
            ]
        );

        $target = ImportTarget::updateOrCreate(
            [
                'sheet_tab_id' => $tab->id,
                'table_name' => 'sales',
            ],
            [
                'write_mode' => 'insert',
                'unique_by' => null,
                'active' => true,
            ]
        );

        $mappings = [
            [
                'source_column' => 'date',
                'target_column' => 'sale_date',
                'target_type' => 'date',
                'type_options' => null,
                'nullable' => false,
                'default_value' => null,
            ],
            [
                'source_column' => 'product_name',
                'target_column' => 'name',
                'target_type' => 'string',
                'type_options' => [
                    'length' => 255,
                ],
                'nullable' => false,
                'default_value' => null,
            ],
            [
                'source_column' => 'price',
                'target_column' => 'product_price',
                'target_type' => 'decimal',
                'type_options' => [
                    'precision' => 10,
                    'scale' => 2,
                ],
                'nullable' => false,
                'default_value' => null,
            ],
            [
                'source_column' => 'amount',
                'target_column' => 'amount',
                'target_type' => 'integer',
                'type_options' => null,
                'nullable' => false,
                'default_value' => null,
            ],
        ];

        foreach ($mappings as $mapping) {
            ColumnMapping::updateOrCreate(
                [
                    'import_target_id' => $target->id,
                    'target_column' => $mapping['target_column'],
                ],
                array_merge(
                    $mapping,
                    [
                        'import_target_id' => $target->id,
                    ]
                )
            );
        }

        /*
         * Destination tables are intentionally NOT Laravel migrations.
         *
         * They are generated dynamically from configuration because
         * that is one of the requirements of the assignment.
         */
        $target->load('mappings');

        app(TableSchemaManager::class)
            ->ensureTargetStructure($target);
    }
}
