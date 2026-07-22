<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'product_id',
        'counted_quantity',
        'expected_quantity_snapshot',
        'last_scanned_by',
        'last_scanned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_scanned_at' => 'datetime',
        ];
    }

    protected function variance(): Attribute
    {
        return Attribute::get(
            fn (): int => $this->counted_quantity - $this->expected_quantity_snapshot,
        );
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(InventorySession::class, 'session_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lastScannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_scanned_by');
    }
}
