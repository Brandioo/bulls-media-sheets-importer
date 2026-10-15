<?php

namespace Tests\Feature;

use App\Models\ColumnMapping;
use App\Models\ImportTarget;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Import\TableSchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TableSchemaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_table_and_adds_new_columns(): void
    {
        $source = SheetSource::create([
            'name' => 'Test Sheet',
            'spreadsheet_id' => 'test-sheet',
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

        ColumnMapping::create([
            'import_target_id' => $target->id,
            'source_column' => 'product_name',
            'target_column' => 'name',
            'target_type' => 'string',
            'nullable' => false,
        ]);

        $manager = app(
            TableSchemaManager::class
        );

        $manager->ensureTargetStructure(
            $target
        );

        $this->assertTrue(
            Schema::hasTable('sales')
        );

        $this->assertTrue(
            Schema::hasColumn(
                'sales',
                'name'
            )
        );

        ColumnMapping::create([
            'import_target_id' => $target->id,
            'source_column' => 'supplier',
            'target_column' => 'supplier_name',
            'target_type' => 'string',
            'nullable' => true,
        ]);

        $manager->ensureTargetStructure(
            $target
        );

        $this->assertTrue(
            Schema::hasColumn(
                'sales',
                'supplier_name'
            )
        );
    }
}
