<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRun extends Model
{
    protected $fillable = [
        'sheet_source_id',
        'status',
        'rows_read',
        'rows_written',
        'started_at',
        'finished_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'rows_read' => 'integer',
            'rows_written' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SheetSource::class, 'sheet_source_id');
    }
}
