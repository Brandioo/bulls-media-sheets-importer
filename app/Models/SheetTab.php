<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SheetTab extends Model
{
    protected $fillable = [
        'sheet_source_id',
        'name',
        'header_row',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'header_row' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(SheetSource::class, 'sheet_source_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(ImportTarget::class);
    }
}
