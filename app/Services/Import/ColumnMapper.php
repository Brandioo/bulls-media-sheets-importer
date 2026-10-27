<?php

namespace App\Services\Import;

use App\Models\ColumnMapping;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class ColumnMapper
{
    /**
     * Convert one Google Sheet row into one database row.
     *
     * Example:
     *
     * Sheet:
     * [
     *     'product_name' => 'Laptop',
     *     'price' => '1299.99',
     * ]
     *
     * Result:
     * [
     *     'name' => 'Laptop',
     *     'product_price' => 1299.99,
     * ]
     */
    public function mapRow(array $sourceRow, Collection $mappings): array
    {
        $result = [];

        /** @var ColumnMapping $mapping */
        foreach ($mappings as $mapping) {
            $value = $sourceRow[$mapping->source_column] ?? null;

            if ($this->isEmpty($value)) {
                if ($mapping->default_value !== null) {
                    $value = $mapping->default_value;
                } elseif ($mapping->nullable) {
                    $value = null;
                } else {
                    throw new RuntimeException(
                        "Required source column [{$mapping->source_column}] " .
                        "is missing or empty."
                    );
                }
            }

            $result[$mapping->target_column] =
                $this->castValue(
                    $value,
                    $mapping->target_type
                );
        }

        return $result;
    }

    private function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'string',
            'text' => (string)$value,

            'integer',
            'bigInteger' => $this->toInteger($value),

            'decimal' => $this->toDecimal($value),

            'boolean' => $this->toBoolean($value),

            'date' => $this->toDate($value),

            'datetime' => $this->toDateTime($value),

            'json' => $this->toJson($value),

            default => throw new InvalidArgumentException(
                "Unsupported mapping type [{$type}]."
            ),
        };
    }

    private function toInteger(mixed $value): int
    {
        if (!is_numeric($value)) {
            throw new RuntimeException(
                "Value [{$value}] cannot be converted to integer."
            );
        }

        return (int)$value;
    }

    private function toDecimal(mixed $value): float
    {
        if (!is_numeric($value)) {
            throw new RuntimeException(
                "Value [{$value}] cannot be converted to decimal."
            );
        }

        return (float)$value;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(
            trim((string)$value)
        );

        return match ($normalized) {
            '1',
            'true',
            'yes',
            'y' => true,

            '0',
            'false',
            'no',
            'n' => false,

            default => throw new RuntimeException(
                "Value [{$value}] cannot be converted to boolean."
            ),
        };
    }

    private function toDate(mixed $value): string
    {
        try {
            return \Carbon\Carbon::parse(
                (string)$value
            )->format('Y-m-d');
        } catch (\Throwable) {
            throw new RuntimeException(
                "Value [{$value}] cannot be converted to date."
            );
        }
    }

    private function toDateTime(mixed $value): string
    {
        try {
            return \Carbon\Carbon::parse(
                (string)$value
            )->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            throw new RuntimeException(
                "Value [{$value}] cannot be converted to datetime."
            );
        }
    }

    private function toJson(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR
            );
        }

        $decoded = json_decode(
            (string)$value,
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Value cannot be converted to valid JSON.'
            );
        }

        return json_encode(
            $decoded,
            JSON_THROW_ON_ERROR
        );
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null
            || (
                is_string($value)
                && trim($value) === ''
            );
    }
}
