<?php

namespace App\Services\Import;

use App\Models\ColumnMapping;
use App\Models\ImportTarget;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class TableSchemaManager
{
    private const ALLOWED_TYPES = [
        'string',
        'text',
        'integer',
        'bigInteger',
        'decimal',
        'boolean',
        'date',
        'datetime',
        'json',
    ];

    private const RESERVED_COLUMNS = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function ensureTargetStructure(ImportTarget $target): void
    {
        $tableName = $this->validateIdentifier(
            $target->table_name,
            'table'
        );

        $mappings = $target->relationLoaded('mappings')
            ? $target->mappings
            : $target->mappings()->get();

        if ($mappings->isEmpty()) {
            throw new InvalidArgumentException(
                "Import target [{$tableName}] has no column mappings."
            );
        }

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($mappings) {
                $table->id();

                foreach ($mappings as $mapping) {
                    $this->addColumn($table, $mapping);
                }

                $table->timestamps();
            });

            return;
        }

        foreach ($mappings as $mapping) {
            $columnName = $this->validateColumnName(
                $mapping->target_column
            );

            if (! Schema::hasColumn($tableName, $columnName)) {
                Schema::table(
                    $tableName,
                    function (Blueprint $table) use ($mapping) {
                        $this->addColumn($table, $mapping);
                    }
                );
            }
        }
    }

    private function addColumn(
        Blueprint $table,
        ColumnMapping $mapping
    ): void {
        $columnName = $this->validateColumnName(
            $mapping->target_column
        );

        $type = $mapping->target_type;

        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                "Unsupported database type [{$type}]."
            );
        }

        $options = $mapping->type_options ?? [];

        $column = match ($type) {
            'string' => $table->string(
                $columnName,
                $this->getStringLength($options)
            ),

            'text' => $table->text($columnName),

            'integer' => $table->integer($columnName),

            'bigInteger' => $table->bigInteger($columnName),

            'decimal' => $table->decimal(
                $columnName,
                $this->getDecimalPrecision($options),
                $this->getDecimalScale($options)
            ),

            'boolean' => $table->boolean($columnName),

            'date' => $table->date($columnName),

            'datetime' => $table->dateTime($columnName),

            'json' => $table->json($columnName),
        };

        if ($mapping->nullable) {
            $column->nullable();
        }

        if (
            $mapping->default_value !== null
            && ! in_array($type, ['text', 'json'], true)
        ) {
            $column->default(
                $this->castDefaultValue(
                    $mapping->default_value,
                    $type
                )
            );
        }
    }

    private function validateColumnName(string $columnName): string
    {
        $columnName = $this->validateIdentifier(
            $columnName,
            'column'
        );

        if (in_array($columnName, self::RESERVED_COLUMNS, true)) {
            throw new InvalidArgumentException(
                "Column [{$columnName}] is reserved."
            );
        }

        return $columnName;
    }

    private function validateIdentifier(
        string $value,
        string $type
    ): string {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            throw new InvalidArgumentException(
                "Invalid {$type} identifier [{$value}]."
            );
        }

        return $value;
    }

    private function getStringLength(array $options): int
    {
        $length = (int) ($options['length'] ?? 255);

        if ($length < 1 || $length > 255) {
            throw new InvalidArgumentException(
                'String length must be between 1 and 255.'
            );
        }

        return $length;
    }

    private function getDecimalPrecision(array $options): int
    {
        $precision = (int) ($options['precision'] ?? 10);

        if ($precision < 1 || $precision > 65) {
            throw new InvalidArgumentException(
                'Decimal precision must be between 1 and 65.'
            );
        }

        return $precision;
    }

    private function getDecimalScale(array $options): int
    {
        $precision = $this->getDecimalPrecision($options);
        $scale = (int) ($options['scale'] ?? 2);

        if ($scale < 0 || $scale > $precision) {
            throw new InvalidArgumentException(
                'Decimal scale must be between 0 and precision.'
            );
        }

        return $scale;
    }

    private function castDefaultValue(
        string $value,
        string $type
    ): mixed {
        return match ($type) {
            'integer',
            'bigInteger' => (int) $value,

            'decimal' => (float) $value,

            'boolean' => filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN
            ),

            default => $value,
        };
    }
}
