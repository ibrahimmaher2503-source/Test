<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySession extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'branch_id',
        'reference',
        'status',
        'created_by',
        'started_at',
        'submitted_at',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function counters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inventory_session_counters', 'session_id', 'user_id');
    }

    public function countLines(): HasMany
    {
        return $this->hasMany(InventoryCountLine::class, 'session_id');
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class, 'session_id');
    }
}
