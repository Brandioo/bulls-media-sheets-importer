<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sheet_tab_id')
                ->constrained()
                ->cascadeOnDelete();

            // Destination MySQL table.
            $table->string('table_name');

            /*
             * Supported modes:
             *
             * insert
             * upsert
             * truncate_insert
             */
            $table->string('write_mode')->default('insert');

            /*
             * Used for upsert.
             *
             * Example:
             * ["date", "name"]
             */
            $table->json('unique_by')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_targets');
    }
};
