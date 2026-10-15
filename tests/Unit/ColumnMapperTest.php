<?php

namespace Tests\Unit;

use App\Models\ColumnMapping;
use App\Services\Import\ColumnMapper;
use PHPUnit\Framework\TestCase;

class ColumnMapperTest extends TestCase
{
    public function test_it_maps_and_casts_google_sheet_row(): void
    {
        $mappings = collect([
            new ColumnMapping([
                'source_column' => 'date',
                'target_column' => 'sale_date',
                'target_type' => 'date',
                'nullable' => false,
            ]),

            new ColumnMapping([
                'source_column' => 'product_name',
                'target_column' => 'name',
                'target_type' => 'string',
                'nullable' => false,
            ]),

            new ColumnMapping([
                'source_column' => 'price',
                'target_column' => 'product_price',
                'target_type' => 'decimal',
                'nullable' => false,
            ]),

            new ColumnMapping([
                'source_column' => 'amount',
                'target_column' => 'amount',
                'target_type' => 'integer',
                'nullable' => false,
            ]),
        ]);

        $row = [
            'date' => '2026-10-15',
            'product_name' => 'Laptop',
            'price' => '1299.99',
            'amount' => '2',
        ];

        $result = (new ColumnMapper())
            ->mapRow($row, $mappings);

        $this->assertSame(
            '2026-10-15',
            $result['sale_date']
        );

        $this->assertSame(
            'Laptop',
            $result['name']
        );

        $this->assertSame(
            1299.99,
            $result['product_price']
        );

        $this->assertSame(
            2,
            $result['amount']
        );
    }
}
