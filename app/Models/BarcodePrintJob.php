<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarcodePrintJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_ids',
        'generated_by',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'product_ids' => 'array',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
