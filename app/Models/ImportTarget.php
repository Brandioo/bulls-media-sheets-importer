<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportTarget extends Model
{
    protected $fillable = [
        'sheet_tab_id',
        'table_name',
        'write_mode',
        'unique_by',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'unique_by' => 'array',
            'active' => 'boolean',
        ];
    }

    public function tab(): BelongsTo
    {
        return $this->belongsTo(SheetTab::class, 'sheet_tab_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ColumnMapping::class);
    }
}
