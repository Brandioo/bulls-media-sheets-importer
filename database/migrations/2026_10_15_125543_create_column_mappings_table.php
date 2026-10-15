<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('column_mappings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('import_target_id')
                ->constrained()
                ->cascadeOnDelete();

            // Header name from Google Sheets.
            $table->string('source_column');

            // Destination database column name.
            $table->string('target_column');

            /*
             * Supported types:
             *
             * string
             * text
             * integer
             * bigInteger
             * decimal
             * boolean
             * date
             * datetime
             * json
             */
            $table->string('target_type')->default('string');

            /*
             * Additional options for types.
             *
             * Example:
             * {
             *     "precision": 10,
             *     "scale": 2
             * }
             */
            $table->json('type_options')->nullable();

            $table->boolean('nullable')->default(true);

            $table->text('default_value')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'import_target_id',
                    'target_column',
                ],
                'mapping_target_column_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('column_mappings');
    }
};
