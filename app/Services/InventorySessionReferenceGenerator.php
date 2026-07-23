<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventorySession;
use Illuminate\Support\Carbon;

class InventorySessionReferenceGenerator
{
    /**
     * Builds references like INV-BR01-20260723-01: branch code, date, and a
     * per-branch-per-day sequence number.
     */
    public function generate(Branch $branch, ?Carbon $date = null): string
    {
        $date ??= now();
        $datePart = $date->format('Ymd');
        $prefix = "INV-{$branch->code}-{$datePart}-";

        $countToday = InventorySession::query()
            ->where('branch_id', $branch->id)
            ->where('reference', 'like', $prefix.'%')
            ->count();

        $sequence = str_pad((string) ($countToday + 1), 2, '0', STR_PAD_LEFT);

        return $prefix.$sequence;
    }
}
