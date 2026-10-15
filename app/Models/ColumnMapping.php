<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColumnMapping extends Model
{
    protected $fillable = [
        'import_target_id',
        'source_column',
        'target_column',
        'target_type',
        'type_options',
        'nullable',
        'default_value',
    ];

    protected function casts(): array
    {
        return [
            'type_options' => 'array',
            'nullable' => 'boolean',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(ImportTarget::class, 'import_target_id');
    }
}
