<?php

namespace App\Services\GoogleSheets;

use Google\Client;
use Google\Service\Sheets;
use InvalidArgumentException;
use RuntimeException;

class GoogleSheetsService
{
    private Sheets $sheets;

    public function __construct()
    {
        $credentialsPath = (string) config(
            'google-sheets.credentials_path'
        );

        if (! str_starts_with($credentialsPath, '/')) {
            $credentialsPath = base_path($credentialsPath);
        }

        if (! is_file($credentialsPath)) {
            throw new RuntimeException(
                "Google credentials file not found at [{$credentialsPath}]."
            );
        }

        $client = new Client();

        $client->setApplicationName(
            (string) config('google-sheets.application_name')
        );

        $client->setScopes([
            Sheets::SPREADSHEETS_READONLY,
        ]);

        $client->setAuthConfig($credentialsPath);

        $this->sheets = new Sheets($client);
    }

    /**
     * Return all tab names from a spreadsheet.
     */
    public function getTabNames(string $spreadsheetId): array
    {
        $spreadsheet = $this->sheets->spreadsheets->get(
            $spreadsheetId,
            [
                'fields' => 'sheets.properties.title',
            ]
        );

        $tabs = [];

        foreach ($spreadsheet->getSheets() as $sheet) {
            $tabs[] = $sheet->getProperties()->getTitle();
        }

        return $tabs;
    }

    /**
     * Read a tab and convert rows into associative arrays
     * using the configured header row.
     *
     * Example:
     *
     * [
     *     'headers' => ['date', 'product_name', 'price'],
     *     'rows' => [
     *         [
     *             'date' => '2026-10-15',
     *             'product_name' => 'Laptop',
     *             'price' => '1200.00',
     *         ],
     *     ],
     * ]
     */
    public function getTabData(
        string $spreadsheetId,
        string $tabName,
        int $headerRow = 1
    ): array {
        if ($headerRow < 1) {
            throw new InvalidArgumentException(
                'Header row must be greater than or equal to 1.'
            );
        }

        $escapedTabName = str_replace(
            "'",
            "''",
            $tabName
        );

        $range = "'{$escapedTabName}'!A:ZZZ";

        $response = $this->sheets
            ->spreadsheets_values
            ->get(
                $spreadsheetId,
                $range,
                [
                    'majorDimension' => 'ROWS',
                ]
            );

        $values = $response->getValues() ?? [];

        if ($values === []) {
            return [
                'headers' => [],
                'rows' => [],
            ];
        }

        $headerIndex = $headerRow - 1;

        if (! isset($values[$headerIndex])) {
            throw new RuntimeException(
                "Header row [{$headerRow}] does not exist in tab [{$tabName}]."
            );
        }

        $headers = array_map(
            static fn ($header) => trim((string) $header),
            $values[$headerIndex]
        );

        $this->validateHeaders($headers, $tabName);

        $rows = [];

        foreach (
            array_slice($values, $headerRow)
            as $row
        ) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $row = array_pad(
                $row,
                count($headers),
                null
            );

            $mappedRow = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $mappedRow[$header] = $row[$index] ?? null;
            }

            $rows[] = $mappedRow;
        }

        return [
            'headers' => array_values(
                array_filter(
                    $headers,
                    static fn ($header) => $header !== ''
                )
            ),
            'rows' => $rows,
        ];
    }

    private function validateHeaders(
        array $headers,
        string $tabName
    ): void {
        $usedHeaders = [];

        foreach ($headers as $header) {
            if ($header === '') {
                continue;
            }

            if (isset($usedHeaders[$header])) {
                throw new RuntimeException(
                    "Duplicate header [{$header}] found in tab [{$tabName}]."
                );
            }

            $usedHeaders[$header] = true;
        }
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (
                $value !== null
                && trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }
}
