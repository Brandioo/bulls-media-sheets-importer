<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sheet_source_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * running
             * success
             * failed
             */
            $table->string('status')->default('running');

            $table->unsignedBigInteger('rows_read')->default(0);
            $table->unsignedBigInteger('rows_written')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->text('error_message')->nullable();

            // Extra details about each import execution.
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_runs');
    }
};
