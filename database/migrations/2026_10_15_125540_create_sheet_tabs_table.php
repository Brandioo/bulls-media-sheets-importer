<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sheet_tabs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sheet_source_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            // Row containing the Google Sheet column headers.
            $table->unsignedInteger('header_row')->default(1);

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique([
                'sheet_source_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sheet_tabs');
    }
};
