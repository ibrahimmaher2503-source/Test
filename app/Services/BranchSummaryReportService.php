<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BranchSummaryReportService
{
    /**
     * One row per branch: session count, total items counted, total variance, and
     * how many `pending_review` products were touched by a session in that branch
     * within the date range (SPEC §7.2). Products aren't branch-scoped themselves
     * (SPEC §2 note — catalog is shared), so "per branch" is read as "created via a
     * scan in a session belonging to that branch" — the closest sensible mapping,
     * called out as an assumption since SPEC doesn't spell it out further.
     */
    public function build(Collection $branches, Carbon $from, Carbon $to): Collection
    {
        return $branches->map(function (Branch $branch) use ($from, $to) {
            $sessions = $branch->inventorySessions()
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->withCount('countLines')
                ->get();

            $sessionIds = $sessions->pluck('id');

            $totals = \App\Models\InventoryCountLine::query()
                ->whereIn('session_id', $sessionIds)
                ->selectRaw('COALESCE(SUM(counted_quantity), 0) as total_counted')
                ->selectRaw('COALESCE(SUM(counted_quantity - expected_quantity_snapshot), 0) as total_variance')
                ->first();

            $pendingReviewCount = Product::query()
                ->where('status', 'pending_review')
                ->whereHas('countLines', fn ($query) => $query->whereIn('session_id', $sessionIds))
                ->count();

            return [
                'branch' => $branch,
                'sessions_count' => $sessions->count(),
                'total_counted' => (int) $totals->total_counted,
                'total_variance' => (int) $totals->total_variance,
                'pending_review_count' => $pendingReviewCount,
            ];
        });
    }
}
