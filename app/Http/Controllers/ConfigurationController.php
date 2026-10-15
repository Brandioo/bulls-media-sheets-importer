<?php

namespace App\Http\Controllers;

use App\Models\ColumnMapping;
use App\Models\ImportRun;
use App\Models\ImportTarget;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Services\Import\SheetImporter;
use App\Services\Import\TableSchemaManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfigurationController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = SheetSource::query()
            ->with([
                'tabs' => function ($query) {
                    $query
                        ->orderBy('id')
                        ->with([
                            'targets' => function ($query) {
                                $query
                                    ->orderBy('id')
                                    ->with([
                                        'mappings' => fn ($query) =>
                                        $query->orderBy('id'),
                                    ]);
                            },
                        ]);
                },
            ])
            ->orderBy('id')
            ->get();

        $runs = ImportRun::query()
            ->with('source:id,name')
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'sources' => $sources,
            'runs' => $runs,
        ]);
    }

    public function storeSource(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'spreadsheet_id' => [
                'required',
                'string',
                'max:255',
                'unique:sheet_sources,spreadsheet_id',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $data['active'] = $data['active'] ?? true;

        $source = SheetSource::create($data);

        return response()->json(
            $source,
            201
        );
    }

    public function updateSource(
        Request $request,
        SheetSource $source
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'spreadsheet_id' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'sheet_sources',
                    'spreadsheet_id'
                )->ignore($source->id),
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $source->update($data);

        return response()->json(
            $source->fresh()
        );
    }

    public function destroySource(
        SheetSource $source
    ): JsonResponse {
        $source->delete();

        return response()->json([
            'message' => 'Source deleted.',
        ]);
    }

    public function storeTab(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sheet_source_id' => [
                'required',
                'integer',
                'exists:sheet_sources,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'header_row' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $data['header_row'] =
            $data['header_row'] ?? 1;

        $data['active'] =
            $data['active'] ?? true;

        $tab = SheetTab::create($data);

        return response()->json(
            $tab,
            201
        );
    }

    public function updateTab(
        Request $request,
        SheetTab $tab
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'header_row' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $tab->update($data);

        return response()->json(
            $tab->fresh()
        );
    }

    public function destroyTab(
        SheetTab $tab
    ): JsonResponse {
        $tab->delete();

        return response()->json([
            'message' => 'Tab deleted.',
        ]);
    }

    public function storeTarget(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'sheet_tab_id' => [
                'required',
                'integer',
                'exists:sheet_tabs,id',
            ],
            'table_name' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
            ],
            'write_mode' => [
                'sometimes',
                Rule::in([
                    'insert',
                    'upsert',
                    'truncate_insert',
                ]),
            ],
            'unique_by' => [
                'nullable',
                'array',
            ],
            'unique_by.*' => [
                'string',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $data['write_mode'] =
            $data['write_mode'] ?? 'insert';

        $data['active'] =
            $data['active'] ?? true;

        $target = ImportTarget::create($data);

        return response()->json(
            $target,
            201
        );
    }

    public function updateTarget(
        Request $request,
        ImportTarget $target
    ): JsonResponse {
        $data = $request->validate([
            'table_name' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
            ],
            'write_mode' => [
                'sometimes',
                Rule::in([
                    'insert',
                    'upsert',
                    'truncate_insert',
                ]),
            ],
            'unique_by' => [
                'nullable',
                'array',
            ],
            'unique_by.*' => [
                'string',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $target->update($data);

        return response()->json(
            $target->fresh()
        );
    }

    public function destroyTarget(
        ImportTarget $target
    ): JsonResponse {
        $target->delete();

        return response()->json([
            'message' => 'Target deleted.',
        ]);
    }

    public function storeMapping(
        Request $request,
        TableSchemaManager $schemaManager
    ): JsonResponse {
        $data = $this->validateMapping(
            $request
        );

        $mapping = ColumnMapping::create($data);

        $target = $mapping
            ->target()
            ->with('mappings')
            ->firstOrFail();

        $schemaManager
            ->ensureTargetStructure($target);

        return response()->json(
            $mapping,
            201
        );
    }

    public function updateMapping(
        Request $request,
        ColumnMapping $mapping,
        TableSchemaManager $schemaManager
    ): JsonResponse {
        $data = $this->validateMapping(
            $request,
            true
        );

        $mapping->update($data);

        $target = $mapping
            ->target()
            ->with('mappings')
            ->firstOrFail();

        $schemaManager
            ->ensureTargetStructure($target);

        return response()->json(
            $mapping->fresh()
        );
    }

    public function destroyMapping(
        ColumnMapping $mapping
    ): JsonResponse {
        /*
         * We intentionally delete only the configuration.
         * Existing destination columns are not automatically
         * dropped because that could destroy imported data.
         */
        $mapping->delete();

        return response()->json([
            'message' => 'Mapping deleted.',
        ]);
    }

    public function importSource(
        SheetSource $source,
        SheetImporter $importer
    ): JsonResponse {
        $run = $importer
            ->importSource($source);

        return response()->json([
            'message' => 'Import completed.',
            'run' => $run,
        ]);
    }

    private function validateMapping(
        Request $request,
        bool $update = false
    ): array {
        $sometimes = $update
            ? 'sometimes'
            : 'required';

        return $request->validate([
            'import_target_id' => [
                $sometimes,
                'integer',
                'exists:import_targets,id',
            ],

            'source_column' => [
                $sometimes,
                'string',
                'max:255',
            ],

            'target_column' => [
                $sometimes,
                'string',
                'max:64',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/',
            ],

            'target_type' => [
                $sometimes,
                Rule::in([
                    'string',
                    'text',
                    'integer',
                    'bigInteger',
                    'decimal',
                    'boolean',
                    'date',
                    'datetime',
                    'json',
                ]),
            ],

            'type_options' => [
                'nullable',
                'array',
            ],

            'nullable' => [
                'sometimes',
                'boolean',
            ],

            'default_value' => [
                'nullable',
            ],
        ]);
    }
}
